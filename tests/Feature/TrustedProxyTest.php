<?php

use Illuminate\Support\Facades\Config;

it('resolves the real client IP through trusted Cloudflare proxies', function () {
    Config::set('trustedproxy.proxies', [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '172.64.0.0/13',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '131.0.72.0/22',
    ]);

    // Laravel sets the current request at the start of handle(); capture what
    // request()->ip() resolves to once trusted proxies are configured.
    $resolved = null;

    Route::post('/__proxy_ip_test', function () use (&$resolved) {
        $resolved = request()->ip();

        return response()->json(['ip' => $resolved]);
    });

    $this->withoutMiddleware([
        \App\Http\Middleware\ValidateAppUrlSignature::class,
    ])->post('/__proxy_ip_test', [], [
        'REMOTE_ADDR' => '162.158.108.137',
        'HTTP_X_FORWARDED_FOR' => '136.158.24.102, 162.158.108.137',
    ])->assertOk();

    expect($resolved)->toBe('136.158.24.102');
});
