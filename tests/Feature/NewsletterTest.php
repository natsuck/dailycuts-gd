<?php

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('guest can subscribe to the newsletter and receives a welcome email', function () {
    Mail::fake();
    Mail::assertNothingQueued();

    $this->post(route('newsletter.subscribe'), ['email' => 'Jane@Example.com'])
        ->assertRedirect()
        ->assertSessionHas('newsletterMessage');

    $subscriber = NewsletterSubscriber::where('email', 'jane@example.com')->first();

    expect($subscriber)->not->toBeNull();
    expect($subscriber->is_active)->toBeTrue();

    Mail::assertQueued(NewsletterWelcomeMail::class, function (NewsletterWelcomeMail $mail) {
        return $mail->hasTo('jane@example.com');
    });
});

test('newsletter email is required and valid', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => ''])
        ->assertSessionHasErrors('email');

    $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    expect(NewsletterSubscriber::count())->toBe(0);
});

test('resubscribing activates an existing subscriber without duplicating', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'jane@example.com',
        'is_active' => false,
    ]);

    $this->post(route('newsletter.subscribe'), ['email' => 'jane@example.com'])
        ->assertRedirect();

    expect(NewsletterSubscriber::count())->toBe(1);
    expect($subscriber->fresh()->is_active)->toBeTrue();
});

test('signed unsubscribe link deactivates the subscriber', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['is_active' => true]);

    $url = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);

    $this->get($url)->assertRedirect()->assertSessionHas('newsletterMessage');

    expect($subscriber->fresh()->is_active)->toBeFalse();
});

test('unsigned unsubscribe url is rejected', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['is_active' => true]);

    $this->get(route('newsletter.unsubscribe', ['subscriber' => $subscriber->id]))
        ->assertForbidden();

    expect($subscriber->fresh()->is_active)->toBeTrue();
});
