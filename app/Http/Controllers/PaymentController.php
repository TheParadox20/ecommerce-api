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
                    "TransactionType" => "CustomerPayBillOnline",
                    "Amount" => $request->amount,
                    "PartyA" => $contact,
                    "PartyB" => $this->shortcode,
                    "PhoneNumber" => $contact,
                    "CallBackURL" => "https://api.ngwindsongk.com/api/mpesa/mpesaCallback",
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
}
