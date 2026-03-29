<?php

namespace App\Listeners;

use App\Events\NewShipmentCreated;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SendShipmentNotifications
{
    public function handle(NewShipmentCreated $event): void
    {
        $shipment = $event->shipment;
        $shipment->load(['orders.orderDetail']);

        try {
            $client = new Client();
            $endpoint = 'https://api2.tiaraconnect.io/api/messaging/sendsms';
            $apiKey = config('app.TIARA_KEY');
            $from = 'TIARACONECT';
            $notifiedContacts = [];

            foreach ($shipment->orders as $order) {
                $phone = $order->orderDetail?->phone;

                if (!$phone || in_array($phone, $notifiedContacts, true)) {
                    continue;
                }

                $name = $order->orderDetail?->full_name ?: 'there';
                $orderReference = $order->slug ?: '#' . $order->id;
                $message = "Hi {$name}, your order {$orderReference} has been shipped and is now on its way. We will contact you if anything else is needed. Thank you for shopping with NG Windsong Kenya!";

                try {
                    $response = $client->post($endpoint, [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $apiKey,
                        ],
                        'json' => [
                            'to' => $phone,
                            'from' => $from,
                            'message' => $message,
                        ],
                    ]);

                    $notifiedContacts[] = $phone;
                    Log::info("Shipment SMS sent|shipment_id: {$shipment->id}|order_id: {$order->id}|msisdn: {$phone}|response: " . $response->getBody()->getContents());
                } catch (\Exception $e) {
                    Log::error("Shipment SMS failed|shipment_id: {$shipment->id}|order_id: {$order->id}|msisdn: {$phone}|error: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Shipment SMS setup failed|shipment_id: {$shipment->id}|error: " . $e->getMessage());
        }
    }
}
