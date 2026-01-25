<?php

return [
    /**
     * RACELAB IS NOT SUITED FOR PRODUCTION ENVIRONMENT
     * Enable or disable the entire package
     */
    'enabled' => env('RACELAB_ENABLED', true),

    /**
     * Database that is used to store the queries and the stack traces
     */
    'database' => [
        'connection' => env('RACELAB_DB_CONNECTION', 'racelab_timeline'),
        'path' => env('RACELAB_DB_PATH', storage_path('app/racelab_timeline.sqlite')),
        'table' => env('RACELAB_DB_TABLE', 'racelab_timeline_events'),
    ],

    /**
     * Enable detailed logging of Racelab operations
     */
    'logging_enabled' => env('RACELAB_LOGGING_ENABLED', false),

    /**
     * Capture HTTP request/response boundaries
     */
    'capture_http_boundaries' => env('RACELAB_CAPTURE_HTTP', true),

    /**
     * Capture HTTP headers
     */
    'capture_headers' => env('RACELAB_CAPTURE_HEADERS', false),

    /**
     * Maximum number of stack frames to keep in tick profiler buffer
     */
    'tick_capacity' => env('RACELAB_TICK_CAPACITY', 10000),
];
