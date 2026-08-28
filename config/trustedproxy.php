<?php

return [
    // Reverse proxies in front of the app whose X-Forwarded-* headers Laravel
    // should trust. Cloudflare publishes these ranges; add/remove as their
    // documented list changes. Comma-separated IPs or CIDR ranges.
    //
    // Read via config('trustedproxy.proxies') so it survives `config:cache`
    // (env() is only safe inside config files, not bootstrap code).
    'proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,127.0.0.0/8,::1')),
    ))),
];
