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
use App\Traits\PolylineTrait;
use App\Notifications\RideNotification;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\RideResource;

class RideController extends Controller
{
    use PolylineTrait;
    /**
     * Passenger requests a ride
     */
    public function store(Request $request)
        {
            $passenger = $request->user();

    // Check if passenger already has an active ride
    $activeRide = Ride::where('passenger_id', $passenger->id)
        ->whereIn('status', ['pending', 'in_progress', 'arrived'])
        ->first();

    if ($activeRide) {
        return response()->json([
            'error' => 'You already have an active ride. Please complete or cancel it before requesting a new one.'
        ], 403);
    }
            $request->validate([
                'origin_lat' => 'required|numeric',
                'origin_lng' => 'required|numeric',
                'destination_lat' => 'required|numeric',
                'destination_lng' => 'required|numeric',
            ]);

            $ride = Ride::create([
                'passenger_id' => $request->user()->id,
                'origin_lat' => $request->origin_lat,
                'origin_lng' => $request->origin_lng,
                'destination_lat' => $request->destination_lat,
                'destination_lng' => $request->destination_lng,
                'status' => 'pending',
            ]);
            
            event(new RideRequested($ride));
            // Find nearby drivers (within 10 km)
            $nearbyDrivers = Driver::where('availability_status', true)
                ->whereNotNull('current_driver_lat')
                ->whereNotNull('current_driver_lng')
                ->select('*')
                ->selectRaw('
                    (6371 * acos(
                        cos(radians(?)) *
                        cos(radians(current_driver_lat)) *
                        cos(radians(current_driver_lng) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(current_driver_lat))
                    )) as distance', [
                    $request->origin_lat,
                    $request->origin_lng,
                    $request->origin_lat
                ])
                ->having('distance', '<=', 10)  // 10 km range
                ->with('user')
                ->get();

            // Notify nearby drivers
            foreach ($nearbyDrivers as $driver) {
                $driver->user->notify(new RideNotification(
                    'New Ride Request',
                    'A passenger nearby needs a ride',
                    [
                        'type' => 'new_ride',
                        'ride_id' => $ride->id,
                        'pickup_lat' => $ride->origin_lat,
                        'pickup_lng' => $ride->origin_lng
                    ]
                ));
            }

            // Broadcast the ride request to all drivers
            RideRequested::dispatch($ride);

            return response()->json($ride);
        }

/**
 * GET /api/rides
 *
 * Return "live" rides for the authenticated user.
 * - Passenger: their active rides (pending, accepted, in_progress, arrived)
 * - Driver: rides assigned to the driver that are active
 * Query params:
 *  - status (optional) single status to filter
 *  - page, per_page (pagination)
 */
public function index(Request $request)
{
    $user = $request->user();
    $perPage = (int) $request->query('per_page', 15);
    $statusFilter = $request->query('status');

    // Live statuses
    $liveStatuses = ['pending', 'accepted', 'in_progress', 'arrived'];

    if ($user->role === 'passenger') {
        $query = Ride::where('passenger_id', $user->id)
            ->whereIn('status', $liveStatuses)
            ->with(['driver.user', 'passenger']);
    } elseif ($user->role === 'driver') {
        $driver = $user->driver;
        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found'], 404);
        }
        $query = Ride::where('driver_id', $driver->id)
            ->whereIn('status', $liveStatuses)
            ->with(['driver.user', 'passenger']);
    } else {
        // admin or others: return all live rides (optional: restrict by permission)
        $query = Ride::whereIn('status', $liveStatuses)->with(['driver.user', 'passenger']);
    }

    if ($statusFilter) {
        $query->where('status', $statusFilter);
    }

    $rides = $query->orderBy('created_at', 'desc')->paginate($perPage);

    return response()->json($rides);
}

/**
 * GET /api/rides/history
 *
 * Return historical rides for the authenticated user (completed / cancelled).
 * Supports pagination and optional date range or status.
 */
public function history(Request $request)
{
    $user = $request->user();
    $perPage = (int) $request->query('per_page', 15);
    $statusFilter = $request->query('status'); // e.g., 'completed' or 'cancelled'
    $from = $request->query('from'); // optional ISO date
    $to = $request->query('to');     // optional ISO date

    $historyStatuses = ['arrived', 'cancelled']; // adjust if completed stored separately

    if ($user->role === 'passenger') {
        $query = Ride::where('passenger_id', $user->id)
            ->whereIn('status', $historyStatuses)
            ->with(['driver.user', 'passenger']);
    } elseif ($user->role === 'driver') {
        $driver = $user->driver;
        if (!$driver) {
            return response()->json(['error' => 'Driver profile not found'], 404);
        }
        $query = Ride::where('driver_id', $driver->id)
            ->whereIn('status', $historyStatuses)
            ->with(['driver.user', 'passenger']);
    } else {
        $query = Ride::whereIn('status', $historyStatuses)->with(['driver.user', 'passenger']);
    }

    if ($statusFilter) $query->where('status', $statusFilter);
    if ($from) $query->where('created_at', '>=', $from);
    if ($to) $query->where('created_at', '<=', $to);

    $rides = $query->orderBy('arrived_at', 'desc')->paginate($perPage);

    $rides = Ride::with(['driver.user', 'passenger'])->paginate(15);
    return RideResource::collection($rides);
}

/**
 * GET /api/rides/{ride}
 *
 * Show a single ride. Only passenger, assigned driver or admin may view.
 */
public function show(Request $request, Ride $ride)
{
    $user = $request->user();

    // Authorization: passenger, driver, or admin
    $isPassenger = $ride->passenger_id === $user->id;
    $isDriver = $user->driver && $ride->driver_id === $user->driver->id;
    $isAdmin  = $user->role === 'admin';

    if (!($isPassenger || $isDriver || $isAdmin)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $ride->load(['passenger', 'driver.user']);

    // Optionally include ride logs, polyline, fare details
    // $ride->load('rideLogs');

    return response()->json($ride);
}
/**
 * GET /api/rides/live
 *
 * Return the current "live" ride for the authenticated user (passenger or driver).
 * If no active ride, return null.
 */
public function current(Request $request)
{
    $user = $request->user();
    $ride = Ride::where('passenger_id', $user->id)
        ->whereIn('status', ['pending', 'accepted', 'in_progress', 'arrived'])
        ->latest()
        ->with(['driver.user', 'passenger'])
        ->first();

    return $ride ? new RideResource($ride) : response()->json(['ride' => null]);
}

    /**
     * Driver sees available rides within their scanning range
     */
            public function availableRides(Request $request)
        {
            $driver = $request->user()->driver;

            if (!$driver) {
                return response()->json(['error' => 'Only drivers can pick rides'], 403);
            }

            if ($driver->current_driver_lat === null || $driver->current_driver_lng=== null ) {
                return response()->json([
                    'error' => 'Driver location not set. Please update your location first.'
                ], 400);
            }

            // Only show pending rides that are not assigned to any driver
            $rides = Ride::where('status', 'pending')
                ->whereNull('driver_id')
                ->select('*')
                ->selectRaw('
                    (6371 * acos(
                        cos(radians(?)) *
                        cos(radians(origin_lat)) *
                        cos(radians(origin_lng) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(origin_lat))
                    )) as distance', [
                    $driver->current_driver_lat,
                    $driver->current_driver_lng,
                    $driver->current_driver_lat
                ])
                ->having('distance', '<=', $driver->scanning_range_km ?? 10)
                ->with('passenger')
                ->get();

            return response()->json($rides);
        }


    /**
     * Driver accepts a ride
     */
    public function acceptRide(Request $request, Ride $ride)
{
    $driver = $request->user()->driver;

    if (!$driver) {
        return response()->json(['error' => 'Only drivers can accept rides'], 403);
    }

    if ($ride->driver_id && $ride->driver_id !== $driver->id) {
        return response()->json(['error' => 'Ride already assigned to another driver'], 403);
    }

    // Assign the ride to the driver and update status
    $ride->driver_id = $driver->id;
    $ride->status = 'in_progress';
    $ride->started_at = now();
    $ride->save();

    // Notify the passenger about the driver's acceptance
    $passenger = $ride->passenger;
    $passenger->notify(new RideNotification(
        'Ride Accepted',
        'A driver has accepted your ride request.',
        [
            'type' => 'ride_accepted',
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'driver_name' => $driver->user->name,
            'driver_lat' => $driver->current_driver_lat,
            'driver_lng' => $driver->current_driver_lng,
            'estimated_time' => $this->calculateEstimatedTimeToPickup($ride, $driver),
            'distance' => $this->calculateDistanceToPickup($ride, $driver),
        ]
    ));

    // Broadcast the ride acceptance to the passenger
    RideAccepted::dispatch($ride, $driver);

    // Broadcast to all drivers to remove this ride from their list
    broadcast(new RideRemoved($ride->id))->toOthers();

    return response()->json($ride);
}


    /**
     * Driver marks ride as arrived
     */
    public function markArrived(Request $request, Ride $ride)
    {
        $driver = $request->user()->driver;
        if (!$driver || $ride->driver_id !== $driver->id) {
            return response()->json(['error' => 'Only assigned driver can mark arrival'], 403);
        }

        $ride->status = 'arrived'; // Observer sets arrived_at
        $ride->save();
        // In markArrived method:
        $ride->passenger->notify(new RideNotification(
            'Driver Arrived',
            'Your driver has arrived at the pickup location',
            ['type' => 'driver_arrived', 'ride_id' => $ride->id]
        ));
        // Calculate fare dynamically
        $this->calculateFare($ride);
        
        $ride->passenger->notify(new RideNotification(
        'Driver Arrived',
        'Your driver has arrived at the pickup location',
        [
            'type' => 'driver_arrived',
            'ride_id' => $ride->id
        ]
    ));
    
    return response()->json($ride);
    
    }
    public function estimateFare(Request $request)
    {
        $request->validate([
            'distance' => 'required|numeric|min:0',
            'duration' => 'required|numeric|min:0',
        ]);

        $settings = \App\Models\FareSettings::first();

        $fare = $settings->base_fare
              + ($request->distance * $settings->per_km_rate)
              + ($request->duration * $settings->per_minute_rate);

        if ($fare < $settings->minimum_fare) $fare = $settings->minimum_fare;
        $fare *= $settings->peak_multiplier;

        return response()->json([
            'estimated_fare' => round($fare, 2)
        ]);
    }

    /**
 * Update ride status
 */
public function updateStatus(Request $request, Ride $ride)
{
    $user = $request->user();
    
    // Authorization: Only passenger, assigned driver, or admin can update
    if ($ride->passenger_id !== $user->id && 
        $ride->driver_id !== $user->driver?->id && 
        $user->role !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $request->validate([
        'status' => 'required|in:pending,accepted,in_progress,arrived,cancelled',
    ]);

    $ride->update([
        'status' => $request->status
    ]);

    return response()->json([
        'message' => 'Ride status updated successfully',
        'ride' => $ride
    ]);
}
// RideController
public function scheduleRide(Request $request)
{
    $request->validate([
        'scheduled_at' => 'required|date|after:now'
    ]);
    
    Ride::create([
        'scheduled_at' => $request->scheduled_at,
        'status' => 'scheduled'
    ]);
}

// Apply promo code to ride
/**
 * Apply promo code
 */
public function applyPromoCode(Request $request, Ride $ride)
{
    $request->validate([
        'code' => 'required|string'
    ]);

    $promo = \App\Models\PromoCode::where('code', strtoupper($request->code))->first();

    if (!$promo) {
        return response()->json(['error' => 'Invalid promo code'], 400);
    }

    if (!$promo->isValid($ride->fare)) {
        return response()->json(['error' => 'Promo code is not valid or expired'], 400);
    }

    $discount = $promo->calculateDiscount($ride->fare);

    $ride->update([
        'promo_code_id' => $promo->id,
        'discount' => $discount,
        'final_fare' => $ride->fare - $discount
    ]);

    $promo->increment('used_count');

    return response()->json([
        'message' => 'Promo code applied successfully',
        'discount' => $discount,
        'final_fare' => $ride->final_fare
    ]);
}

/**
 * Request pool ride (ride sharing)
 */
public function requestPoolRide(Request $request)
{
    $request->validate([
        'origin_lat' => 'required|numeric',
        'origin_lng' => 'required|numeric',
        'destination_lat' => 'required|numeric',
        'destination_lng' => 'required|numeric',
    ]);

    // Find matching pool rides within 5km radius going same direction
    $existingPoolRides = Ride::where('status', 'pending')
        ->where('is_pool', true)
        ->whereRaw('
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(origin_lat)) *
                cos(radians(origin_lng) - radians(?)) +
                sin(radians(?)) *
                sin(radians(origin_lat))
            )) <= 5
        ', [
            $request->origin_lat,
            $request->origin_lng,
            $request->origin_lat
        ])
        ->get();

    // Create new pool ride
    $ride = Ride::create([
        'passenger_id' => $request->user()->id,
        'origin_lat' => $request->origin_lat,
        'origin_lng' => $request->origin_lng,
        'destination_lat' => $request->destination_lat,
        'destination_lng' => $request->destination_lng,
        'status' => 'pending',
        'is_pool' => true,
        'pool_discount_percentage' => 30 // 30% discount for pool rides
    ]);

    return response()->json([
        'ride' => $ride,
        'matching_rides' => $existingPoolRides->count(),
        'estimated_savings' => '30%'
    ]);
}
    /**
     * Passenger or driver cancels a ride
     */
   public function cancelRide(Request $request, Ride $ride)
{
    $request->validate([
        'reason' => 'required|string|in:driver_no_show,wrong_location,changed_mind,too_expensive,other',
        'note' => 'nullable|string|max:200'
    ]);
    
    $ride->update([
        'status' => 'cancelled',
        'cancellation_reason' => $request->reason,
        'cancellation_note' => $request->note,
        'cancelled_by' => $request->user()->id
    ]);
}

    /**
     * Update driver location during a ride
     */
    public function updateLocation(Request $request, Ride $ride)
{
    
    $driver = $request->user()->driver;

    if (!$driver || $ride->driver_id !== $driver->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $data = $request->validate([
        'driver_lat' => 'required|numeric',
        'driver_lng' => 'required|numeric',
    ]);

    $ride->update([
        'current_driver_lat' => $data['driver_lat'],
        'current_driver_lng' => $data['driver_lng'],
    ]);

    if (method_exists($ride, 'rideLogs')) {
        $ride->rideLogs()->create([
            'driver_lat' => $data['driver_lat'],
            'driver_lng' => $data['driver_lng'],
        ]);
    }

    $routePolyline = null;
if ($ride->origin_lat && $ride->destination_lat) {
    $coords = $this->getRoutePolyline(
        $data['driver_lat'],
        $data['driver_lng'],
        $ride->destination_lat,
        $ride->destination_lng
    );
    $routePolyline = $this->encodePolyline($coords);
}

broadcast(new DriverLocationUpdated($driver, $ride->id, $routePolyline, $ride))->toOthers();

return response()->json([
    'status' => 'location updated',
    'ride_id' => $ride->id,
    'driver_id' => $ride->driver_id,
    'current_driver_lat' => $ride->current_driver_lat,
    'current_driver_lng' => $ride->current_driver_lng,
    'current_route' => $routePolyline,
]);

}


    /**
     * Calculate fare dynamically (admin-adjustable formula)
     */
   protected function calculateFare(Ride $ride)
{
    $settings = \App\Models\FareSettings::first();
    if (!$settings) return;

    $fare = $settings->base_fare
          + ($ride->distance * $settings->per_km_rate)
          + ($ride->duration * $settings->per_minute_rate);

    // Apply minimum fare
    if ($fare < $settings->minimum_fare) {
        $fare = $settings->minimum_fare;
    }

    // Apply peak multiplier
    $fare *= $settings->peak_multiplier;

    $ride->fare = round($fare, 2);
    $ride->save();
}


protected function calculateDistanceToPickup(Ride $ride, Driver $driver)
{
    // Use Haversine formula or a maps API to calculate distance
    // Example: Google Maps API or custom logic
    return 2.5; // in km (example)
}

protected function calculateEstimatedTimeToPickup(Ride $ride, Driver $driver)
{
    // Example: Assume 30 km/h average speed
    $distance = $this->calculateDistanceToPickup($ride, $driver);
    $speed = 30; // km/h
    $timeInMinutes = ($distance / $speed) * 60;
    return round($timeInMinutes, 1);
}


}
