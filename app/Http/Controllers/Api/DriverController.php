<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\DriverActiveDuration;
use Carbon\Carbon;
use App\Events\DriverLocationUpdated;
use App\Models\Ride;
use Illuminate\Support\Facades\Log;
use App\Traits\PolylineTrait;
use App\Models\DriverBlockedPassenger;
use App\Services\LocationService;
class DriverController extends Controller
{
    use PolylineTrait;

    /**
     * 🚗 MAIN ENDPOINT: Get drivers/passengers based on user role and context
     * 
     * CORRECTED RULES:
     * - Admin: See ALL drivers + ALL passengers
     * - Driver (online, no active ride): See ALL OTHER online drivers (not passengers)
     * - Driver (has active ride): See ONLY their assigned PASSENGER
     * - Passenger: See ONLY their assigned driver (if ride accepted/in_progress/arrived)
     */
public function index(Request $request)
{
    $user = Auth::user();
    $defaultLocations = [
        ['lat' => 33.8938, 'lng' => 35.5018],
        ['lat' => 34.0058, 'lng' => 36.2181],
        ['lat' => 33.8500, 'lng' => 35.9000],
        ['lat' => 34.4367, 'lng' => 35.8497],
        ['lat' => 33.5606, 'lng' => 35.3756],
        ['lat' => 33.2734, 'lng' => 35.1939],
    ];

    if ($user->role === 'passenger') {
        return $this->getDriverForPassenger($user, $defaultLocations);
    }

    if ($user->role === 'driver' && $user->driver) {
        return $this->getViewForDriver($user, $defaultLocations);
    }

    return response()->json(['message' => 'Unauthorized'], 403);
}



    /**
     * 🔹 Admin sees ALL drivers + passengers
     */
    private function getAllUsersForAdmin($defaultLocations)
    {
        // Get all drivers
        $drivers = Driver::with(['user', 'rides' => fn($q) => $q->latest()->limit(1), 'rides.rideLogs'])
            ->get()
            ->map(function ($driver) use ($defaultLocations) {
                return $this->formatDriverData($driver, $defaultLocations);
            });

        // Get all passengers
        $passengers = User::where('role', 'passenger')
            ->where('status', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get()
            ->map(function ($passenger) {
                return [
                    'type' => 'passenger',
                    'id' => $passenger->id,
                    'name' => $passenger->name,
                    'phone' => $passenger->phone,
                    'lat' => (float) $passenger->current_lat,
                    'lng' => (float) $passenger->current_lng,
                    'last_update' => $passenger->last_location_update,
                ];
            });

        return response()->json([
            'role' => 'admin',
            'drivers' => $drivers,
            'passengers' => $passengers,
            'total_drivers' => $drivers->count(),
            'total_passengers' => $passengers->count(),
        ]);
    }

    /**
     * 🔹 Passenger sees ONLY their assigned driver
     */
private function getDriverForPassenger($user, $defaultLocations)
{
    $activeRide = Ride::where('passenger_id', $user->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->with(['driver.user', 'driver.rides.rideLogs'])
        ->latest()
        ->first();

    if (!$activeRide || !$activeRide->driver) {
        return response()->json([
            'role' => 'passenger',
            'my_location' => [
                'lat' => (float) $user->current_lat,
                'lng' => (float) $user->current_lng,
            ],
            'driver' => null,
            'message' => 'No active ride',
        ]);
    }

    $driver = $activeRide->driver;
    $driverData = $this->formatDriverData($driver, $defaultLocations, $activeRide);

    return response()->json([
        'role' => 'passenger',
        'my_location' => [
            'lat' => (float) $user->current_lat,
            'lng' => (float) $user->current_lng,
        ],
        'driver' => $driverData,
        'active_ride_id' => $activeRide->id,
        'ride_status' => $activeRide->status,
        'pickup_location' => [
            'lat' => (float) $activeRide->origin_lat,
            'lng' => (float) $activeRide->origin_lng,
        ],
        'destination_location' => [
            'lat' => (float) $activeRide->destination_lat,
            'lng' => (float) $activeRide->destination_lng,
        ],
    ]);
}
    /**
     * 🔹 Driver visibility logic (CORRECTED):
     * - Has active ride → see ONLY assigned PASSENGER
     * - Online but no active ride → see ALL OTHER online DRIVERS
     * - Offline → see nothing
     */
private function getViewForDriver($user, $defaultLocations)
{
    $driver = $user->driver;
    if (!$driver) {
        return response()->json(['message' => 'Driver profile not found'], 404);
    }

    $activeRide = Ride::where('driver_id', $driver->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->with('passenger')
        ->latest()
        ->first();

    if ($activeRide && $activeRide->passenger) {
        $passenger = $activeRide->passenger;
        return response()->json([
            'role' => 'driver',
            'context' => 'active_ride',
            'my_location' => [
                'lat' => (float) $driver->current_driver_lat,
                'lng' => (float) $driver->current_driver_lng,
            ],
            'passenger' => [
                'type' => 'passenger',
                'id' => $passenger->id,
                'name' => $passenger->name,
                'phone' => $passenger->phone,
                'lat' => (float) $passenger->current_lat,
                'lng' => (float) $passenger->current_lng,
                'last_update' => $passenger->last_location_update,
            ],
            'active_ride_id' => $activeRide->id,
            'ride_status' => $activeRide->status,
            'pickup_location' => [
                'lat' => (float) $activeRide->origin_lat,
                'lng' => (float) $activeRide->origin_lng,
            ],
            'destination_location' => [
                'lat' => (float) $activeRide->destination_lat,
                'lng' => (float) $activeRide->destination_lng,
            ],
        ]);
    }

    if ($driver->availability_status) {
        $otherDrivers = Driver::where('availability_status', true)
            ->where('id', '!=', $driver->id)
            ->with(['user', 'rides' => fn($q) => $q->latest()->limit(1), 'rides.rideLogs'])
            ->get()
            ->map(function ($d) use ($defaultLocations) {
                return $this->formatDriverData($d, $defaultLocations);
            });

        return response()->json([
            'role' => 'driver',
            'context' => 'online_no_ride',
            'my_location' => [
                'lat' => (float) $driver->current_driver_lat,
                'lng' => (float) $driver->current_driver_lng,
            ],
            'other_drivers' => $otherDrivers,
            'total_online' => $otherDrivers->count(),
        ]);
    }

    return response()->json([
        'role' => 'driver',
        'context' => 'offline',
        'my_location' => null,
        'other_drivers' => [],
        'message' => 'You are offline. Go online to see other drivers.',
    ]);
}





    /**
     * 🔹 Format driver data consistently
     */
    private function formatDriverData($driver, $defaultLocations, $ride = null)
    {
        $latestRide = $ride ?? $driver->rides->first();
        $latestRideLog = $latestRide?->rideLogs->last();

        $fallback = $defaultLocations[array_rand($defaultLocations)];
        $currentLat = $latestRideLog?->driver_lat ?? $driver->current_driver_lat ?? $fallback['lat'];
        $currentLng = $latestRideLog?->driver_lng ?? $driver->current_driver_lng ?? $fallback['lng'];

        $routePolyline = null;
        if ($latestRide && $latestRide->origin_lat && $latestRide->destination_lat) {
            $coords = $this->getRoutePolyline(
                $currentLat,
                $currentLng,
                $latestRide->destination_lat,
                $latestRide->destination_lng
            );
            $routePolyline = $this->encodePolyline($coords);
        }

        return [
            'type' => 'driver',
            'id' => $driver->id,
            'user_id' => $driver->user_id,
            'name' => $driver->user->name ?? null,
            'phone' => $driver->user->phone ?? null,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'lat' => (float) $currentLat,
            'lng' => (float) $currentLng,
            'availability_status' => $driver->availability_status,
            'rating' => (float) $driver->rating,
            'current_route' => $routePolyline,
            'ride_id' => $latestRide?->id,
            'ride_status' => $latestRide?->status,
            'passenger_id' => $latestRide?->passenger_id,
        ];
    }

    // ========================================
    // NEW: Get My Current Location on Map Open
    // ========================================
    
    /**
     * Get driver's current location when opening map
     */
    public function getMyLocation(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role === 'driver') {
            $driver = $user->driver;
            
            if (!$driver) {
                return response()->json(['error' => 'Driver profile not found'], 404);
            }
            
            return response()->json([
                'role' => 'driver',
                'my_location' => [
                    'lat' => (float) $driver->current_driver_lat,
                    'lng' => (float) $driver->current_driver_lng,
                ],
                'is_online' => $driver->availability_status,
                'scanning_range_km' => $driver->scanning_range_km ?? 10,
            ]);
        }
        
        if ($user->role === 'passenger') {
            return response()->json([
                'role' => 'passenger',
                'my_location' => [
                    'lat' => (float) $user->current_lat,
                    'lng' => (float) $user->current_lng,
                ],
            ]);
        }
        
        return response()->json(['error' => 'Invalid role'], 403);
    }

    // ========================================
    // EXISTING METHODS (Keep as is)
    // ========================================

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability_status' => 'required|boolean'
        ]);

        $driver = $request->user()->driver;
        $driver->availability_status = $request->availability_status;
        $driver->save();

        return response()->json($driver);
    }

    public function show(Request $request)
    {
        return response()->json($request->user()->driver);
    }

    public function showProfile(Request $request, ?Driver $driver = null)
{
    $user = Auth::user();

    if ($user->role === 'admin') {
        if (!$driver) {
            return response()->json(['message' => 'Driver ID is required for admin'], 400);
        }
    } else {
        $driver = $driver ?? $user->driver;
        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }
    }

    // Prevent non-admins from accessing other drivers
    if ($user->role !== 'admin' && $driver->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Load the related user info (name, email, etc.)
    $driver->load('user');

    // Convert to array
    $driverData = $driver->toArray();

    // Add full URLs for photo fields
    foreach ([
        'car_photo',
        'car_photo_front',
        'car_photo_back',
        'car_photo_left',
        'car_photo_right',
        'license_photo',
        'id_photo',
        'insurance_photo'
    ] as $photoField) {
        $driverData[$photoField] = $driver->$photoField
            ? asset('storage/' . $driver->$photoField)
            : null;
    }

    return response()->json($driverData);
}




public function updateProfile(Request $request, Driver $driver)
{
    $authUser = Auth::user();
    $isAdmin = $authUser->role === 'admin';
    $isOwner = $driver->user_id === $authUser->id;

    if (!$isAdmin && !$isOwner) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $rules = [
        'license_number' => 'nullable|string|max:50',
        'vehicle_type' => 'nullable|string|max:50',
        'vehicle_number' => 'nullable|string|max:50',
        'car_photo' => 'nullable|image|max:2048',
        'license_photo' => 'nullable|image|max:2048',
        'id_photo' => 'nullable|image|max:2048',
        'insurance_photo' => 'nullable|image|max:2048',
    ];

    $validated = $request->validate($rules);

    // Handle file uploads
    foreach (['car_photo', 'license_photo', 'id_photo', 'insurance_photo'] as $photoField) {
        if ($request->hasFile($photoField)) {
            $path = $request->file($photoField)->store('drivers', 'public');
            $validated[$photoField] = $path;
        }
    }

    $driver->update($validated);

    return response()->json([
        'message' => 'Driver profile updated successfully',
        'driver' => $driver,
    ]);
}





   public function goOnline(Request $request)
{
    $user = Auth::user();
    
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can go online'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    // ✅ Check if driver has active ride
    $activeRide = Ride::where('driver_id', $driver->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->exists();
    
    if ($activeRide) {
        return response()->json([
            'error' => 'Cannot go online while you have an active ride'
        ], 400);
    }
    
    // ✅ Check if already online
    if ($driver->availability_status) {
        return response()->json([
            'message' => 'Already online',
            'driver' => $driver
        ]);
    }
    
    // ✅ Validate location exists before going online
    if (!$driver->current_driver_lat || !$driver->current_driver_lng) {
        return response()->json([
            'error' => 'Please enable location services before going online'
        ], 400);
    }
    
    // ✅ Update driver status
    $driver->update([
        'availability_status' => true,
        'active_at' => now(),
        'inactive_at' => null,
    ]);
    
    // ✅ Create activity log
    DriverActiveDuration::create([
        'driver_id' => $driver->id,
        'active_at' => now(),
    ]);
    
    // ✅ Broadcast status change
    broadcast(new DriverLocationUpdated(
        $driver->id,
        $user->name,
        $driver->current_driver_lat,
        $driver->current_driver_lng
    ))->toOthers();
    
    return response()->json([
        'message' => 'Driver is now online',
        'driver' => [
            'id' => $driver->id,
            'availability_status' => true,
            'active_at' => $driver->active_at,
            'location' => [
                'lat' => $driver->current_driver_lat,
                'lng' => $driver->current_driver_lng,
            ]
        ]
    ]);
}

/**
 * Driver goes offline - fixed version
 */
public function goOffline(Request $request)
{
    $user = Auth::user();
    
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can go offline'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    // ✅ CRITICAL: Check for active ride
    $activeRide = Ride::where('driver_id', $driver->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->first();
    
    if ($activeRide) {
        return response()->json([
            'error' => 'Cannot go offline while you have an active ride',
            'active_ride_id' => $activeRide->id,
            'ride_status' => $activeRide->status
        ], 400);
    }
    
    // ✅ Check if already offline
    if (!$driver->availability_status) {
        return response()->json([
            'message' => 'Already offline',
            'driver' => $driver
        ]);
    }
    
    // ✅ Update driver status
    $driver->update([
        'availability_status' => false,
        'inactive_at' => now(),
    ]);
    
    // ✅ Close active session
    $session = DriverActiveDuration::where('driver_id', $driver->id)
        ->whereNull('inactive_at')
        ->latest()
        ->first();
    
    if ($session) {
        $session->update([
            'inactive_at' => now(),
            'duration_seconds' => now()->diffInSeconds($session->active_at),
        ]);
    }
    
    // ✅ Broadcast status change
    broadcast(new DriverLocationUpdated(
        $driver->id,
        $user->name,
        null,
        null
    ))->toOthers();
    
    return response()->json([
        'message' => 'Driver is now offline',
        'driver' => [
            'id' => $driver->id,
            'availability_status' => false,
            'inactive_at' => $driver->inactive_at,
            'session_duration' => $session ? gmdate('H:i:s', $session->duration_seconds) : null
        ]
    ]);
}

/**
 * Get total active time for a driver (today or custom period)
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\Driver $driver
 * @return \Illuminate\Http\JsonResponse
 */
public function getActiveTime(Request $request, Driver $driver)
{
    $this->authorizeDriver($driver);

    $period = $request->input('period', 'today'); // Default: today
    $startDate = null;
    $endDate = null;

    switch ($period) {
        case 'today':
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
            break;
        case 'week':
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
            break;
        case 'month':
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            break;
        case 'custom':
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            break;
        default:
            return response()->json(['error' => 'Invalid period'], 400);
    }

    $seconds = $driver->activeDurations()
        ->whereBetween('active_at', [$startDate, $endDate])
        ->sum('duration_seconds');

    $formattedDuration = gmdate('H:i:s', $seconds);

    return response()->json([
        'driver_id' => $driver->id,
        'period' => $period,
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
        'total_active_time_seconds' => $seconds,
        'total_active_time_formatted' => $formattedDuration,
    ]);
}

    /**
     * Update driver's current location
     * 
     * @param Request $request
     * @param Driver $driver
     * @return \Illuminate\Http\JsonResponse
     */


public function updateLocation(Request $request, LocationService $locationService)
{
    $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
    ]);

    $user = $request->user();

    if ($user->role !== 'driver') {
        return response()->json(['error' => 'User is not a driver'], 403);
    }

    $result = $locationService->handleLocation($user, $request->lat, $request->lng, true);

    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['status']);
    }

    return response()->json([
        'success' => true,
        'message' => 'Driver location updated successfully',
        'data' => $result
    ]);
}

public function streamLocation(Request $request, LocationService $locationService)
{
    $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
    ]);

    $result = $locationService->handleLocation($request->user(), $request->lat, $request->lng, false);

    if (isset($result['error'])) {
        return response()->json(['error' => $result['error']], $result['status']);
    }

    return response()->json([
        'success' => true,
        'message' => 'Location streamed',
        'data' => $result
    ]);
}





    public function updateRange(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $request->validate([
            'scanning_range_km' => 'required|numeric|min:1|max:5000',
        ]);

        $driver->update([
            'scanning_range_km' => $request->scanning_range_km,
        ]);

        return response()->json([
            'message' => 'Scanning range updated',
            'range' => $driver->scanning_range_km,
        ]);
    }

    public function activityLogs(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $logs = DriverActiveDuration::where('driver_id', $driver->id)
            ->orderByDesc('active_at')
            ->get()
            ->map(fn($log) => [
                'active_at' => Carbon::parse($log->active_at)->toDateTimeString(),
                'inactive_at' => $log->inactive_at ? Carbon::parse($log->inactive_at)->toDateTimeString() : null,
                'duration_seconds' => $log->duration_seconds,
                'duration_human' => gmdate("H:i:s", $log->duration_seconds ?? 0),
            ]);

        return response()->json($logs);
    }

    public function liveStatus(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $status = [
            'is_online' => $driver->availability_status,
            'active_since' => null,
            'duration_seconds' => 0,
            'duration_human' => null,
        ];

        if ($driver->availability_status && $driver->active_at) {
            $now = Carbon::now();
            $duration = $now->diffInSeconds($driver->active_at);

            $status['active_since'] = $driver->active_at->toDateTimeString();
            $status['duration_seconds'] = $duration;
            $status['duration_human'] = gmdate("H:i:s", $duration);
        }

        return response()->json($status);
    }

    /**
     * Block a passenger
     */
    public function blockPassenger(Request $request, User $passenger)
    {
        $driver = $request->user()->driver;

        if (!$driver) {
            return response()->json(['error' => 'Only drivers can block passengers'], 403);
        }

        if ($passenger->role !== 'passenger') {
            return response()->json(['error' => 'Can only block passengers'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        DriverBlockedPassenger::firstOrCreate([
            'driver_id' => $driver->id,
            'passenger_id' => $passenger->id
        ], [
            'reason' => $validated['reason'] ?? null
        ]);

        return response()->json(['message' => 'Passenger blocked successfully']);
    }

    /**
     * Unblock a passenger
     */
    public function unblockPassenger(Request $request, User $passenger)
    {
        $driver = $request->user()->driver;

        DriverBlockedPassenger::where('driver_id', $driver->id)
            ->where('passenger_id', $passenger->id)
            ->delete();

        return response()->json(['message' => 'Passenger unblocked']);
    }

    /**
     * Get blocked passengers
     */
    public function getBlockedPassengers(Request $request)
    {
        $driver = $request->user()->driver;

        $blocked = DriverBlockedPassenger::where('driver_id', $driver->id)
            ->with('passenger:id,name,email,phone')
            ->get();

        return response()->json($blocked);
    }

    private function authorizeDriver(Driver $driver)
    {
        if (Auth::user()->role !== 'admin' && $driver->user_id !== Auth::id()) {
            abort(response()->json(['message' => 'Unauthorized'], 403));
        }
    }

    private function encodePolyline(array $coordinates, $precision = 5)
    {
        $factor = pow(10, $precision);
        $output = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($coordinates as $point) {
            $lat = round($point[0] * $factor);
            $lng = round($point[1] * $factor);
            $dLat = $lat - $prevLat;
            $dLng = $lng - $prevLng;

            $encode = function ($num) {
                $num = $num < 0 ? ~(($num << 1)) : ($num << 1);
                $out = '';
                while ($num >= 0x20) {
                    $out .= chr((0x20 | ($num & 0x1f)) + 63);
                    $num >>= 5;
                }
                return $out . chr($num + 63);
            };

            $output .= $encode($dLat) . $encode($dLng);
            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $output;
    }

    private function getRoutePolyline($startLat, $startLng, $endLat, $endLng)
    {
        $apiKey = env('ORS_API_KEY');
        if (!$apiKey) return [];

        $cacheKey = "route:{$startLat},{$startLng}-{$endLat},{$endLng}";

        return cache()->remember($cacheKey, now()->addMinutes(30), function () use ($startLat, $startLng, $endLat, $endLng, $apiKey) {
            $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key={$apiKey}&start={$startLng},{$startLat}&end={$endLng},{$endLat}";
            try {
                $response = @file_get_contents($url);
                if (!$response) return [];
                $data = json_decode($response, true);
                if (!isset($data['features'][0]['geometry']['coordinates'])) return [];
                return array_map(fn($c) => [$c[1], $c[0]], $data['features'][0]['geometry']['coordinates']);
            } catch (\Exception $e) {
                Log::error("ORS Route API Error: " . $e->getMessage());
                return [];
            }
        });
    }

// public function saveLocation(Request $request)
// {
//     $user = $request->user();
//     $driver = $user->driver;

//     if (!$driver) {
//         return response()->json(['error' => 'Not a driver'], 403);
//     }

//     $request->validate([
//         'lat' => 'required|numeric|between:-90,90',
//         'lng' => 'required|numeric|between:-180,180',
//     ]);

//     $lat = $request->input('lat');
//     $lng = $request->input('lng');

//     // ✅ Save to DRIVER table
//     $driver->update([
//         'current_driver_lat' => $lat,
//         'current_driver_lng' => $lng,
//         'last_location_update' => now(),
//     ]);

//     // ✅ Also save to USER table
//     $user->update([
//         'current_lat' => $lat,
//         'current_lng' => $lng,
//         'last_location_update' => now(),
//     ]);

//     // ✅ Broadcast event (optional)
//     broadcast(new DriverLocationUpdated(
//         $driver->id,
//         $user->name,
//         $lat,
//         $lng
//     ))->toOthers();

//     return response()->json([
//         'status' => 'saved',
//         'message' => 'Driver and user location updated successfully',
//         'data' => [
//             'driver' => [
//                 'lat' => $driver->current_driver_lat,
//                 'lng' => $driver->current_driver_lng,
//             ],
//             'user' => [
//                 'lat' => $user->current_lat,
//                 'lng' => $user->current_lng,
//             ],
//         ]
//     ]);
// }


}