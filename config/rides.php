<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fare Calculation Settings
    |--------------------------------------------------------------------------
    */

    'fare_tolerance_percent' => env('RIDE_FARE_TOLERANCE', 2),

    /*
    |--------------------------------------------------------------------------
    | Driver Range Settings
    |--------------------------------------------------------------------------
    */

    'default_scanning_range' => env('DRIVER_SCANNING_RANGE_KM', 10),
    'max_acceptance_range_km' => env('DRIVER_MAX_ACCEPTANCE_RANGE_KM', 15),

    /*
    |--------------------------------------------------------------------------
    | Estimated Time Calculation
    |--------------------------------------------------------------------------
    */

    'average_speed_kmh' => env('AVERAGE_SPEED_KMH', 30),

    /*
    |--------------------------------------------------------------------------
    | Ride Status Transitions
    |--------------------------------------------------------------------------
    */

    'allowed_status_transitions' => [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['arrived', 'cancelled'],
        'arrived' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancellation Reasons
    |--------------------------------------------------------------------------
    */

    'cancellation_reasons' => [
        'driver_no_show' => 'Driver did not show up',
        'wrong_location' => 'Wrong pickup location',
        'changed_mind' => 'Changed my mind',
        'too_expensive' => 'Fare too expensive',
        'emergency' => 'Emergency situation',
        'other' => 'Other reason',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'geocoding_cache_ttl' => env('GEOCODING_CACHE_TTL', 86400), // 24 hours
    'route_cache_ttl' => env('ROUTE_CACHE_TTL', 3600), // 1 hour
    'eta_cache_ttl' => env('ETA_CACHE_TTL', 180), // 3 minutes

    /*
    |--------------------------------------------------------------------------
    | API Limits
    |--------------------------------------------------------------------------
    */

    'max_rides_per_page' => 100,
    'available_rides_limit' => 20,

];