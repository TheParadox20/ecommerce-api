<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $status)
    {
        $this->user = $user;
        $this->status = $status;
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
        $role = ucfirst($this->user->role);
        $isApproved = $this->status === 'active';
        
        $message = (new MailMessage)
                    ->subject("Your {$role} Application Status: " . ($isApproved ? 'Approved' : 'Rejected'))
                    ->greeting("Hello {$this->user->name},");

        if ($isApproved) {
            $voucherCode = $this->user->vouchers()->first()?->code;
            
            $message->line("Congratulations! Your application to join our network as a {$role} has been approved.")
                    ->line("You can now log in to your dashboard to access exclusive features.");
            
            if ($voucherCode) {
                $message->line("Your unique referral code is: **{$voucherCode}**")
                        ->line("Share this code with your audience to earn commissions on every purchase they make!");
            }

            $message->action('Go to Dashboard', url('/login'));
        } else {
            $message->line("Thank you for your interest in joining as a {$role}.")
                    ->line("Unfortunately, we are unable to approve your application at this time.")
                    ->line("If you have any questions, please feel free to contact our support team.");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'role' => $this->user->role,
            'message' => "Your application status has been updated to {$this->status}.",
        ];
    }
}
