<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class MessageController extends Controller
{
    public function contact(Request $request){
        return response()->json([
            'message'=>"We'll be in touch"
        ]);
    }
    function sendSMS(Request $request){
        $client = new Client();
        $endpoint = 'https://api2.tiaraconnect.io/api/messaging/sendsms'; // set the Endpoint provided.
        $apiKey = config('app.TIARA_KEY');
        $from = 'TIARACONECT';
        $message = 'New Transaction made on your account';
        $to = '254721815617'; // set a valid number using format '2547********' or '2541********'

        $requestData = [  
            'to' => $to,
            'from' => $from,
            'message' => $message,
        ];
    
        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => $requestData
            ]);
    
            $responseBody = $response->getBody()->getContents();
            Log::info("request|msisdn: $to|response: $responseBody | url: $endpoint");
        } catch (\Exception $e) {
            Log::error("{$apiKey} :: request|msisdn: $to|error: " . $e->getMessage() . " | url: $endpoint");
        }
    }
}
