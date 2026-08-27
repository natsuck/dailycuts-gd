<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Content Security Policy -- shipped in Report-Only mode so it enforces
        // nothing while it surfaces any policy violations for tightening.
        // Align the final enforcing policy with the browser reports before
        // switching this to Content-Security-Policy.
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https://www.google.com",
            "frame-src 'self' https://www.google.com",
            "connect-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', $csp));

        return $response;
    }
}
