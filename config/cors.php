<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // or specify your Flutter Web domain(s)

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // true if you use cookies/Sanctum
];
