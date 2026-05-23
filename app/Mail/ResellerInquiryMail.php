<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResellerInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Reseller Inquiry from '.$this->inquiry['name'],
            replyTo: [
                $this->inquiry['email'],
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller_inquiry',
            with: [
                'inquiry' => $this->inquiry,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
