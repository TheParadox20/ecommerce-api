<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Mpesa;
use App\Models\Order;
use App\Events\OrderPaymentSuccessful;
use App\Events\OrderPaymentFailed;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewOrderReceived;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $shortcode;
    protected $till;
    protected $passkey;

    public function __construct()
    {
        $this->shortcode = config('app.MPESA_SHORTCODE');
        $this->till = config('app.MPESA_TILL_NUMBER');
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
            $order = Order::where('slug', $request->order_id)->first();
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ], 404);
            }
            
            $actualAmount = $order->total;


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
                    "Amount" => round($actualAmount),
                    "PartyA" => $contact,
                    "PartyB" => $this->till,
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
                'amount' => $actualAmount,
                'account_reference' => $request->order_id,
            ]);

            if (($jsonResponse->ResponseCode ?? '') == "0") {
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
        // IP Allowlisting for Safaricom (in production)
        Log::info('M-Pesa Callback received: ' . json_encode($request->all()));
        if (config('app.env') === 'production') {
            $allowedIps = explode(',', str_replace(' ', '', config('app.MPESA_ALLOWED_IPS', '196.201.214.200,196.201.214.206,196.201.213.114,196.201.214.207,196.201.214.208,196.201.213.44,196.201.212.127,196.201.212.138,196.201.212.129,196.201.212.136,196.201.212.74,196.201.212.69')));
            $ip = $request->ip();
            if (!in_array($ip, $allowedIps)) {
                logger("M-Pesa Callback from unauthorized IP: $ip");
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $payload = $request->all();

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

        $perPage = $request->get('per_page', 20);
        $payments = $query->orderBy('created_at', 'desc')->paginate($perPage);

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

    public function submitManualReceipt(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'receipt_number' => 'required|string|min:5|max:20',
        ]);

        $order = Order::where('slug', $request->order_id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->payment_status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'This order is already paid.'
            ], 400);
        }

        // Update status to 'pending' and set the reference so the Admin knows it awaits verification
        $receiptNumber = strtoupper(trim($request->receipt_number));
        $order->update([
            'payment_status' => 'pending',
            'payment_reference' => $receiptNumber
        ]);

        // 1. Dispatch SMS Alert to Admin Numbers
        try {
            $client = new Client();
            $apiKey = config('app.TIARA_KEY');
            $smsMessage = "MANUAL PAYMENT SUBMITTED | Order #{$order->slug} | Receipt: {$receiptNumber} | Amount: KES " . number_format($order->total) . ". Please verify on admin panel.";
            $adminRecipients = ['254791210705', '254718156421', '254113748906', '254721815617'];

            foreach ($adminRecipients as $to) {
                try {
                    $client->post('https://api2.tiaraconnect.io/api/messaging/sendsms', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $apiKey,
                        ],
                        'json' => [
                            'to' => $to,
                            'from' => 'TIARACONECT',
                            'message' => $smsMessage,
                        ],
                    ]);
                } catch (\Exception $e) {
                    logger("Admin manual receipt SMS error | msisdn: $to | " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            logger('Manual receipt SMS dispatch failed: ' . $e->getMessage());
        }

        // 2. Dispatch Email Alert to Admin Team
        try {
            Mail::to([config('mail.from.address'), 'jennifer@ngwindsong.com'])->send(new NewOrderReceived($order));
        } catch (\Exception $e) {
            logger('Manual receipt admin email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Receipt submitted successfully. Awaiting verification.'
        ]);
    }

    /**
     * M-Pesa C2B Validation Callback
     * Called by Safaricom before a transaction is accepted.
     * Respond with ResultCode 0 to accept, or non-zero to reject.
     */
    public function mpesaValidation(Request $request)
    {
        Log::info('M-Pesa Validation Callback received', $request->all());

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * M-Pesa C2B Confirmation Callback
     * Called by Safaricom after a transaction has been successfully completed.
     * BillRefNumber maps to the order slug. Automatically marks the order as paid.
     */
    public function mpesaConfirmation(Request $request)
    {
        $payload = $request->all();

        // Map BillRefNumber → order slug
        $orderSlug       = $payload['BillRefNumber']    ?? null;
        $transactionId   = $payload['TransID']           ?? null;
        $transactionType = $payload['TransactionType']   ?? null;
        $transTime       = $payload['TransTime']         ?? null;
        $amount          = $payload['TransAmount']       ?? null;
        $shortCode       = $payload['BusinessShortCode'] ?? null;
        $orgBalance      = $payload['OrgAccountBalance'] ?? null;
        $msisdn          = $payload['MSISDN']            ?? null;
        $firstName       = $payload['FirstName']         ?? null;

        $order = $orderSlug ? Order::where('slug', $orderSlug)->first() : null;

        Log::info('M-Pesa Confirmation Callback received', [
            'TransactionType'    => $transactionType,
            'TransID'            => $transactionId,
            'TransTime'          => $transTime,
            'TransAmount'        => $amount,
            'BusinessShortCode'  => $shortCode,
            'BillRefNumber'      => $orderSlug,
            'OrgAccountBalance'  => $orgBalance,
            'MSISDN'             => $msisdn,
            'FirstName'          => $firstName,
            'order_found'        => $order ? true : false,
            'order_id'           => $order?->id,
            'order_status_before' => $order?->payment_status,
        ]);

        if ($order) {
            // Only update if not already marked as success (idempotent)
            if ($order->payment_status !== 'success') {
                $order->update([
                    'payment_status'    => 'success',
                    'payment_reference' => $transactionId,
                ]);

                OrderPaymentSuccessful::dispatch($order);

                Log::info("M-Pesa Confirmation | Order '{$orderSlug}' marked as PAID | TransID: {$transactionId} | Amount: {$amount}");
            } else {
                Log::info("M-Pesa Confirmation | Order '{$orderSlug}' already marked as paid. Skipping. | TransID: {$transactionId}");
            }
        } else {
            Log::warning("M-Pesa Confirmation | Order not found for BillRefNumber: '{$orderSlug}' | TransID: {$transactionId}");
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}