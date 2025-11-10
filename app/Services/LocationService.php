<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\User;
use App\Events\DriverLocationUpdated;
use App\Events\PassengerLocationUpdated;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Update or stream location for a user
     * 
     * @param User $user
     * @param float $lat
     * @param float $lng
     * @param bool $saveToDB
     * @return array
     */
    public function handleLocation(User $user, float $lat, float $lng, bool $saveToDB = true): array
    {
        try {
            if ($user->role === 'driver') {
                $driver = $user->driver;

                if (!$driver) {
                    Log::warning("Driver record missing for user_id {$user->id}");
                    return ['error' => 'Not a driver', 'status' => 403];
                }

                if ($saveToDB) {
                    $driver->current_driver_lat = $lat;
                    $driver->current_driver_lng = $lng;
                    $driver->last_location_update = now();
                    $driver->save(); // Save driver location

                    // Optional: update user's last_location_update as well
                    $user->last_location_update = now();
                    $user->save();
                }

                broadcast(new DriverLocationUpdated(
                    $driver->id,
                    $user->name,
                    $lat,
                    $lng
                ))->toOthers();

                return [
                    'role' => 'driver',
                    'driver_id' => $driver->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'saved' => $saveToDB
                ];

            } elseif ($user->role === 'passenger') {
                if ($saveToDB) {
                    $user->current_lat = $lat;
                    $user->current_lng = $lng;
                    $user->last_location_update = now();
                    $user->save(); // Save passenger location
                }

                if ($user->status) {
                    broadcast(new PassengerLocationUpdated($user))->toOthers();
                }

                return [
                    'role' => 'passenger',
                    'user_id' => $user->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'saved' => $saveToDB
                ];
            }

            return ['error' => 'Invalid role', 'status' => 403];

        } catch (\Exception $e) {
            Log::error("LocationService error: " . $e->getMessage());
            return ['error' => 'Failed to handle location', 'status' => 500];
        }
    }
}
