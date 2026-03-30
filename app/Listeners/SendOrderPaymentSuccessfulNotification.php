<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPaymentSuccessfulMail;

class SendOrderPaymentSuccessfulNotification
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order;
        $order->load(['orderDetail', 'sales.product', 'sales.productVariation']);

        // Send email to admin
        try {
            Mail::to(config('mail.from.address'))->send(new OrderPaymentSuccessfulMail($order));
        }
        catch (\Exception $e) {
            Log::error('Order email failed: ' . $e->getMessage());
        }

        // Send SMS notification to admins
        try {
            $client = new Client();
            $endpoint = 'https://api2.tiaraconnect.io/api/messaging/sendsms';
            $apiKey = config('app.TIARA_KEY');
            $from = 'TIARACONECT';
            $message = 'Order ' . $order->slug . ' - Payment successful. Total: ' . $order->total . ' KES. Please process the order.';
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
                    Log::info("Payment Success Admin SMS|msisdn: $to|response: $responseBody | url: $endpoint");
                }
                catch (\Exception $e) {
                    Log::error("Payment Success Admin SMS failed|msisdn: $to|error: " . $e->getMessage());
                }
            }
        }
        catch (\Exception $e) {
            Log::error("SMS setup failed: " . $e->getMessage());
        }

        // Send SMS confirmation to buyer
        if ($order->orderDetail && $order->orderDetail->phone) {
            try {
                $client = $client ?? new Client();
                $buyerMessage = 'Hi ' . ($order->orderDetail->full_name ?: 'there') . ', your payment of KES ' . number_format($order->total) . ' for order ' . $order->slug . ' has been received successfully! Thank you for shopping with NG Windsong Kenya!';

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

                Log::info("Payment Success Buyer SMS|msisdn: {$order->orderDetail->phone}|response: " . $response->getBody()->getContents());
            }
            catch (\Exception $e) {
                Log::error("Payment Success Buyer SMS failed|msisdn: {$order->orderDetail->phone}|error: " . $e->getMessage());
            }
        }
    }
}