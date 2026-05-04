<?php

namespace App\Listeners;

use App\Events\NewOrderPlaced;
use Illuminate\Support\Facades\Log;

class SendNewOrderNotifications
{
    /**
     * Notifications are intentionally suppressed here.
     * All order notifications (admin email, admin SMS, buyer SMS) are sent
     * only after successful M-Pesa payment via SendOrderPaymentSuccessfulNotification.
     */
    public function handle(NewOrderPlaced $event): void
    {
        Log::info("Order created: {$event->order->slug}. Awaiting payment confirmation before sending notifications.");
    }
}
