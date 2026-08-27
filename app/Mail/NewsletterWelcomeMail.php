<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to The Daily Cuts Newsletter',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter_welcome',
            with: [
                'email' => $this->subscriber->email,
                'unsubscribeUrl' => \Illuminate\Support\Facades\URL::signedRoute(
                    'newsletter.unsubscribe',
                    ['subscriber' => $this->subscriber->id]
                ),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
