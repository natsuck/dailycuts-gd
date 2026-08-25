<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    /**
     * Redirect the user to the provider's authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback and authenticate the user.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => 'The login request expired or was cancelled. Please try again.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => "We couldn't retrieve an email address from your {$provider} account."]);
        }

        $email = strtolower(trim((string) $socialUser->getEmail()));
        $oauthId = $socialUser->getId();
        $emailVerified = $this->emailVerifiedByProvider($socialUser);

        // Prefer the provider identity; fall back to a verified email match only.
        $user = User::where('oauth_provider', $provider)
            ->where('oauth_id', $oauthId)
            ->first();

        if (! $user) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if (! $emailVerified) {
                    return redirect()
                        ->route('login')
                        ->withErrors(['social' => 'This email is already registered. Sign in with your existing password, or use a provider that verifies this email.']);
                }

                $user = $existing;
            }
        }

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getEmail(),
                'email' => $email,
                'password' => null,
            ]);

            $user->forceFill([
                'user_type' => 'user',
                'oauth_provider' => $provider,
                'oauth_id' => $oauthId,
                'email_verified_at' => $emailVerified ? now() : null,
            ])->save();
        } else {
            $user->forceFill([
                'oauth_provider' => $provider,
                'oauth_id' => $oauthId,
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function emailVerifiedByProvider($socialUser): bool
    {
        $raw = $socialUser->getRaw();

        if (isset($raw['email_verified'])) {
            return filter_var($raw['email_verified'], FILTER_VALIDATE_BOOL);
        }

        if (isset($raw['verified'])) {
            return filter_var($raw['verified'], FILTER_VALIDATE_BOOL);
        }

        return false;
    }

    private function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }
}
