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
                    return ['error' => 'Not a driver', 'status' => 403];
                }

                if ($saveToDB) {
                    $driver->update([
                        'current_driver_lat' => $lat,
                        'current_driver_lng' => $lng,
                        'last_location_update' => now(),
                    ]);
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
                    $user->update([
                        'current_lat' => $lat,
                        'current_lng' => $lng,
                        'last_location_update' => now(),
                    ]);
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
