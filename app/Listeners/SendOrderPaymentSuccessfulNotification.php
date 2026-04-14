<?php

namespace App\Listeners;

use App\Events\OrderPaymentSuccessful;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderNotification;

class SendOrderPaymentSuccessfulNotification
{
    public function handle(OrderPaymentSuccessful $event): void
    {
        $order = $event->order;
        $order->load(['orderDetail', 'sales.product', 'sales.productVariation']);

        // 1. Send Email with PDF Invoice
        try {
            $recipients = ['sales@ngwindsongk.com', 'pauline@ngwindsongk.com'];
            if ($order->orderDetail && $order->orderDetail->email) {
                $recipients[] = $order->orderDetail->email;
            }

            Mail::to($recipients)->send(new OrderNotification($order));
        }
        catch (\Exception $e) {
            Log::error('Order email failed: ' . $e->getMessage());
        }

        // 2. Send SMS notification to Payer
        if ($order->orderDetail && $order->orderDetail->phone) {
            try {
                $client = new Client();
                $apiKey = config('app.TIARA_KEY');
                
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