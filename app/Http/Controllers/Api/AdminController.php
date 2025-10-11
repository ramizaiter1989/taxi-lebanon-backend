<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use App\Models\Ride;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Fetch live locations based on user role
     * - Admin: See all online drivers and passengers
     * - Driver: See all online drivers only (no active ride)
     * - Passenger: See only themselves, and their assigned driver when ride is accepted
     */
    public function liveLocations(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $now = Carbon::now();
            $fiveMinutesAgo = $now->copy()->subMinutes(5);

            $liveLocations = collect();

            // ---------------------
            // ADMIN: See everything
            // ---------------------
            if ($user->role === 'admin') {
                // Online Drivers (all)
                $drivers = Driver::where('availability_status', true)
                    ->whereNotNull('current_driver_lat')
                    ->whereNotNull('current_driver_lng')
                    ->with('user')
                    ->get()
                    ->map(function($driver) {
                        return [
                            'type' => 'driver',
                            'id' => $driver->id,
                            'user_id' => $driver->user_id,
                            'name' => $driver->user->name ?? 'Unknown Driver',
                            'lat' => (float) $driver->current_driver_lat,
                            'lng' => (float) $driver->current_driver_lng,
                            'last_update' => $driver->updated_at,
                            'status' => $driver->availability_status ? 'online' : 'offline',
                        ];
                    });

                // Online Passengers (active in last 5 minutes)
                $passengers = User::where('role', 'passenger')
                    ->where('status', true)
                    ->whereNotNull('current_lat')
                    ->whereNotNull('current_lng')
                    ->where('last_location_update', '>=', $fiveMinutesAgo)
                    ->get()
                    ->map(function($passenger) {
                        return [
                            'type' => 'passenger',
                            'id' => $passenger->id,
                            'name' => $passenger->name,
                            'lat' => (float) $passenger->current_lat,
                            'lng' => (float) $passenger->current_lng,
                            'last_update' => $passenger->last_location_update,
                            'status' => 'online',
                        ];
                    });

                $liveLocations = $drivers->merge($passengers);
            }

            // ---------------------
            // DRIVER: See all drivers (except self) with no active ride
            // ---------------------
            elseif ($user->role === 'driver' && $user->driver) {
                $drivers = Driver::where('availability_status', true)
                    ->whereNotNull('current_driver_lat')
                    ->whereNotNull('current_driver_lng')
                    ->where('id', '!=', $user->driver->id) // Exclude self by driver ID
                    ->with('user')
                    ->get()
                    ->filter(function($driver) {
                        // Only show drivers without active rides
                        $hasActiveRide = $driver->rides()
                            ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
                            ->exists();
                        return !$hasActiveRide;
                    })
                    ->map(function($driver) {
                        return [
                            'type' => 'driver',
                            'id' => $driver->id,
                            'user_id' => $driver->user_id,
                            'name' => $driver->user->name ?? 'Unknown Driver',
                            'lat' => (float) $driver->current_driver_lat,
                            'lng' => (float) $driver->current_driver_lng,
                            'last_update' => $driver->updated_at,
                            'status' => 'online',
                        ];
                    })
                    ->values(); // Reset array keys

                $liveLocations = $drivers;
            }

            // ---------------------
            // PASSENGER: See only assigned driver (if ride accepted)
            // ---------------------
            elseif ($user->role === 'passenger') {
                // Find active/accepted ride for this passenger
                $activeRide = Ride::where('passenger_id', $user->id)
                    ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
                    ->with('driver.user')
                    ->first();

                if ($activeRide && $activeRide->driver) {
                    $driver = $activeRide->driver;
                    
                    if ($driver->current_driver_lat && $driver->current_driver_lng) {
                        $liveLocations->push([
                            'type' => 'driver',
                            'id' => $driver->id,
                            'user_id' => $driver->user_id,
                            'name' => $driver->user->name ?? 'Your Driver',
                            'lat' => (float) $driver->current_driver_lat,
                            'lng' => (float) $driver->current_driver_lng,
                            'last_update' => $driver->updated_at,
                            'ride_id' => $activeRide->id,
                            'ride_status' => $activeRide->status,
                            'is_my_driver' => true,
                        ]);
                    }
                }
                // Passenger only sees their assigned driver, no other users
            }

            return response()->json($liveLocations);

        } catch (\Exception $e) {
            Log::error('Error fetching live locations: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to fetch live locations',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Admin only: Get all users with their current status
     */
    public function allUsers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::with(['driver', 'rides' => function($q) {
            $q->latest()->limit(5);
        }])->get();

        return response()->json($users);
    }

    /**
     * Admin only: Get dashboard statistics
     */
    public function statistics(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fiveMinutesAgo = Carbon::now()->subMinutes(5);

        $stats = [
            'total_users' => User::count(),
            'total_drivers' => Driver::count(),
            'online_drivers' => Driver::where('availability_status', true)->count(),
            'total_passengers' => User::where('role', 'passenger')->count(),
            'online_passengers' => User::where('role', 'passenger')
                ->where('status', true)
                ->where('last_location_update', '>=', $fiveMinutesAgo)
                ->count(),
            'total_rides' => Ride::count(),
            'active_rides' => Ride::whereIn('status', ['pending', 'accepted', 'in_progress'])->count(),
            'completed_today' => Ride::where('status', 'completed')
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];

        return response()->json($stats);
    }
}