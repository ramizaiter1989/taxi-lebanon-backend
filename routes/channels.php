<?php

use App\Models\Ride;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ride.{rideId}', function ($user, $rideId) {
    $ride = Ride::find($rideId);

    if (!$ride) return false;

    return $user->id === $ride->passenger_id || $user->id === optional($ride->driver)->user_id;
});
