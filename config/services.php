<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bridge (self-hosted Node.js + Baileys)
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'url' => env('WHATSAPP_BRIDGE_URL'),
        'key' => env('WHATSAPP_BRIDGE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy alert channels
    |--------------------------------------------------------------------------
    |
    | When true, AlertNotifier will also send the legacy email + WhatsApp
    | duplicates alongside the per-event recipient matrix in
    | App\Services\InternalNotifier. Read via config() so it survives
    | config:cache (env() returns null in cached-config environments).
    |
    */
    'legacy_alert_channels' => env('LEGACY_ALERT_CHANNELS', false),
];
