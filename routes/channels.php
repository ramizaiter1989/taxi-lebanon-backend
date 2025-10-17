<?php

use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Enable broadcasting authentication endpoint
// For web session authentication


/**
 * 🌍 GLOBAL DRIVERS CHANNEL
 * Who can listen:
 * - Drivers who are online (no active ride)
 * - Admin
 */
Broadcast::channel('drivers-location', function ($user) {
    if ($user->role === 'admin') {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'admin'];
    }

    // Driver must be online and not have an active ride
    if ($user->role === 'driver' && $user->driver) {
        $hasActiveRide = $user->driver->rides()
            ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
            ->exists();

        if ($user->driver->availability_status && !$hasActiveRide) {
            return [
                'id' => $user->id,
                'driver_id' => $user->driver->id,
                'name' => $user->name,
                'role' => 'driver'
            ];
        }
    }

    return false;
});

/**
 * 🌍 GLOBAL PASSENGERS CHANNEL
 * Who can listen:
 * - Admin only (to see all passengers)
 */
Broadcast::channel('passengers-location', function ($user) {
    if ($user->role === 'admin') {
        return ['id' => $user->id, 'name' => $user->name, 'role' => 'admin'];
    }
    return false;
});

/**
 * 🚗 RIDE-SPECIFIC CHANNEL
 * Who can listen:
 * - Passenger who owns the ride
 * - Driver assigned to the ride
 * - Admin
 */
Broadcast::channel('ride.{rideId}', function ($user, $rideId) {
    $ride = Ride::find($rideId);

    if (!$ride) {
        return false;
    }

    // Passenger
    if ($user->id === $ride->passenger_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'passenger'
        ];
    }

    // Driver
    if ($user->driver && $user->driver->id === $ride->driver_id) {
        return [
            'id' => $user->id,
            'driver_id' => $user->driver->id,
            'name' => $user->name,
            'role' => 'driver'
        ];
    }

    // Admin
    if ($user->role === 'admin') {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'admin'
        ];
    }

    return false;
});

/**
 * 🔒 PRIVATE USER CHANNEL (for notifications)
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * 🔔 PASSENGER PRIVATE CHANNEL
 */
Broadcast::channel('passenger.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId && $user->role === 'passenger';
});
/**
 * 🔔 DRIVER PRIVATE CHANNEL
 */
Broadcast::channel('driver.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId && $user->role === 'driver';
});

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});