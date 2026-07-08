<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Better Uptime
    |--------------------------------------------------------------------------
    |
    | External uptime monitors (Better Uptime / Better Stack) ping signed
    | heartbeat URLs. The token must be sent as the `X-Uptime-Token` request
    | header — not a query string, which leaks into access logs and referer
    | headers. See docs/OBSERVABILITY.md for monitor setup.
    |
    */

    'uptime' => [
        'enabled' => env('BETTER_UPTIME_ENABLED', false),
        'heartbeat_token' => env('UPTIME_HEARTBEAT_TOKEN'),
        'scheduler_stale_minutes' => (int) env('UPTIME_SCHEDULER_STALE_MINUTES', 5),
        'queue_stale_minutes' => (int) env('UPTIME_QUEUE_STALE_MINUTES', 5),
        'cache_key_scheduler' => 'observability.uptime.scheduler',
        'cache_key_queue' => 'observability.uptime.queue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity log retention
    |--------------------------------------------------------------------------
    */

    'activitylog_retention_days' => (int) env('ACTIVITYLOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Flare tags (applied to every report when enabled)
    |--------------------------------------------------------------------------
    */

    'flare' => [
        'application' => env('APP_NAME', 'Quatriz TimeSheet'),
        'tenant' => env('FLARE_TENANT_TAG', 'quatriz'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Site traffic (admin dashboard metric)
    |--------------------------------------------------------------------------
    */

    'traffic' => [
        'enabled' => env('SITE_TRAFFIC_ENABLED', true),
    ],

];
