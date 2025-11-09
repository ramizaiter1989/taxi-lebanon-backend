<?php
// app/Observers/DriverObserver.php
// app/Observers/DriverObserver.php
namespace App\Observers;

use App\Models\Driver;
use App\Models\DriverActiveDuration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DriverObserver
{
    public function updating(Driver $driver)
    {
        if ($driver->isDirty('availability_status')) {
            $now = Carbon::now();
            Log::info("Driver {$driver->id} availability changed to: " . ($driver->availability_status ? 'ONLINE' : 'OFFLINE'));

            if ($driver->availability_status) {
                // Close any open session before creating a new one
                $openSession = DriverActiveDuration::where('driver_id', $driver->id)
                    ->whereNull('inactive_at')
                    ->latest()
                    ->first();
                if ($openSession) {
                    $activeAt = Carbon::parse($openSession->active_at);
                    $openSession->inactive_at = $now;
                    $openSession->duration_seconds = $activeAt->diffInSeconds($now);
                    $openSession->save();
                }

                // Create new session
                $driver->active_at = $now;
                DriverActiveDuration::create([
                    'driver_id' => $driver->id,
                    'active_at' => $now,
                ]);
            } else {
                // Update the current session with inactive_at and duration
                $driver->inactive_at = $now;
                $activeDuration = DriverActiveDuration::where('driver_id', $driver->id)
                    ->whereNull('inactive_at')
                    ->latest()
                    ->first();
                if ($activeDuration) {
                    $activeAt = Carbon::parse($activeDuration->active_at);
                    $activeDuration->inactive_at = $now;
                    $activeDuration->duration_seconds = $activeAt->diffInSeconds($now);
                    $activeDuration->save();
                }
            }
        }
    }
}
