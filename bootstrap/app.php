<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,127.0.0.0/8,::1')),
        )));

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

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
