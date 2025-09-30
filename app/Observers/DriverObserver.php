<?php

namespace App\Observers;

use App\Models\Driver;
use App\Models\DriverActiveDuration;
use Carbon\Carbon;

class DriverObserver
{
    /**
     * Handle the Driver "updating" event.
     */
    public function updating(Driver $driver)
    {
        // Check if availability_status has changed
        if ($driver->isDirty('availability_status')) {
            $now = Carbon::now();

            // Case 1: Availability switched to TRUE (active)
            if ($driver->availability_status) {
                $driver->active_at = $now;

                DriverActiveDuration::create([
                    'driver_id' => $driver->id,
                    'active_at' => $now,
                ]);
            }

            // Case 2: Availability switched to FALSE (inactive)
            else {
                $driver->inactive_at = $now;

                // Find the last active duration with no inactive_at
                $activeDuration = DriverActiveDuration::where('driver_id', $driver->id)
                    ->whereNull('inactive_at')
                    ->latest()
                    ->first();

                if ($activeDuration) {
                    $activeDuration->inactive_at = $now;
                    $activeDuration->duration_seconds = $now->diffInSeconds($activeDuration->active_at);
                    $activeDuration->save();
                }
            }
        }
    }
}
