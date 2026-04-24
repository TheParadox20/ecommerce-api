<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Commission;

class CommissionEarned extends Notification implements ShouldQueue
{
    use Queueable;

    protected $commission;

    /**
     * Create a new notification instance.
     */
    public function __construct(Commission $commission)
    {
        $this->commission = $commission;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->commission->amount, 2);
        $orderId = $this->commission->order_id;
        
        return (new MailMessage)
                    ->subject("New Commission Earned! Order #{$orderId}")
                    ->greeting("Hello,")
                    ->line("Great news! You have earned a new commission of KES {$amount} from a sale attributed to your voucher.")
                    ->line("Order ID: #{$orderId}")
                    ->line("Status: Pending (Earnings will be finalized once the order is completed)")
                    ->action('View My Stats', url('/influencer/dashboard'))
                    ->line('Keep up the great work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'commission_id' => $this->commission->id,
            'order_id' => $this->commission->order_id,
            'amount' => $this->commission->amount,
            'message' => "You earned a commission of KES {$this->commission->amount} from Order #{$this->commission->order_id}.",
        ];
    }
}
