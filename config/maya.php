<?php

return [
    'public_key' => env('MAYA_PUBLIC_KEY'),
    // Only authenticates Create Webhook and similar management calls; the
    // Checkout v1 Create Checkout endpoint authenticates with the public key
    // only, so this is not used in the order flow.
    'secret_key' => env('MAYA_SECRET_KEY'),

    // Sandbox or production. Sandbox: https://pg-sandbox.paymaya.com
    // Production: https://pg.paymaya.com
    'mode' => env('MAYA_MODE', 'sandbox'),

    // Create Checkout is retried on transient failures (HTTP 429 / 5xx /
    // network errors). Maya's gateway can return 504 (error codes PY0015,
    // PY0016, PY0060) during incidents; each retry uses a fresh request
    // reference number because Maya rejects reused ones.
    'checkout_retries' => (int) env('MAYA_CHECKOUT_RETRIES', 3),
    'checkout_retry_delay_ms' => (int) env('MAYA_CHECKOUT_RETRY_DELAY_MS', 500),

    // NOTE: the Maya API Environments table lists the production API domain as
    // https://pg.maya.ph, but the Create Checkout reference and this config use
    // https://pg.paymaya.com. Set MAYA_BASE_URL if the verified live host differs.
    'base_url' => env('MAYA_BASE_URL', env('MAYA_MODE', 'sandbox') === 'production'
        ? 'https://pg.paymaya.com'
        : 'https://pg-sandbox.paymaya.com'),

    // Maya Checkout webhooks are not signed. Requests are only accepted from
    // these IPs. See https://developers.maya.ph/reference/configuring-your-webhook-for-maya-checkout
    // Sandbox: 13.229.160.234, 3.1.199.75 -- Production: 18.138.50.235, 3.1.207.200
    'webhook_ips' => env('MAYA_WEBHOOK_IPS')
        ? array_map('trim', explode(',', env('MAYA_WEBHOOK_IPS')))
        : (env('MAYA_MODE', 'sandbox') === 'production'
            ? ['18.138.50.235', '3.1.207.200']
            : ['13.229.160.234', '3.1.199.75']),
];
