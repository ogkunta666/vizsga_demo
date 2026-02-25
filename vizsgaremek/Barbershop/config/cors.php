<?php

$allowedOrigins = array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200'))
));

// Fejlesztői környezetben mindig engedélyezzük a localhost-ot
if (env('APP_ENV') !== 'production') {
    $allowedOrigins = array_unique(array_merge($allowedOrigins, [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://10.1.47.16:4200',
    ]));
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values($allowedOrigins),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
