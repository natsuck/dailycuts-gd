<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\InvalidSignatureException;

class ValidateAppUrlSignature
{
    /**
     * Handle an incoming request.
     *
     * Validates the signed request against the host it was served from, or
     * against the configured APP_URL when the two hosts differ (e.g. a link
     * generated for the ngrok URL is clicked from a localhost session).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (URL::hasValidSignature($request) || $this->hasValidSignatureAgainstAppUrl($request)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    /**
     * Determine if the request has a valid signature computed against APP_URL.
     */
    protected function hasValidSignatureAgainstAppUrl(Request $request): bool
    {
        $url = rtrim((string) config('app.url'), '/').$request->getPathInfo();

        $queryString = (new Collection(explode('&', (string) $request->server->get('QUERY_STRING'))))
            ->reject(function ($parameter) {
                return Str::before($parameter, '=') === 'signature';
            })
            ->join('&');

        $original = rtrim($url.'?'.$queryString, '?');

        foreach (Arr::wrap(config('app.key')) as $key) {
            if (hash_equals(hash_hmac('sha256', $original, (string) $key), (string) $request->query('signature', ''))) {
                return $this->signatureHasNotExpired($request);
            }
        }

        return false;
    }

    /**
     * Determine if the signed request has not expired.
     */
    protected function signatureHasNotExpired(Request $request): bool
    {
        $expires = $request->query('expires');

        return ! ($expires && now()->getTimestamp() > (int) $expires);
    }
}
