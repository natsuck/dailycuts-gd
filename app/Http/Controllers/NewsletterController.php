<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => $email],
            [
                'is_active' => true,
                'subscribed_at' => now(),
            ]
        );

        Mail::to($email)->queue(new NewsletterWelcomeMail($subscriber));

        return redirect()->back()->with(
            'newsletterMessage',
            'Thanks for subscribing! Check your inbox for a confirmation.'
        );
    }

    public function unsubscribe(Request $request, NewsletterSubscriber $subscriber)
    {
        $subscriber->update(['is_active' => false]);

        return redirect()->back()->with(
            'newsletterMessage',
            'You have been unsubscribed from our newsletter.'
        );
    }
}
