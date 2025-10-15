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
    $user = $request->user();
    if ($user->role !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $now = Carbon::now();
    $fiveMinutesAgo = $now->copy()->subMinutes(5);

    // Online Drivers
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
                'status' => 'online',
            ];
        });

    // Online Passengers
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

    return response()->json([
        'drivers' => $drivers,
        'passengers' => $passengers,
        'total_drivers' => $drivers->count(),
        'total_passengers' => $passengers->count(),
    ]);
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