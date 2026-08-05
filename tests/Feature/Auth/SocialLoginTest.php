<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;

test('social login redirect route sends the user to the provider', function () {
    Socialite::fake('google');

    $this->get('/auth/google/redirect')
        ->assertRedirect('https://socialite.fake/google/authorize');
});

test('a new user can authenticate via a social provider', function () {
    Socialite::fake('google', SocialUser::fake([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback');

    $this->assertAuthenticated();

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->oauth_provider)->toBe('google')
        ->and($user->oauth_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->user_type)->toBe('user')
        ->and($user->password)->toBeNull();
});

test('a new user from a provider that does not confirm the email must verify', function () {
    Socialite::fake('google', SocialUser::fake([
        'id' => 'google-456',
        'name' => 'Unverified',
        'email' => 'unverified@example.com',
    ]));

    $this->get('/auth/google/callback');

    $this->assertAuthenticated();

    $user = User::where('email', 'unverified@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->toBeNull();
});

test('an existing user with a verified email is logged in via a social provider', function () {
    $existing = User::factory()->create(['email' => 'jane@example.com']);

    Socialite::fake('facebook', SocialUser::fake([
        'id' => 'facebook-456',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified' => true,
    ]));

    $this->get('/auth/facebook/callback');

    $this->assertAuthenticatedAs($existing);
    expect(User::where('email', 'jane@example.com')->count())->toBe(1);
});

test('an existing account cannot be taken over by an unverified provider email', function () {
    $existing = User::factory()->create(['email' => 'jane@example.com']);

    Socialite::fake('facebook', SocialUser::fake([
        'id' => 'attacker-789',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $this->get('/auth/facebook/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('social');

    $this->assertGuest();
    expect(User::where('email', 'jane@example.com')->count())->toBe(1);
    expect($existing->fresh()->oauth_provider)->toBeNull();
});

test('a returning social user is matched by provider identity', function () {
    $existing = User::factory()->create([
        'email' => 'jane@example.com',
        'oauth_provider' => 'google',
        'oauth_id' => 'google-123',
        'email_verified_at' => now(),
    ]);

    Socialite::fake('google', SocialUser::fake([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback');

    $this->assertAuthenticatedAs($existing);
    expect(User::where('email', 'jane@example.com')->count())->toBe(1);
});

test('a social callback without an email is rejected', function () {
    Socialite::fake('google', SocialUser::fake([
        'id' => 'google-123',
        'name' => 'No Email',
        'email' => null,
    ]));

    $this->get('/auth/google/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('social');

    $this->assertGuest();
});

test('an unsupported social provider returns 404', function () {
    $this->get('/auth/twitter/callback')->assertNotFound();
});
