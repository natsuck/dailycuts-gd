<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('index', absolute: false));
        }

        $user = $request->user();
        $code = $user->generateVerificationCode();

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));

        return back()->with('status', 'verification-code-sent');
    }
}
