<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messaging Transport Driver
    |--------------------------------------------------------------------------
    |
    | The transport used to move messages between services. "qstash" queues async
    | delivery via Upstash; "direct" sends signed HTTP synchronously (handy for
    | local/dev). Kafka / Redis pub-sub can be added later as additional drivers
    | without touching call sites.
    |
    | Supported: "qstash", "direct"
    |
    */
    'driver' => env('MESSAGING_DRIVER', 'qstash'),

    /*
    |--------------------------------------------------------------------------
    | Shared Signing Secret
    |--------------------------------------------------------------------------
    |
    | HMAC secret shared by both services. Defaults to the existing JWT secret so
    | signatures stay wire-compatible with the current X-Sdp-Signature scheme.
    |
    */
    'secret' => env('MESSAGING_SECRET', env('JWT_SECRET')),

    'signature_ttl' => (int) env('MESSAGING_SIGNATURE_TTL', 300),

    'qstash' => [
        'base_url' => env('UPSTASH_QSTASH_BASE_URL', 'https://qstash.upstash.io'),
        'token' => env('UPSTASH_QSTASH_TOKEN'),
        'retries' => env('UPSTASH_QSTASH_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Receive Route
    |--------------------------------------------------------------------------
    |
    | The package auto-registers the inbound HTTP endpoint. Disable it to wire the
    | route yourself, or tweak the path / middleware.
    |
    */
    'route' => [
        'enabled' => env('MESSAGING_ROUTE_ENABLED', true),
        'path' => env('MESSAGING_ROUTE_PATH', 'api/messaging/receive'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound Handlers
    |--------------------------------------------------------------------------
    |
    | Maps an incoming Message type to the handler (implementing
    | Iankibet\Messaging\Contracts\MessageHandler) that processes it. Apps publish
    | this config and register their own handlers here.
    |
    */
    'handlers' => [
        // 'reset-page' => \App\Messaging\Handlers\ResetPageHandler::class,
    ],

];
