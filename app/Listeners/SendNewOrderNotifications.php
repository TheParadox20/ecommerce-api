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
            $recipients = ['254791210705', '254718156421', '254113748906'];

            foreach ($recipients as $to) {
                try {
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
        } catch (\Exception $e) {
            Log::error("SMS setup failed: " . $e->getMessage());
        }

        // Send SMS confirmation to buyer
        if ($order->orderDetail && $order->orderDetail->phone) {
            try {
                $client = $client ?? new Client();
                $buyerMessage = 'Hi ' . ($order->orderDetail->full_name ?: 'there') . ', your order has been received! Order total: KES ' . number_format($order->total) . '. We will notify you once it is on its way. Thank you for shopping with NG Windsong Kenya!';

                $response = $client->post('https://api2.tiaraconnect.io/api/messaging/sendsms', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . config('app.TIARA_KEY'),
                    ],
                    'json' => [
                        'to' => $order->orderDetail->phone,
                        'from' => 'TIARACONECT',
                        'message' => $buyerMessage,
                    ],
                ]);

                Log::info("Buyer SMS sent|msisdn: {$order->orderDetail->phone}|response: " . $response->getBody()->getContents());
            } catch (\Exception $e) {
                Log::error("Buyer SMS failed|msisdn: {$order->orderDetail->phone}|error: " . $e->getMessage());
            }
        }
    }
}
