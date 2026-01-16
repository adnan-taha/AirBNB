<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:8080',
        'http://localhost:3000',
        'http://localhost:5000',
        'http://localhost:60610',
        'http://127.0.0.1',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5000',
        'http://127.0.0.1:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => true, // Can be true when specifying exact origins
];
