<?php 
return [
    'default' => env('BROADCAST_DRIVER', 'pusher'), // Change 'null' to 'pusher'
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'eu'),
                'useTLS' => env('PUSHER_APP_USE_TLS', true),
            ],
        ],
    ],
];

  