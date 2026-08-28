<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Deployment-critical for the Maya webhook IP allowlist: $request->ip()
        // must resolve to Maya's IP, so every reverse proxy in front of the app
        // has to be listed here. Defaults cover the documented nginx + PHP-FPM
        // same-box setup; set TRUSTED_PROXIES when deploying behind an external
        // load balancer / CDN (comma-separated IPs or CIDR ranges).
        //
        // The list is loaded by Laravel's default TrustProxies middleware from
        // config('trustedproxy.proxies') (config/trustedproxy.php, sourced from
        // the TRUSTED_PROXIES env var). It is read at handle-time so it survives
        // `php artisan config:cache` -- a bare env()/config() call in this
        // bootstrap closure returns null/throws once config is cached.

        $middleware->alias([
            'admin' => App\Http\Middleware\AdminMiddleware::class,
            'customer' => App\Http\Middleware\CustomerMiddleware::class,
            'signed.appurl' => App\Http\Middleware\ValidateAppUrlSignature::class,
        ]);

        $middleware->web(append: [
            App\Http\Middleware\SecurityHeaders::class,
            App\Http\Middleware\ServeProductionAssets::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
