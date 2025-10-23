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





    public function goOnline(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $driver->update([
            'availability_status' => true,
            'active_at' => now(),
            'inactive_at' => null,
        ]);

        DriverActiveDuration::create([
            'driver_id' => $driver->id,
            'active_at' => now(),
        ]);

        broadcast(new DriverLocationUpdated($driver->id, $driver->user->name, null, null))->toOthers();

        return response()->json(['message' => 'Driver online', 'driver' => $driver]);
    }

    public function goOffline(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $driver->update([
            'availability_status' => false,
            'inactive_at' => now(),
        ]);

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

        broadcast(new DriverLocationUpdated($driver->id, $driver->user->name, null, null))->toOthers();

        return response()->json(['message' => 'Driver offline', 'driver' => $driver]);
    }


    /**
     * Update driver's current location
     * 
     * @param Request $request
     * @param Driver $driver
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request, Driver $driver)
{
    try {
        // Check authorization
        if ($request->user()->id !== $driver->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the request (use current_driver_lat/lng)
        $validated = $request->validate([
                'current_driver_lat' => 'required|numeric|between:-90,90',
                'current_driver_lng' => 'required|numeric|between:-180,180',
            ]);

            $driver->update([
                'current_driver_lat' => $validated['current_driver_lat'],
                'current_driver_lng' => $validated['current_driver_lng'],
            ]);


        // Broadcast location update
        broadcast(new DriverLocationUpdated(
            $driver->id,
            $driver->user->name,
            $validated['current_driver_lat'],
            $validated['current_driver_lng']
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'driver_id' => $driver->id,
                'lat' => $driver->current_driver_lat,
                'lng' => $driver->current_driver_lng,
                'updated_at' => $driver->updated_at,
            ]
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error updating driver location: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update location',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
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
    public function streamLocation(Request $request)
{
    $user = $request->user();
    $driver = $user->driver;

    if (!$driver) {
        return response()->json(['error' => 'Not a driver'], 403);
    }

    $lat = $request->input('lat');
    $lng = $request->input('lng');

    // Just broadcast — no DB write
    broadcast(new DriverLocationUpdated(
        $driver->id,
        $user->name,
        $lat,
        $lng
    ));

    return response()->json(['status' => 'streamed']);
}

public function saveLocation(Request $request)
{
    $user = $request->user();
    $driver = $user->driver;

    if (!$driver) {
        return response()->json(['error' => 'Not a driver'], 403);
    }

    $lat = $request->input('lat');
    $lng = $request->input('lng');

    // Save to DB
    $driver->update([
        'current_lat' => $lat,
        'current_lng' => $lng,
        'last_location_update' => now(),
    ]);

    // Also broadcast (optional)
    broadcast(new DriverLocationUpdated(
        $driver->id,
        $user->name,
        $lat,
        $lng
    ));

    return response()->json(['status' => 'saved']);
}

}