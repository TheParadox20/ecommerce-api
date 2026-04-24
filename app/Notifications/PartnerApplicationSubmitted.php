<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class PartnerApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $applicant;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $applicant)
    {
        $this->applicant = $applicant;
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
        $role = ucfirst($this->applicant->role);
        
        return (new MailMessage)
                    ->subject("New {$role} Application: {$this->applicant->name}")
                    ->greeting("Hello Admin,")
                    ->line("A new user has shown interest in joining as a {$role}.")
                    ->line("Name: {$this->applicant->name}")
                    ->line("Email: {$this->applicant->email}")
                    ->line("Phone: {$this->applicant->phone}")
                    ->action('Review Application', url('/admin/applications'))
                    ->line('Please review their profile in the administration panel.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'applicant_id' => $this->applicant->id,
            'applicant_name' => $this->applicant->name,
            'role' => $this->applicant->role,
            'message' => "New {$this->applicant->role} application received from {$this->applicant->name}.",
        ];
    }
}
