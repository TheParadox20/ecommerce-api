<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order #' . substr($this->order->slug, 0, 8) . ' - KES ' . number_format($this->order->total),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-order',
        );
    }
}
