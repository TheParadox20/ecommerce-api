<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class PaymentController extends Controller
{
    protected $shortcode;
    protected $passkey;
    
    public function __construct()
    {
        $this->shortcode = config('app.MPESA_SHORTCODE');
        $this->passkey = config('app.MPESA_PASSKEY');
    }
    public function getToken(){
        $credentials = [
            "ConsumerKey" => config('app.MPESA_CONSUMER_KEY'),
            "ConsumerSecret" => config('app.MPESA_CONSUMER_SECRET'),
        ];

        $client = new Client();

        try {
            logger('Getting token');
            //log credentials
            logger($credentials['ConsumerKey']);
            logger($credentials['ConsumerSecret']);
            $response = $client->request('GET', 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', [
                'auth' => [$credentials['ConsumerKey'], $credentials['ConsumerSecret']]
            ]);

            return json_decode($response->getBody())->access_token;
        } catch (\Exception $e) {
            return response()->json(([
                'error'=>$e->getMessage(),
            ]));
        }
    }
    public function mpesaSTK(Request $request)
    {
        $timestamp = date('YmdHis');
        logger($this->passkey);
        logger($this->shortcode);
        logger($timestamp);
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        logger($password);
        $contact = '254' . preg_replace('/\D/', '', ltrim($request->phone, '+2540'));

        try {
            $client = new Client();
            $token = $this->getToken();
            logger($token);
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
                    "Amount" => $request->amount,
                    "PartyA" => $contact,
                    "PartyB" => 960393,
                    "PhoneNumber" => $contact,
                    "CallBackURL" => "https://api.eik.co.ke/api/mpesa/mpesaCallback",
                    "AccountReference" => "Item Purchase",
                    "TransactionDesc" => "Payment"
                ]
            ]);
            logger($response->getBody());
            $response = json_decode($response->getBody());
            if ($response->ResponseCode=="0"){
                logger('Payment prompt sent to ' . $contact);
            }
            return $response;
        } catch (\Exception $e) {
            return response()->json(([
                'error'=>$e->getMessage(),
            ]));
        }
    }
    public function mpesaCallback(Request $request)
    {
        $payload = $request->all();
        logger('M-Pesa Callback received:', $payload);

        try {
            $body = $payload['Body']['stkCallback'] ?? null;

            if (!$body) {
                return response()->json(['status' => 'error', 'message' => 'Invalid callback payload'], 400);
            }

            $resultCode    = $body['ResultCode'];
            $resultDesc    = $body['ResultDesc'];
            $checkoutRequestId = $body['CheckoutRequestID'] ?? null;

            if ($resultCode == 0) {
                // Payment successful
                $items = collect($body['CallbackMetadata']['Item']);
                $mpesaCode = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
                $amount    = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
                $phone     = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;

                logger("Payment SUCCESS | Receipt: $mpesaCode | Amount: $amount | Phone: $phone");

                // TODO: Match by CheckoutRequestID and update order payment_status to 'paid'
                // Example:
                // Order::where('mpesa_checkout_id', $checkoutRequestId)
                //     ->update(['payment_status' => 'paid', 'mpesa_code' => $mpesaCode]);

            } else {
                logger("Payment FAILED | Code: $resultCode | Desc: $resultDesc");

                // TODO: Update order payment_status to 'failed'
            }

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        } catch (\Exception $e) {
            logger('Callback error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }
    }
}

