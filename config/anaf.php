<?php

declare(strict_types=1);

return [
    // OAuth2 settings
    'oauth' => [
        'client_id' => env('ANAF_CLIENT_ID'),
        'client_secret' => env('ANAF_CLIENT_SECRET'),
        'redirect_uri' => env('ANAF_REDIRECT_URI', 'http://localhost/auth/anaf/callback'),
    ],
    // eFactura settings
    'efactura' => [
        'test_mode' => env('ANAF_EFACTURA_TEST_MODE', false),
        'timeout' => env('ANAF_EFACTURA_TIMEOUT', 15), // in seconds
    ],

];
