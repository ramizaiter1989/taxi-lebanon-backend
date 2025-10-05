<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://127.0.0.1:5174',           // Vite dev server
        'http://localhost:5174',
        'https://4f7abfb7d80b.ngrok-free.app', // Ngrok public URL (for external use)
        'http://localhost:19006',
        'http://localhost:8081',
        'http://192.168.2.107:8081',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Set to true if you're using cookies (like with Sanctum)

];
