<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;

class NewOrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {
        $this->order->load(['sales.product', 'sales.productVariation', 'orderDetail']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Successful >order number-#' . $this->order->slug . '- Amount -KES' . number_format($this->order->total),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-order',
        );
    }

    public function attachments(): array
    {
        // Generate PDF on the fly
        $pdf = Pdf::loadView('pdfs.invoice', ['order' => $this->order->load(['sales.product', 'sales.productVariation', 'orderDetail'])]);
        
        return [
            Attachment::fromData(fn () => $pdf->output(), 'Invoice-'.$this->order->slug.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
