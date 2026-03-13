<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Message $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Message from ' . $this->contactMessage->first_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-received',
        );
    }
}
