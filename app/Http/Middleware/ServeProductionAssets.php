<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ServeProductionAssets
{
    /**
     * Handle an incoming request.
     *
     * When the site is reached through the public APP_URL host (e.g. the ngrok
     * tunnel), the Vite dev server URL written to public/hot (like
     * http://[::1]:5173) is unreachable from other devices, leaving the page
     * blank. For those requests, ignore the hot file and serve the built
     * assets from public/build instead.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isPublicRequest($request) && Vite::isRunningHot()) {
            Vite::useHotFile(storage_path('framework/hot-disabled-for-external-access'));
        }

        return $next($request);
    }

    /**
     * Determine if the request arrived through the configured public URL host.
     */
    protected function isPublicRequest(Request $request): bool
    {
        $publicHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($publicHost) && $publicHost !== '' && $request->getHost() === $publicHost;
    }
}
