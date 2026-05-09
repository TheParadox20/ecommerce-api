<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderNotification;
use App\Mail\NewOrderReceived;

class SendOrderPaymentSuccessfulNotification
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order;
        $order->load(['orderDetail', 'sales.product', 'sales.productVariation']);

        $client = new Client();
        $apiKey = config('app.TIARA_KEY');

        // 1. Send Email to Admin and Buyer
        try {
            // Admin receives NewOrderReceived (Premium template + Invoice)
            Mail::to(config('mail.from.address'))->send(new NewOrderReceived($order));

            // Buyer receives OrderNotification (Simple template + Invoice)
            if ($order->orderDetail && $order->orderDetail->email) {
                Mail::to($order->orderDetail->email)->send(new OrderNotification($order));
            }
        }
        catch (\Exception $e) {
            Log::error('Order payment email failed: ' . $e->getMessage());
        }

        // 2. Send Admin SMS notification (payment confirmed)
        try {
            $message = $order->slug . ' - Payment CONFIRMED. Total: KES ' . number_format($order->total) . '. Ref: ' . ($order->payment_reference ?? 'N/A') . '. Check admin panel.';
            $adminRecipients = ['254791210705', '254718156421', '254113748906'];

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
                            'message' => $message,
                        ],
                    ]);
                    Log::info("Admin payment SMS sent|msisdn: $to|order: {$order->slug}");
                } catch (\Exception $e) {
                    Log::error("Admin payment SMS failed|msisdn: $to|error: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Admin payment SMS setup failed: " . $e->getMessage());
        }

        // 3. Send SMS confirmation to Buyer
        if ($order->orderDetail && $order->orderDetail->phone) {
            try {
                // Format items for SMS
                $itemsSummary = $order->sales->map(function($sale) {
                    $size = $sale->productVariation->name ?? 'Std';
                    return "{$sale->product->name} x{$sale->quantity} ({$size})";
                })->implode(', ');

                $shippingDate = \Carbon\Carbon::parse($order->expected_shipping_date)->format('M d');
                $buyerMessage = "Order #{$order->slug} Confirmed! Items: {$itemsSummary}. Total: KES " . number_format($order->total) . ". Delivery: {$order->orderDetail->address}. Expected Shipment: {$shippingDate}. Thank you for shopping with NG Windsong Kenya!";

                $to = '254' . preg_replace('/\D/', '', ltrim($order->orderDetail->phone, '+2540'));

                $client->post('https://api2.tiaraconnect.io/api/messaging/sendsms', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey,
                    ],
                    'json' => [
                        'to' => $to,
                        'from' => 'TIARACONECT',
                        'message' => $buyerMessage,
                    ],
                ]);

                Log::info("Payment Success Buyer SMS|msisdn: $to|sent successfully");
            }
            catch (\Exception $e) {
                Log::error("Payment Success Buyer SMS failed|error: " . $e->getMessage());
            }
        }
    }
}