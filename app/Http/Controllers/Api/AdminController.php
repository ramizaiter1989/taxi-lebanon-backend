<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Fetch live locations of all online drivers and passengers
     */
    public function liveLocations()
    {
        $now = Carbon::now();
        $fiveMinutesAgo = $now->subMinutes(5);

        // ---------------------
        // Online Drivers
        // ---------------------
        $drivers = Driver::where('availability_status', true)
            ->whereNotNull('current_driver_lat')
            ->whereNotNull('current_driver_lng')
            ->get()
            ->map(fn($driver) => [
                'type' => 'driver',
                'id' => $driver->id,
                'name' => $driver->user->name ?? null,
                'lat' => $driver->current_driver_lat,
                'lng' => $driver->current_driver_lng,
                'last_update' => $driver->updated_at, // optional
            ]);

        // ---------------------
        // Online Passengers
        // ---------------------
        $passengers = User::where('role', 'passenger')
            ->where('status', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->where('last_location_update', '>=', $fiveMinutesAgo)
            ->get()
            ->map(fn($user) => [
                'type' => 'passenger',
                'id' => $user->id,
                'name' => $user->name,
                'lat' => $user->current_lat,
                'lng' => $user->current_lng,
                'last_update' => $user->last_location_update,
            ]);

        // Merge both
        $liveLocations = $drivers->merge($passengers);

        return response()->json($liveLocations);
    }
}
