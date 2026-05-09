<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Mpesa;
use App\Models\Order;
use App\Events\OrderPaymentSuccessful;
use App\Events\OrderPaymentFailed;

class PaymentController extends Controller
{
    protected $shortcode;
    protected $passkey;

    public function __construct()
    {
        $this->shortcode = config('app.MPESA_SHORTCODE');
        $this->passkey = config('app.MPESA_PASSKEY');
    }
    public function getToken()
    {
        $credentials = [
            "ConsumerKey" => config('app.MPESA_CONSUMER_KEY'),
            "ConsumerSecret" => config('app.MPESA_CONSUMER_SECRET'),
        ];

        $client = new Client();

        try {
            $response = $client->request('GET', 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', [
                'auth' => [$credentials['ConsumerKey'], $credentials['ConsumerSecret']]
            ]);

            return json_decode($response->getBody())->access_token;
        } catch (\Exception $e) {
            logger('M-Pesa Token Error: ' . $e->getMessage());
            return null;
        }
    }
    public function mpesaSTK(Request $request)
    {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        $contact = '254' . preg_replace('/\D/', '', ltrim($request->phone, '+2540'));

        try {
            $client = new Client();
            $token = $this->getToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with M-Pesa. Please check credentials.'
                ], 500);
            }
            logger($request->amount);
            logger($contact);


            $response = $client->request('POST', 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => [
                    "BusinessShortCode" => $this->shortcode,
                    "Password" => $password,
                    "Timestamp" => $timestamp,
                    "TransactionType" => "CustomerBuyGoodsOnline",
                    "Amount" => round($request->amount),
                    "PartyA" => $contact,
                    "PartyB" => config('app.MPESA_SHORTCODE', $this->shortcode),
                    "PhoneNumber" => $contact,
                    "CallBackURL" => "https://api.ngwindsongk.com/api/mpesa/mpesaCallback",
                    "AccountReference" => $request->order_id,
                    "TransactionDesc" => "Payment"
                ]
            ]);
            $responseBody = $response->getBody()->getContents();
            logger($responseBody);

            $jsonResponse = json_decode($responseBody);

            Mpesa::create([
                'checkout_request_id' => $jsonResponse->CheckoutRequestID ?? null,
                'result_code' => $jsonResponse->ResponseCode ?? null,
                'result_desc' => $jsonResponse->ResponseDescription ?? null,
                'merchant_request_id' => $jsonResponse->MerchantRequestID ?? null,
                'phone' => $contact,
                'amount' => $request->amount,
                'account_reference' => $request->order_id,
            ]);

            if (($jsonResponse->ResponseCode ?? '') == "0") {
                logger('Payment prompt sent to ' . $contact);
                return response()->json([
                    'success' => true,
                    'ResponseCode' => $jsonResponse->ResponseCode,
                    'CheckoutRequestID' => $jsonResponse->CheckoutRequestID,
                    'CustomerMessage' => $jsonResponse->CustomerMessage ?? ''
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $jsonResponse->ResponseDescription ?? $jsonResponse->errorMessage ?? 'Failed to initiate payment prompt.'
                ], 400);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $errorData = json_decode($responseBody);
            logger('M-Pesa API Error: ' . $responseBody);
            return response()->json([
                'success' => false,
                'message' => $errorData->errorMessage ?? 'M-Pesa Service Error. Please ensure your number is correct and active.'
            ], 400);
        } catch (\Exception $e) {
            logger('M-Pesa Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during payment processing.'
            ], 500);
        }
    }
    public function mpesaCallback(Request $request)
    {
        $payload = $request->all();
        logger('M-Pesa Callback received:');
        logger('M-Pesa Callback:', $payload);

        try {
            $body = $payload['Body']['stkCallback'] ?? null;

            if (!$body) {
                return response()->json(['status' => 'error', 'message' => 'Invalid callback payload'], 400);
            }

            $resultCode = $body['ResultCode'];
            $resultDesc = $body['ResultDesc'];
            $checkoutRequestId = $body['CheckoutRequestID'] ?? null;
            $mpesa = Mpesa::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$mpesa) {
                logger("M-Pesa Callback | Mpesa record not found for CheckoutRequestID: $checkoutRequestId");
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            $mpesa->update([
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'status' => $resultCode == 0 ? 'success' : 'failed',
            ]);

            if ($resultCode == 0) {
                $items = collect($body['CallbackMetadata']['Item']);
                $mpesaCode = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
                $mpesa->update(['mpesa_receipt_number' => $mpesaCode]);
            }

            $order = Order::where('slug', $mpesa->account_reference)->first();

            if (!$order) {
                logger("M-Pesa Callback | Order not found for slug: " . $mpesa->account_reference);
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            if ($resultCode == 0) {
                // Payment successful
                $items = collect($body['CallbackMetadata']['Item']);
                $mpesaCode = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
                $amount = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
                $phone = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;

                logger("Payment SUCCESS | Receipt: $mpesaCode | Amount: $amount | Phone: $phone");

                $order->update([
                    'payment_status' => 'success',
                    'payment_reference' => $mpesaCode,
                ]);

                OrderPaymentSuccessful::dispatch($order);

            } else {
                logger("Payment FAILED | Code: $resultCode | Desc: $resultDesc");
                $order->update([
                    'payment_status' => 'failed',
                ]);

                OrderPaymentFailed::dispatch($order);
            }

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        } catch (\Exception $e) {
            logger('Callback error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }
    }

    public function adminIndex(Request $request)
    {
        $query = Mpesa::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('phone', 'like', "%$search%")
                ->orWhere('mpesa_receipt_number', 'like', "%$search%")
                ->orWhere('account_reference', 'like', "%$search%");
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($payments);
    }

    public function checkStatus($orderId)
    {
        $order = Order::where('slug', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $order->payment_status, // success, failed, pending
            'reference' => $order->payment_reference
        ]);
    }
}