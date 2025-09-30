<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use App\Models\DriverActiveDuration;
use Carbon\Carbon;
use App\Events\DriverLocationUpdated;
use App\Models\Ride;
use Illuminate\Support\Facades\Log;
use App\Traits\PolylineTrait;

class DriverController extends Controller
{
    use PolylineTrait;
    // ---------------------
    // Update availability
    // ---------------------
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

    // ---------------------
    // List available drivers
    // ---------------------
    public function index()
{
    // Default fallback locations (in case a driver has no ride)
    $defaultLocations = [
        ['lat' => 33.8938, 'lng' => 35.5018],
        ['lat' => 34.0058, 'lng' => 36.2181],
        ['lat' => 33.8500, 'lng' => 35.9000],
        ['lat' => 34.4367, 'lng' => 35.8497],
        ['lat' => 33.5606, 'lng' => 35.3756],
        ['lat' => 33.2734, 'lng' => 35.1939],
        ['lat' => 33.9700, 'lng' => 35.9000],
        ['lat' => 34.1200, 'lng' => 35.6500],
    ];

    $driversQuery = Driver::query()->where('availability_status', true)
        ->with(['user', 'rides' => fn($q) => $q->latest()->limit(1), 'rides.rideLogs']);

    // If the user is not admin, show only their driver record
    if (Auth::user()->role !== 'admin') {
    $driversQuery->where('user_id', Auth::id());
}


    $drivers = $driversQuery->get()->map(function ($driver) use ($defaultLocations) {
        $latestRide = $driver->rides->first();
        $latestRideLog = $latestRide?->rideLogs->last();

        // Fallback location if no ride exists
        $fallback = $defaultLocations[array_rand($defaultLocations)];
        $currentLat = $latestRideLog->driver_lat ?? $driver->current_driver_lat ?? $fallback['lat'];
        $currentLng = $latestRideLog->driver_lng ?? $driver->current_driver_lng ?? $fallback['lng'];

        // Generate polyline if a ride exists
        $routePolyline = null;
        if ($latestRideLog) {
            $coords = $this->getRoutePolyline(
                $latestRideLog->start_lat,
                $latestRideLog->start_lng,
                $latestRideLog->end_lat,
                $latestRideLog->end_lng
            );
            $routePolyline = $this->encodePolyline($coords);
        }

        return [
            'id' => $driver->id,
            'name' => $driver->user->name ?? null,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'current_driver_lat' => $currentLat,
            'current_driver_lng' => $currentLng,
            'availability_status' => $driver->availability_status,
            'rating' => $driver->rating,
            'current_route' => $routePolyline,
            'ride_id' => $latestRide?->id,
        ];
    });

    return response()->json($drivers);
}
// ---------------------
// Fetch authenticated driver's profile
// ---------------------
public function showProfile(Request $request, ?Driver $driver = null)
{
    $user = Auth::user();

    // If the user is an admin, $driver must be passed
    if ($user->role === 'admin') {
        if (!$driver) {
            return response()->json(['message' => 'Driver ID is required for admin'], 400);
        }
    } else {
        // For normal drivers, use their own driver record if not passed
        $driver = $driver ?? $user->driver;
        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }
    }

    // Authorization: admin can access any driver, normal drivers only their own
    if (!$user->role === 'admin' && $driver->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Convert stored file paths to public URLs
    $driverData = $driver->toArray();
    foreach (['car_photo', 'license_photo', 'id_photo', 'insurance_photo'] as $photoField) {
        $driverData[$photoField] = $driver->$photoField ? asset('storage/' . $driver->$photoField) : null;
    }

    return response()->json($driverData);
}


// ---------------------
// Update authenticated driver's profile
// ---------------------
public function updateProfile(Request $request, Driver $driver)
{
    $user = Auth::user();
Log::info('UpdateProfile called', [
        'user_id' => $user->id,
        'driver_id' => $driver->id,
        'request_data' => $request->all()
    ]);
    // Authorization
    if ($user->role !== 'admin' && $driver->user_id !== $user->id) {
        Log::warning('Unauthorized attempt', ['user_id' => $user->id]);
        return response()->json(['message' => 'Unauthorized'], 403);

    }

    // Validate inputs
    $validated = $request->validate([
        'license_number' => ['nullable', 'string', 'max:50'],
        'vehicle_type' => ['nullable', 'string', 'max:50'],
        'vehicle_number' => ['nullable', 'string', 'max:50'],
        'rating' => ['nullable', 'numeric', 'between:0,10'],
        'availability_status' => ['nullable', 'boolean'],
        'current_driver_lat' => ['nullable', 'numeric'],
        'current_driver_lng' => ['nullable', 'numeric'],
        'scanning_range_km' => ['nullable', 'numeric'],
        'active_at' => ['nullable', 'date'],
        'inactive_at' => ['nullable', 'date'],
        'car_photo' => ['nullable', 'image', 'max:2048'],
        'license_photo' => ['nullable', 'image', 'max:2048'],
        'id_photo' => ['nullable', 'image', 'max:2048'],
        'insurance_photo' => ['nullable', 'image', 'max:2048'],
    ]);

    // Ensure checkbox is handled
    if ($request->has('availability_status')) {
        $validated['availability_status'] = true;
    } elseif ($request->has('availability_status') === false) {
        $validated['availability_status'] = false;
    }

    // Handle optional file uploads
    foreach (['car_photo', 'license_photo', 'id_photo', 'insurance_photo'] as $photoField) {
        if ($request->hasFile($photoField)) {
            $path = $request->file($photoField)->store('drivers', 'public');
            $validated[$photoField] = $path;
        }
    }

    // Update only submitted fields
    $driver->update($validated);

    // Redirect back to driver list with success
    return redirect()->route('admin.drivers.index')
        ->with('success', 'Driver updated successfully.');
}



// ---------------------


public function driversForPassenger()
{
    $user = Auth::user();

    // Default fallback locations
    $defaultLocations = [
        ['lat' => 33.8938, 'lng' => 35.5018],
        ['lat' => 34.0058, 'lng' => 36.2181],
        ['lat' => 33.8500, 'lng' => 35.9000],
        ['lat' => 34.4367, 'lng' => 35.8497],
        ['lat' => 33.5606, 'lng' => 35.3756],
        ['lat' => 33.2734, 'lng' => 35.1939],
        ['lat' => 33.9700, 'lng' => 35.9000],
        ['lat' => 34.1200, 'lng' => 35.6500],
    ];

    // Only drivers who have accepted a ride for this passenger
    $drivers = Driver::whereHas('rides', function($q) use ($user) {
            $q->where('passenger_id', $user->id)
              ->where('status', 'accepted'); // or your ride status code
        })
        ->with(['user', 'rides' => fn($q) => $q->latest()->limit(1), 'rides.rideLogs'])
        ->get()
        ->map(function ($driver) use ($defaultLocations) {
            $latestRide = $driver->rides->first();
            $latestRideLog = $latestRide?->rideLogs->last();

            $fallback = $defaultLocations[array_rand($defaultLocations)];
            $currentLat = $latestRideLog->driver_lat ?? $driver->current_driver_lat ?? $fallback['lat'];
            $currentLng = $latestRideLog->driver_lng ?? $driver->current_driver_lng ?? $fallback['lng'];

            $routePolyline = null;
            if ($latestRideLog) {
                $coords = $this->getRoutePolyline(
                    $latestRideLog->start_lat,
                    $latestRideLog->start_lng,
                    $latestRideLog->end_lat,
                    $latestRideLog->end_lng
                );
                $routePolyline = $this->encodePolyline($coords);
            }

            return [
                'id' => $driver->id,
                'name' => $driver->user->name ?? null,
                'vehicle_type' => $driver->vehicle_type,
                'vehicle_number' => $driver->vehicle_number,
                'current_driver_lat' => $currentLat,
                'current_driver_lng' => $currentLng,
                'availability_status' => $driver->availability_status,
                'rating' => $driver->rating,
                'current_route' => $routePolyline,
                'ride_id' => $latestRide?->id,
            ];
        });

    return response()->json($drivers);
}


    // ---------------------
    // Go online
    // ---------------------
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

        $latestRideLog = $driver->rides()->latest()->first()?->rideLogs->last();
        $routePolyline = null;

        if ($latestRideLog) {
            $coords = $this->getRoutePolyline(
                $latestRideLog->start_lat,
                $latestRideLog->start_lng,
                $latestRideLog->end_lat,
                $latestRideLog->end_lng
            );
            $routePolyline = $this->encodePolyline($coords);
        }

        broadcast(new DriverLocationUpdated($driver, $latestRideLog?->ride_id, $routePolyline))->toOthers();

        return response()->json(['message' => 'Driver online', 'driver' => $driver]);
    }

    // ---------------------
    // Go offline
    // ---------------------
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

        $latestRideLog = $driver->rides()->latest()->first()?->rideLogs->last();
        $routePolyline = null;

        if ($latestRideLog) {
            $coords = $this->getRoutePolyline(
                $latestRideLog->start_lat,
                $latestRideLog->start_lng,
                $latestRideLog->end_lat,
                $latestRideLog->end_lng
            );
            $routePolyline = $this->encodePolyline($coords);
        }

        broadcast(new DriverLocationUpdated($driver, $latestRideLog?->ride_id, $routePolyline))->toOthers();

        return response()->json(['message' => 'Driver offline', 'driver' => $driver]);
    }

    // ---------------------
    // Update location
    // ---------------------
    

    public function updateLocation(Request $request, Driver $driver, $ride_id = null)
    {
        $this->authorizeDriver($driver); // driver or admin check

        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $driver->update([
            'current_driver_lat' => $request->lat,
            'current_driver_lng' => $request->lng,
        ]);

        // Only broadcast ride polyline if ride exists
        $ride = $ride_id ? Ride::find($ride_id) : $driver->rides()->latest()->first();
        $routePolyline = null;
        $rideLog = $ride?->rideLogs->last();

        if ($rideLog) {
            $coords = $this->getRoutePolyline(
                $rideLog->start_lat,
                $rideLog->start_lng,
                $rideLog->end_lat,
                $rideLog->end_lng
            );
            $routePolyline = $this->encodePolyline($coords);
        }

        broadcast(new DriverLocationUpdated($driver, $ride?->id, $routePolyline))->toOthers();

        return response()->json([
            'message' => 'Location updated & broadcasted',
            'lat' => $driver->current_driver_lat,
            'lng' => $driver->current_driver_lng,
            'ride_id' => $ride?->id,
            'current_route' => $routePolyline ?: null,
        ]);
    }
    // ---------------------
    // Update scanning range
    // ---------------------
    public function updateRange(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $request->validate([
            'scanning_range_km' => 'required|numeric|min:1|max:50',
        ]);

        $driver->update([
            'scanning_range_km' => $request->scanning_range_km,
        ]);

        return response()->json([
            'message' => 'Scanning range updated',
            'range' => $driver->scanning_range_km,
        ]);
    }

    // ---------------------
    // Driver activity logs
    // ---------------------
    public function activityLogs(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $logs = DriverActiveDuration::where('driver_id', $driver->id)
            ->orderByDesc('active_at')
            ->get()
            ->map(function ($log) {
                return [
                    'active_at' => $log->active_at->toDateTimeString(),
                    'inactive_at' => $log->inactive_at?->toDateTimeString(),
                    'duration_seconds' => $log->duration_seconds,
                    'duration_human' => gmdate("H:i:s", $log->duration_seconds ?? 0),
                ];
            });

        return response()->json($logs);
    }

    // ---------------------
    // Live status
    // ---------------------
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

    // ---------------------
    // Helper: authorize driver
    // ---------------------
    private function authorizeDriver(Driver $driver)
{
    if (Auth::user()->role !== 'admin' && $driver->user_id !== Auth::id()) {
        abort(response()->json(['message' => 'Unauthorized'], 403));
    }
}
    public function indexAdmin()
            {
                $drivers = Driver::with('user')->get();
                return view('admin.drivers.index', compact('drivers'));
            }


    // ---------------------
    // Polyline helpers
    // ---------------------
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
}

