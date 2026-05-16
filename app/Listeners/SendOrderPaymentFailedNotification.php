<?php

namespace App\Listeners;

use App\Events\OrderPaymentFailed;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class SendOrderPaymentFailedNotification
{
    public function handle(OrderPaymentFailed $event): void
    {
        $order = $event->order;
        $order->load(['orderDetail']);

        /*
        // Send SMS confirmation to buyer
        if ($order->orderDetail && $order->orderDetail->phone) {
            try {
                $client = new Client();
                $frontendUrl = rtrim(config('app.FRONTEND_URL'), '/');
                $buyerMessage = "Payment for order {$order->slug} failed. Open {$frontendUrl}/orders?order={$order->slug} to retry payment";

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

                Log::info("Payment Failed Buyer SMS|msisdn: {$order->orderDetail->phone}|response: " . $response->getBody()->getContents());
            }
            catch (\Exception $e) {
                Log::error("Payment Failed Buyer SMS failed|msisdn: {$order->orderDetail->phone}|error: " . $e->getMessage());
            }
        }
        */

        Log::info("Payment failed for order {$order->slug}. SMS notification suppressed (commented out).");
    }
}