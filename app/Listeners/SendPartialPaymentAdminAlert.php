<?php

namespace App\Listeners;

use App\Events\OrderPartialPaymentReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Events\Attributes\ListensTo;
use GuzzleHttp\Client;

class SendPartialPaymentAdminAlert
{
    /**
     * Handle the OrderPartialPaymentReceived event.
     * Sends admin-only SMS + email alerts for underpayment.
     * The buyer is intentionally NOT notified to avoid false "order confirmed" messages.
     */
    #[ListensTo(OrderPartialPaymentReceived::class)]
    public function handle(OrderPartialPaymentReceived $event): void
    {
        $order       = $event->order;
        $amountPaid  = $event->amountPaid;
        $expected    = $event->amountExpected;
        $shortfall   = $expected - $amountPaid;
        $transId     = $event->transactionId;

        $order->load(['orderDetail', 'sales.product']);

        // 1. Admin SMS alert to all 4 numbers
        try {
            $client = new Client();
            $apiKey = config('app.TIARA_KEY');

            $message = implode(' | ', [
                "⚠️ UNDERPAYMENT",
                "Order: {$order->slug}",
                "Paid: KES " . number_format($amountPaid),
                "Owed: KES " . number_format($expected),
                "Short: KES " . number_format($shortfall),
                "Ref: {$transId}",
                "Action needed in admin panel.",
            ]);

            $adminRecipients = ['254791210705', '254718156421', '254113748906', '254721815617'];

            foreach ($adminRecipients as $to) {
                try {
                    $client->post('https://api2.tiaraconnect.io/api/messaging/sendsms', [
                        'headers' => [
                            'Content-Type'  => 'application/json',
                            'Authorization' => 'Bearer ' . $apiKey,
                        ],
                        'json' => [
                            'to'      => $to,
                            'from'    => 'TIARACONECT',
                            'message' => $message,
                        ],
                    ]);
                    Log::info("Partial payment alert SMS sent | to: {$to} | order: {$order->slug}");
                } catch (\Exception $e) {
                    Log::error("Partial payment alert SMS failed | to: {$to} | " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Partial payment SMS setup failed: " . $e->getMessage());
        }

        // 2. Admin email alert
        try {
            $subject = "⚠️ Underpayment Alert — Order {$order->slug}";
            $body    = "An underpayment was detected for Order <strong>{$order->slug}</strong>.<br><br>"
                     . "<table style='border-collapse:collapse;font-family:monospace'>"
                     . "<tr><td style='padding:4px 12px'>Amount Paid:</td><td><strong>KES " . number_format($amountPaid, 2) . "</strong></td></tr>"
                     . "<tr><td style='padding:4px 12px'>Amount Expected:</td><td><strong>KES " . number_format($expected, 2) . "</strong></td></tr>"
                     . "<tr><td style='padding:4px 12px'>Shortfall:</td><td style='color:red'><strong>KES " . number_format($shortfall, 2) . "</strong></td></tr>"
                     . "<tr><td style='padding:4px 12px'>M-Pesa Ref:</td><td>{$transId}</td></tr>"
                     . "<tr><td style='padding:4px 12px'>Order Status:</td><td>pending_verification</td></tr>"
                     . "</table><br>"
                     . "Please review and either approve or contact the customer for the outstanding balance.";

            Mail::html($body, function ($mail) use ($subject) {
                $mail->to([config('mail.from.address'), 'jennifer@ngwindsong.com'])
                     ->subject($subject);
            });

            Log::info("Partial payment admin email sent | order: {$order->slug}");
        } catch (\Exception $e) {
            Log::error("Partial payment admin email failed: " . $e->getMessage());
        }
    }
}
