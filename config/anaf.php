<?php

declare(strict_types=1);

return [
    'request_timeout' => env('ANAF_REQUEST_TIMEOUT', 15), // in seconds
    // OAuth2 settings
    'oauth' => [
        'client_id' => env('ANAF_CLIENT_ID'),
        'client_secret' => env('ANAF_CLIENT_SECRET'),
        'redirect_uri' => env('ANAF_REDIRECT_URI', 'http://localhost/auth/anaf/callback'),
    ],
    // eFactura settings
    'efactura' => [
        'test_mode' => env('ANAF_EFACTURA_TEST_MODE', false), // true for sandbox, false for production
        'cache' => [
            'store' => env('ANAF_EFACTURA_CACHE_STORE', 'file'), // e.g., 'file', 'database', 'redis'
            'enabled' => env('ANAF_EFACTURA_CACHE_ENABLED', true),
            'ttl' => env('ANAF_EFACTURA_CACHE_TTL', 84600), // in seconds
        ],
    ],

];
