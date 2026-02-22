<?php

return [
    'hokesen' => [
        'enabled' => env('HOKESEN_INTEGRATION_ENABLED', false),
        'issuer' => env('HOKESEN_ASSERTION_ISSUER', 'hokesen.dev'),
        'audience' => env('HOKESEN_ASSERTION_AUDIENCE', 'dailycalisthenic'),
        'shared_secret' => env('HOKESEN_ASSERTION_SHARED_SECRET'),
        'previous_shared_secret' => env('HOKESEN_ASSERTION_PREVIOUS_SHARED_SECRET'),
        'clock_skew_seconds' => (int) env('HOKESEN_ASSERTION_CLOCK_SKEW_SECONDS', 60),
        'max_ttl_seconds' => (int) env('HOKESEN_ASSERTION_MAX_TTL_SECONDS', 300),
    ],
];
