<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order; 

    public function __construct($order)
    {
        $this->order = $order; 
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order Has Been Shipped',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_shipped',
            with: [
                'order' => $this->order, 
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}