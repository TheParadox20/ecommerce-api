<?php

namespace App\Listeners;

use App\Events\NewOrderPlaced;
use App\Mail\NewOrderReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Client;

class SendNewOrderNotifications
{
    public function handle(NewOrderPlaced $event): void
    {
        $order = $event->order;
        $order->load(['orderDetail', 'sales.product', 'sales.productVariation']);

        // Send email to admin
        try {
            Mail::to(config('mail.from.address'))->send(new NewOrderReceived($order));
        } catch (\Exception $e) {
            Log::error('Order email failed: ' . $e->getMessage());
        }

        // Send SMS notification
        try {
            $client = new Client();
            $endpoint = 'https://api2.tiaraconnect.io/api/messaging/sendsms';
            $apiKey = config('app.TIARA_KEY');
            $from = 'TIARACONECT';
            $message = $order->slug . ' - New order placed. Total: ' . $order->total . ' KES. Please check the admin panel for details.';
            $to = '254791210705, 254701259936';

            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'to' => $to,
                    'from' => $from,
                    'message' => $message,
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            Log::info("request|msisdn: $to|response: $responseBody | url: $endpoint");
        } catch (\Exception $e) {
            Log::error("SMS failed|msisdn: $to|error: " . $e->getMessage());
        }
    }
}
