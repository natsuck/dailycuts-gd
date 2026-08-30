<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lalamove' => [
        'key' => env('LALAMOVE_API_KEY'),
        'secret' => env('LALAMOVE_API_SECRET'),
        'market' => env('LALAMOVE_MARKET', 'PH'),
        'sandbox' => (bool) env('LALAMOVE_SANDBOX', true),
        'webhook_url' => env('LALAMOVE_WEBHOOK_URL'),
        'webhook_permissive' => (bool) env('LALAMOVE_WEBHOOK_PERMISSIVE', false),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'reseller' => [
        'email' => env('RESELLER_INQUIRY_EMAIL'),
    ],

    'geocoder' => [
        'providers' => env('GEOCODER_PROVIDERS', 'google,photon,nominatim'),
        'photon_url' => env('PHOTON_GEOCODING_URL', 'https://photon.komoot.io/api'),
        'nominatim_url' => env('NOMINATIM_GEOCODING_URL', 'https://nominatim.openstreetmap.org/search'),
        'nominatim_ua' => env('NOMINATIM_USER_AGENT', 'TheDailyCutsByGD/1.0'),
    ],

];
