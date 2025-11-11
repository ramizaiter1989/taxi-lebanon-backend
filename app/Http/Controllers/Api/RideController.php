<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Models\Driver;
use App\Events\DriverLocationUpdated;
use App\Events\RideRequested;
use App\Events\RideAccepted;
use App\Events\RideRemoved;
use App\Events\RideCancelled;
use App\Traits\PolylineTrait;
use App\Notifications\RideNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\RideResource;
use App\Services\GeocodingService;
use App\Services\RouteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // ✅ ADDED: Import Auth facade

class RideController extends Controller
{
    use PolylineTrait;

    public function __construct()
    {
        if (!env('ORS_API_KEY')) {
            throw new \RuntimeException('ORS_API_KEY is not set in .env');
        }
    }

    // Passenger requests a ride
    public function store(Request $request)
    {
        $passenger = $request->user();
        // Prevent multiple active rides
        $activeRide = Ride::where('passenger_id', $passenger->id)
            ->whereIn('status', ['pending', 'accepted', 'in_progress', 'arrived'])
            ->first();
        if ($activeRide) {
            return response()->json(['error' => 'You already have an active ride.'], 403);
        }
        // Validate request
        $data = $request->validate([
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|numeric|min:0',
            'fare' => 'nullable|numeric|min:0',
            'is_pool' => 'sometimes|boolean',
        ]);
        // Compute distance/duration if not provided
        if (empty($data['distance']) || empty($data['duration'])) {
            try {
                $routeService = app(RouteService::class);
                $trip = $routeService->getRouteInfo(
                    $data['origin_lat'],
                    $data['origin_lng'],
                    $data['destination_lat'],
                    $data['destination_lng']
                );
                Log::info('RouteService response:', ['trip' => $trip]);
                if ($trip && isset($trip['distance'], $trip['duration'])) {
                    $data['distance'] = $trip['distance'] / 1000.0;
                    $data['duration'] = $trip['duration'] / 60.0;
                } else {
                    Log::error('RouteService failed to calculate route.', [
                        'origin' => [$data['origin_lat'], $data['origin_lng']],
                        'destination' => [$data['destination_lat'], $data['destination_lng']],
                    ]);
                    return response()->json(['error' => 'Unable to calculate route. Please try again.'], 400);
                }
            } catch (\Exception $e) {
                Log::error('RouteService error:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Unable to calculate route. Please try again.'], 500);
            }
        }
        // Create ride
        $ride = Ride::create(array_merge([
            'passenger_id' => $passenger->id,
            'origin_lat' => $data['origin_lat'],
            'origin_lng' => $data['origin_lng'],
            'destination_lat' => $data['destination_lat'],
            'destination_lng' => $data['destination_lng'],
            'status' => 'pending',
        ], array_filter([
            'distance' => $data['distance'] ?? null,
            'duration' => $data['duration'] ?? null,
            'fare' => $data['fare'] ?? null,
            'is_pool' => $data['is_pool'] ?? false,
        ])));
        // Server-side fare validation and calculation
        if (!empty($ride->distance) && !empty($ride->duration)) {
            $expectedFare = $ride->calculateFare(false);
            if (!is_null($expectedFare)) {
                if ($request->filled('fare')) {
                    $frontendFare = (float)$request->input('fare');
                    $tolerancePercent = config('rides.fare_tolerance_percent', 2);
                    $allowedDiff = max(0.01, ($tolerancePercent / 100) * max($expectedFare, $frontendFare));
                    if (abs($frontendFare - $expectedFare) > $allowedDiff) {
                        Log::warning('Fare mismatch on ride create', [
                            'user_id' => $passenger->id,
                            'frontend_fare' => $frontendFare,
                            'expected_fare' => $expectedFare,
                            'difference' => abs($frontendFare - $expectedFare),
                        ]);
                        $ride->fare = $expectedFare;
                    } else {
                        $ride->fare = $frontendFare;
                    }
                } else {
                    $ride->fare = $expectedFare;
                }
                $ride->save();
            }
        }
        event(new RideRequested($ride));
        return response()->json([
            'message' => 'Ride requested successfully',
            'ride' => new RideResource($ride->load(['passenger'])),
        ], 201);
    }

    // GET /api/rides - live rides
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min((int)$request->query('per_page', 15), 100);
        $statusFilter = $request->query('status');
        $liveStatuses = ['pending', 'accepted', 'in_progress', 'arrived'];
        $query = $this->buildRideQuery($user, $liveStatuses);
        if ($statusFilter && in_array($statusFilter, $liveStatuses)) {
            $query->where('status', $statusFilter);
        }
        $rides = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return RideResource::collection($rides);
    }

    // GET /api/rides/history
    public function history(Request $request)
    {
        $user = $request->user();
        $perPage = min((int)$request->query('per_page', 15), 100);
        $statusFilter = $request->query('status');
        $from = $request->query('from');
        $to = $request->query('to');
        $historyStatuses = ['completed', 'cancelled'];
        $query = $this->buildRideQuery($user, $historyStatuses);
        if ($statusFilter && in_array($statusFilter, $historyStatuses)) {
            $query->where('status', $statusFilter);
        }
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        $rides = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return RideResource::collection($rides);
    }

    // GET /api/rides/{ride}
    public function show(Request $request, Ride $ride)
    {
        $user = $request->user();
        $isPassenger = $ride->passenger_id === $user->id;
        $isDriver = $user->driver && $ride->driver_id === $user->driver->id;
        $isAdmin = $user->role === 'admin';
        if (!($isPassenger || $isDriver || $isAdmin)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $ride->load(['passenger', 'driver.user']);
        return new RideResource($ride);
    }

    // GET /api/rides/live - Current active ride for passenger
    public function current(Request $request)
    {
        try {
            $user = $request->user();
            // Define allowed statuses based on role
            $allowedStatuses = match ($user->role) {
                'driver' => ['accepted', 'in_progress', 'arrived'],
                'passenger' => ['pending', 'accepted', 'in_progress', 'arrived'],
                default => [],
            };
            // If somehow not driver or passenger, just return null
            if (empty($allowedStatuses)) {
                return response()->json(['ride' => null]);
            }
            // Base query
            $query = Ride::query()
                ->whereIn('status', $allowedStatuses)
                ->with(['driver.user', 'passenger']);
            // Role-based filtering
            if ($user->role === 'driver') {
                $driver = $user->driver;
                if (!$driver) {
                    return response()->json(['ride' => null], 404);
                }
                $query->where('driver_id', $driver->id);
            } else {
                $query->where('passenger_id', $user->id);
            }
            $ride = $query->latest()->first();
            if (!$ride) {
                return response()->json(['ride' => null]);
            }
            return new RideResource($ride);
        } catch (\Exception $e) {
            \Log::error('Ride current error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch current ride',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
 * @param Request $request
 * @param GeocodingService $geocodingService
 * @return \Illuminate\Http\JsonResponse
 */
// GET /api/rides/available - Available rides for driver

public function availableRides(Request $request, GeocodingService $geocodingService)
{
    $user = Auth::user(); // ✅ Using Auth facade
    
    // ✅ Verify user is a driver
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can view available rides'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    // ✅ CRITICAL: Check if driver is online
    if (!$driver->availability_status) {
        return response()->json([
            'rides' => [],
            'message' => 'You must be online to see available rides',
            'driver_status' => 'offline'
        ], 200);
    }
    
    // ✅ Check if driver has location set
    if ($driver->current_driver_lat === null || $driver->current_driver_lng === null) {
        return response()->json([
            'rides' => [],
            'error' => 'Driver location not set. Please enable location services.',
            'driver_status' => 'online_no_location'
        ], 400);
    }
    
    // ✅ Check if driver already has an active ride
    $activeRide = Ride::where('driver_id', $driver->id)
        ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
        ->exists();
    
    if ($activeRide) {
        return response()->json([
            'rides' => [],
            'message' => 'You already have an active ride',
            'driver_status' => 'active_ride'
        ], 200);
    }
    
    $scanningRange = $driver->scanning_range_km ?? config('rides.default_scanning_range', 10);
    
    // Get ride IDs that this driver has already declined
    $declinedRideIds = \App\Models\RideDecline::where('driver_id', $driver->id)
        ->pluck('ride_id')
        ->toArray();
    
    // Get blocked passenger IDs
    $blockedPassengerIds = \App\Models\DriverBlockedPassenger::where('driver_id', $driver->id)
        ->pluck('passenger_id')
        ->toArray();
    
    \Log::info('Fetching available rides', [
        'driver_id' => $driver->id,
        'online' => $driver->availability_status,
        'location' => [$driver->current_driver_lat, $driver->current_driver_lng],
        'scanning_range' => $scanningRange,
        'declined_rides' => count($declinedRideIds),
        'blocked_passengers' => count($blockedPassengerIds),
    ]);
    
    // Build the query
    $query = Ride::where('status', 'pending')
        ->whereNull('driver_id')
        ->select('*')
        ->selectRaw('
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(origin_lat)) *
                cos(radians(origin_lng) - radians(?)) +
                sin(radians(?)) *
                sin(radians(origin_lat))
            )) as distance_to_pickup', [
            $driver->current_driver_lat,
            $driver->current_driver_lng,
            $driver->current_driver_lat
        ]);
    
    // Filter out declined rides
    if (!empty($declinedRideIds)) {
        $query->whereNotIn('id', $declinedRideIds);
    }
    
    // Filter out blocked passengers
    if (!empty($blockedPassengerIds)) {
        $query->whereNotIn('passenger_id', $blockedPassengerIds);
    }
    
    $rides = $query
        ->having('distance_to_pickup', '<=', $scanningRange)
        ->orderBy('distance_to_pickup', 'asc')
        ->with('passenger:id,name,phone,current_lat,current_lng')
        ->limit(20)
        ->get();
    
    \Log::info('Available rides found', [
        'driver_id' => $driver->id,
        'total_rides' => $rides->count(),
        'ride_ids' => $rides->pluck('id')->toArray()
    ]);
    
    // Batch geocoding to reduce API calls
    $coordinates = [];
    foreach ($rides as $ride) {
        $coordinates[] = [$ride->origin_lat, $ride->origin_lng];
        $coordinates[] = [$ride->destination_lat, $ride->destination_lng];
    }
    
    $addresses = Cache::remember(
        'geocode_' . md5(serialize($coordinates)),
        now()->addHours(6),
        function () use ($geocodingService, $coordinates) {
            return $geocodingService->batchGetAddresses($coordinates);
        }
    );
    
    $ridesWithAddresses = $rides->map(function ($ride, $index) use ($addresses) {
        $originIndex = $index * 2;
        $destIndex = $originIndex + 1;
        return array_merge($ride->toArray(), [
            'origin_address' => $addresses[$originIndex] ?? 'Address unavailable',
            'destination_address' => $addresses[$destIndex] ?? 'Address unavailable',
            'distance_to_pickup_km' => round($ride->distance_to_pickup, 2),
        ]);
    });
    
    return response()->json([
        'rides' => $ridesWithAddresses,
        'driver_status' => 'online',
        'scanning_range_km' => $scanningRange,
        'driver_location' => [
            'lat' => $driver->current_driver_lat,
            'lng' => $driver->current_driver_lng,
        ],
        'total_available' => $ridesWithAddresses->count(),
    ]);
}

// POST /api/rides/{ride}/accept - Accept ride
public function acceptRide(Request $request, Ride $ride)
{
    $user = Auth::user(); // ✅ Using Auth facade
    
    // ✅ Verify user is a driver
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can accept rides'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    // ✅ CRITICAL: Verify driver is online
    if (!$driver->availability_status) {
        return response()->json([
            'error' => 'You must be online to accept rides. Please go online first.'
        ], 400);
    }
    
    // Check if ride is still available
    DB::beginTransaction();
    try {
        $ride = Ride::where('id', $ride->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();
        
        if (!$ride) {
            DB::rollBack();
            return response()->json(['error' => 'Ride is no longer available'], 409);
        }
        
        if ($ride->driver_id && $ride->driver_id !== $driver->id) {
            DB::rollBack();
            return response()->json(['error' => 'Ride already assigned to another driver'], 409);
        }
        
        // ✅ Check if driver already has an active ride
        $activeRide = Ride::where('driver_id', $driver->id)
            ->whereIn('status', ['accepted', 'in_progress', 'arrived'])
            ->where('id', '!=', $ride->id)
            ->exists();
        
        if ($activeRide) {
            DB::rollBack();
            return response()->json(['error' => 'You already have an active ride'], 400);
        }
        
        // ✅ Verify driver is within acceptable range
        if ($driver->current_driver_lat && $driver->current_driver_lng) {
            $distance = $this->calculateDistance(
                $driver->current_driver_lat,
                $driver->current_driver_lng,
                $ride->origin_lat,
                $ride->origin_lng
            );
            $maxAcceptanceRange = config('rides.max_acceptance_range_km', 15);
            if ($distance > $maxAcceptanceRange) {
                DB::rollBack();
                return response()->json([
                    'error' => 'You are too far from the pickup location',
                    'distance_km' => round($distance, 2),
                    'max_range_km' => $maxAcceptanceRange
                ], 400);
            }
        }
        
        // ✅ Update ride status to accepted
        $ride->driver_id = $driver->id;
        $ride->status = 'accepted';
        $ride->accepted_at = now();
        $ride->save();
        
        // ✅ CRITICAL: Keep driver OFFLINE after accepting (he's now busy with this ride)
        // Driver will only go back online after completing/cancelling the ride
        $driver->availability_status = false;
        $driver->save();
        
        // Calculate ETA
        $estimatedTime = $this->calculateEstimatedTimeToPickup($ride, $driver);
        $distanceToPickup = $this->calculateDistanceToPickup($ride, $driver);
        
        // Notify passenger
        $ride->passenger->notify(new RideNotification(
            'Ride Accepted',
            'Your ride has been accepted by a driver.',
            [
                'type' => 'ride_accepted',
                'ride_id' => $ride->id,
                'driver_id' => $driver->id,
                'driver_name' => $driver->user->name,
                'driver_phone' => $driver->user->phone,
                'driver_lat' => $driver->current_driver_lat,
                'driver_lng' => $driver->current_driver_lng,
                'vehicle_type' => $driver->vehicle_type,
                'vehicle_plate' => $driver->license_plate ?? $driver->vehicle_number,
                'estimated_time' => $estimatedTime,
                'distance' => $distanceToPickup,
            ]
        ));
        
        RideAccepted::dispatch($ride->load(['driver.user', 'passenger']), $driver);
        broadcast(new RideRemoved($ride->id))->toOthers();
        
        DB::commit();
        
        \Log::info('Ride accepted successfully', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'driver_now_offline' => !$driver->availability_status,
        ]);
        
        return response()->json([
            'message' => 'Ride accepted successfully',
            'ride' => new RideResource($ride->load(['driver.user', 'passenger'])),
            'driver_status' => 'offline', // Driver is now busy with this ride
            'pickup_eta' => [
                'distance_km' => $distanceToPickup,
                'duration_minutes' => $estimatedTime,
            ],
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error accepting ride', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => 'Failed to accept ride. Please try again.'], 500);
    }
}

// POST /api/rides/{ride}/decline
public function declineRide(Request $request, Ride $ride)
{
    $user = Auth::user(); // ✅ Using Auth facade
    
    // ✅ Verify user is a driver
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can decline rides'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    // ✅ CRITICAL: Verify driver is online (can't decline if offline)
    if (!$driver->availability_status) {
        return response()->json([
            'error' => 'Cannot decline rides while offline'
        ], 400);
    }
    
    // If driver is assigned or ride not pending, block decline
    if ($ride->driver_id || $ride->status !== 'pending') {
        return response()->json(['error' => 'Ride is no longer available to decline'], 409);
    }
    
    // Record decline (unique constraint in migration prevents duplicates)
    try {
        \App\Models\RideDecline::firstOrCreate([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
        ]);
        
        \Log::info('Ride declined by driver', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
        ]);
        
        // DO NOT broadcast RideRemoved here - only the declining driver should not see it
        // Other drivers should still be able to see and accept this ride
        
        return response()->json([
            'message' => 'Ride declined successfully',
            'driver_status' => 'online' // Driver remains online after declining
        ]);
    } catch (\Exception $e) {
        \Log::error('Error declining ride', [
            'ride' => $ride->id, 
            'driver' => $driver->id, 
            'err' => $e->getMessage()
        ]);
        return response()->json(['error' => 'Failed to decline ride'], 500);
    }
}

// POST /api/rides/{ride}/complete - Complete ride
public function completeRide(Request $request, Ride $ride)
{
    $user = Auth::user(); // ✅ Using Auth facade
    
    // ✅ Verify user is a driver
    if ($user->role !== 'driver') {
        return response()->json(['error' => 'Only drivers can complete rides'], 403);
    }
    
    $driver = $user->driver;
    
    if (!$driver) {
        return response()->json(['error' => 'Driver profile not found'], 404);
    }
    
    if ($ride->driver_id !== $driver->id) {
        return response()->json(['error' => 'Only assigned driver can complete the ride'], 403);
    }
    
    if (!in_array($ride->status, ['in_progress', 'arrived'])) {
        return response()->json(['error' => 'Ride must be in progress to complete'], 400);
    }
    
    DB::beginTransaction();
    try {
        $ride->status = 'completed';
        $ride->completed_at = now();
        
        // Recalculate final fare
        if (!empty($ride->distance) && !empty($ride->duration)) {
            $finalFare = $ride->calculateFare(true);
            if ($ride->fare && abs($finalFare - $ride->fare) > ($ride->fare * 0.1)) {
                Log::warning('Significant fare change at completion', [
                    'ride_id' => $ride->id,
                    'initial_fare' => $ride->fare,
                    'final_fare' => $finalFare,
                ]);
            }
            $ride->fare = $finalFare;
        }
        
        $ride->save();
        
        // ✅ CRITICAL: Set driver back to OFFLINE (not online)
        // Driver must manually go online again to accept new rides
        $driver->availability_status = false;
        $driver->save();
        
        // Notify passenger
        $ride->passenger->notify(new RideNotification(
            'Ride Completed',
            'Your ride has been completed. Thank you for riding with us!',
            [
                'type' => 'ride_completed',
                'ride_id' => $ride->id,
                'fare' => $ride->fare,
                'duration' => $ride->started_at ? now()->diffInMinutes($ride->started_at) : null,
            ]
        ));
        
        DB::commit();
        
        \Log::info('Ride completed successfully', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'driver_now_offline' => !$driver->availability_status,
        ]);
        
        return response()->json([
            'message' => 'Ride completed successfully',
            'ride' => new RideResource($ride->load(['driver.user', 'passenger'])),
            'driver_status' => 'offline', // Driver is now offline
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error completing ride', [
            'ride_id' => $ride->id,
            'error' => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Failed to complete ride. Please try again.'], 500);
    }
}

// POST /api/rides/{ride}/cancel - Passenger or driver cancels a ride
public function cancelRide(Request $request, Ride $ride)
{
    $user = Auth::user(); // ✅ Using Auth facade
    $isPassenger = $ride->passenger_id === $user->id;
    $isDriver = $user->driver && $ride->driver_id === $user->driver->id;
    
    if (!$isPassenger && !$isDriver) {
        return response()->json(['error' => 'Unauthorized to cancel this ride'], 403);
    }
    
    // Cannot cancel completed or already cancelled rides
    if (in_array($ride->status, ['completed', 'cancelled'])) {
        return response()->json(['error' => 'Cannot cancel a ' . $ride->status . ' ride'], 400);
    }
    
    $data = $request->validate([
        'reason' => 'required|string|in:driver_no_show,wrong_location,changed_mind,too_expensive,emergency,other',
        'note' => 'nullable|string|max:200',
    ]);
    
    DB::beginTransaction();
    try {
        // Update ride status
        $ride->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['reason'],
            'cancellation_note' => $data['note'],
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);
        
        // ✅ CRITICAL: Make driver OFFLINE (not available) after cancellation
        // Driver must manually go online again
        if ($isDriver && $ride->driver) {
            $ride->driver->availability_status = false;
            $ride->driver->save();
            
            \Log::info('Driver set to offline after cancellation', [
                'ride_id' => $ride->id,
                'driver_id' => $ride->driver->id,
            ]);
        }
        
        // Notify other party (if they exist)
        if ($isPassenger && $ride->driver && $ride->driver->user) {
            $ride->driver->user->notify(new RideNotification(
                'Ride Cancelled',
                'The passenger has cancelled the ride.',
                [
                    'type' => 'ride_cancelled',
                    'ride_id' => $ride->id,
                    'cancelled_by' => 'passenger',
                    'reason' => $data['reason'],
                ]
            ));
        } elseif ($isDriver && $ride->passenger) {
            $ride->passenger->notify(new RideNotification(
                'Ride Cancelled',
                'The driver has cancelled the ride.',
                [
                    'type' => 'ride_cancelled',
                    'ride_id' => $ride->id,
                    'cancelled_by' => 'driver',
                    'reason' => $data['reason'],
                ]
            ));
        }
        
        // Broadcast event safely
        if (class_exists(RideCancelled::class)) {
            broadcast(new RideCancelled($ride, $user))->toOthers();
        }
        
        DB::commit();
        
        return response()->json([
            'message' => 'Ride cancelled successfully',
            'ride' => new RideResource($ride->load(['driver.user', 'passenger'])),
            'driver_status' => $isDriver ? 'offline' : null, // Driver is now offline
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error cancelling ride', [
            'ride_id' => $ride->id,
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Failed to cancel ride. Please try again.'], 500);
    }
}

    // POST /api/rides/{ride}/start - Start ride (driver picked up passenger)
    public function startRide(Request $request, Ride $ride)
    {
        $driver = $request->user()->driver;
        if (!$driver || $ride->driver_id !== $driver->id) {
            return response()->json(['error' => 'Only assigned driver can start the ride'], 403);
        }
        $validTransitions = [
            'arrived' => ['in_progress'],
        ];
        if (!isset($validTransitions[$ride->status]) || !in_array('in_progress', $validTransitions[$ride->status])) {
            return response()->json(['error' => 'Invalid ride status transition'], 400);
        }
        $ride->status = 'in_progress';
        $ride->started_at = now();
        $ride->save();
        $ride->passenger->notify(new RideNotification(
            'Ride Started',
            'Your ride has started.',
            ['type' => 'ride_started', 'ride_id' => $ride->id]
        ));
        return response()->json([
            'message' => 'Ride started successfully',
            'ride' => new RideResource($ride->load(['driver.user', 'passenger'])),
        ]);
    }

    // POST /api/rides/{ride}/arrived - Mark ride as arrived at passenger
    public function markArrived(Request $request, Ride $ride)
    {
        $driver = $request->user()->driver;
        if (!$driver || $ride->driver_id !== $driver->id) {
            return response()->json(['error' => 'Only assigned driver can mark arrival'], 403);
        }
        $validTransitions = [
            'accepted' => ['arrived'],
        ];
        if (!isset($validTransitions[$ride->status]) || !in_array('arrived', $validTransitions[$ride->status])) {
            return response()->json(['error' => 'Invalid ride status transition'], 400);
        }
        $ride->status = 'arrived';
        $ride->arrived_at = now();
        $ride->save();
        $ride->passenger->notify(new RideNotification(
            'Driver Arrived',
            'Your driver has arrived at the pickup location.',
            [
                'type' => 'driver_arrived',
                'ride_id' => $ride->id,
                'driver_name' => $driver->user->name,
            ]
        ));
        return response()->json([
            'message' => 'Arrival marked successfully',
            'ride' => new RideResource($ride->load(['driver.user', 'passenger'])),
        ]);
    }

    // PATCH /api/rides/{ride}/location - Update driver location during a ride
    public function updateLocation(Request $request, Ride $ride)
    {
        $driver = $request->user()->driver;
        if (!$driver || $ride->driver_id !== $driver->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        // Only allow location updates for active rides
        if (!in_array($ride->status, ['accepted', 'in_progress', 'arrived'])) {
            return response()->json(['error' => 'Cannot update location for this ride status'], 400);
        }
        $data = $request->validate([
            'driver_lat' => 'required|numeric|between:-90,90',
            'driver_lng' => 'required|numeric|between:-180,180',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0',
        ]);
        // Update ride's current driver location
        $ride->update([
            'current_driver_lat' => $data['driver_lat'],
            'current_driver_lng' => $data['driver_lng'],
        ]);
        // Also update driver's global location
        $driver->update([
            'current_driver_lat' => $data['driver_lat'],
            'current_driver_lng' => $data['driver_lng'],
        ]);
        // Log location for tracking (if rideLogs relation exists)
        if (method_exists($ride, 'rideLogs')) {
            $ride->rideLogs()->create([
                'driver_lat' => $data['driver_lat'],
                'driver_lng' => $data['driver_lng'],
                'heading' => $data['heading'] ?? null,
                'speed' => $data['speed'] ?? null,
                'timestamp' => now(),
            ]);
        }
        // Get route polyline
        $routePolyline = null;
        $destination = null;
        // Determine destination based on ride status
        if ($ride->status === 'accepted' || $ride->status === 'arrived') {
            // Driver is heading to pickup
            $destination = ['lat' => $ride->origin_lat, 'lng' => $ride->origin_lng];
        } elseif ($ride->status === 'in_progress') {
            // Driver is heading to destination
            $destination = ['lat' => $ride->destination_lat, 'lng' => $ride->destination_lng];
        }
        if ($destination) {
            try {
                $coords = $this->getRoutePolyline(
                    $data['driver_lat'],
                    $data['driver_lng'],
                    $destination['lat'],
                    $destination['lng']
                );
                $routePolyline = $this->encodePolyline($coords);
            } catch (\Exception $e) {
                Log::warning('Failed to get route polyline', [
                    'ride_id' => $ride->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        // Broadcast location update
        broadcast(new DriverLocationUpdated(
            $driver,
            $ride->id,
            $routePolyline,
            $ride
        ))->toOthers();
        return response()->json([
            'status' => 'location updated',
            'ride_id' => $ride->id,
            'driver_id' => $ride->driver_id,
            'current_driver_lat' => $ride->current_driver_lat,
            'current_driver_lng' => $ride->current_driver_lng,
            'current_route' => $routePolyline,
            'heading' => $data['heading'] ?? null,
        ]);
    }

    // Helper: Build ride query by role
    protected function buildRideQuery($user, array $statuses)
    {
        if ($user->role === 'passenger') {
            return Ride::where('passenger_id', $user->id)
                ->whereIn('status', $statuses)
                ->with(['driver.user', 'passenger']);
        } elseif ($user->role === 'driver') {
            $driver = $user->driver;
            if (!$driver) {
                abort(404, 'Driver profile not found');
            }
            return Ride::where('driver_id', $driver->id)
                ->whereIn('status', $statuses)
                ->with(['driver.user', 'passenger']);
        } else {
            // Admin or other roles
            return Ride::whereIn('status', $statuses)
                ->with(['driver.user', 'passenger']);
        }
    }

    // Calculate distance between two coordinates (Haversine formula)
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    // Calculate distance to pickup using ORS API (cached)
    protected function calculateDistanceToPickup(Ride $ride, Driver $driver)
    {
        $cacheKey = "distance_pickup_{$driver->id}_{$ride->id}";
        return Cache::remember($cacheKey, 300, function () use ($ride, $driver) {
            $maxRetries = 2;
            $retryDelay = 1;
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders(['Authorization' => env('ORS_API_KEY')])
                        ->get('https://api.openrouteservice.org/v2/directions/driving-car', [
                            'start' => "{$driver->current_driver_lng},{$driver->current_driver_lat}",
                            'end'   => "{$ride->origin_lng},{$ride->origin_lat}",
                        ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['features'][0]['properties']['segments'][0]['distance'])) {
                            return round($data['features'][0]['properties']['segments'][0]['distance'] / 1000, 2);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("ORS API attempt $i failed: " . $e->getMessage());
                    if ($i < $maxRetries - 1) {
                        sleep($retryDelay);
                        continue;
                    }
                }
            }
            Log::warning('Using Haversine fallback for distance calculation');
            return $this->calculateDistance(
                $driver->current_driver_lat,
                $driver->current_driver_lng,
                $ride->origin_lat,
                $ride->origin_lng
            );
        });
    }

    // Calculate estimated time to pickup using ORS API (cached)
    protected function calculateEstimatedTimeToPickup(Ride $ride, Driver $driver)
    {
        $cacheKey = "eta_pickup_{$driver->id}_{$ride->id}";
        return Cache::remember($cacheKey, 300, function () use ($ride, $driver) {
            $maxRetries = 2;
            $retryDelay = 1;
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders(['Authorization' => env('ORS_API_KEY')])
                        ->get('https://api.openrouteservice.org/v2/directions/driving-car', [
                            'start' => "{$driver->current_driver_lng},{$driver->current_driver_lat}",
                            'end'   => "{$ride->origin_lng},{$ride->origin_lat}",
                        ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['features'][0]['properties']['segments'][0]['duration'])) {
                            return round($data['features'][0]['properties']['segments'][0]['duration'] / 60, 1);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("ORS API attempt $i failed: " . $e->getMessage());
                    if ($i < $maxRetries - 1) {
                        sleep($retryDelay);
                        continue;
                    }
                }
            }
            // Fallback: approximate from distance
            $distance = $this->calculateDistanceToPickup($ride, $driver);
            $averageSpeed = config('rides.average_speed_kmh', 30);
            return $distance ? round(($distance / $averageSpeed) * 60, 1) : null;
        });
    }

}