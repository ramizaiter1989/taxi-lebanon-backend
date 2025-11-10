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
        Log::info("Handling location for user {$user->id}", [
            'role' => $user->role,
            'lat' => $lat,
            'lng' => $lng,
            'saveToDB' => $saveToDB,
        ]);

        if ($user->role === 'driver') {
            $driver = $user->driver;
            Log::info("Driver record for user {$user->id}: ", [$driver]);

            if (!$driver) {
                return ['error' => 'Not a driver', 'status' => 403];
            }

            if ($saveToDB) {
                Log::info("Updating driver location for driver {$driver->id}");
                $driver->update([
                    'current_driver_lat' => $lat,
                    'current_driver_lng' => $lng,
                    'last_location_update' => now(),
                ]);
                Log::info("Driver location updated successfully for driver {$driver->id}");
            }

            // Temporarily disable broadcasting for debugging
            // broadcast(new DriverLocationUpdated($driver->id, $user->name, $lat, $lng))->toOthers();

            return [
                'role' => 'driver',
                'driver_id' => $driver->id,
                'lat' => $lat,
                'lng' => $lng,
                'saved' => $saveToDB
            ];
        } elseif ($user->role === 'passenger') {
            // ... passenger logic
        }

        return ['error' => 'Invalid role', 'status' => 403];
    } catch (\Exception $e) {
        Log::error("LocationService error: " . $e->getMessage(), [
            'user_id' => $user->id,
            'role' => $user->role,
            'driver_id' => $driver->id ?? null,
            'lat' => $lat,
            'lng' => $lng,
            'stack_trace' => $e->getTraceAsString(),
        ]);
        return ['error' => 'Failed to handle location', 'status' => 500];
    }
}

}
