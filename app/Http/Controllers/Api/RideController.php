<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Models\Driver;
use App\Events\DriverLocationUpdated;
use App\Events\RideRequested;
use App\Events\RideAccepted;
use App\Traits\PolylineTrait;
use App\Notifications\RideNotification;

class RideController extends Controller
{
    use PolylineTrait;
    /**
     * Passenger requests a ride
     */
    public function store(Request $request)
    {
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


         $nearbyDrivers = Driver::where('availability_status', true)
        ->whereRaw('...')  // your distance query
        ->with('user')
        ->get();
    
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

        // Notify all drivers in general (drivers filter based on scanning range in frontend)
        RideRequested::dispatch($ride);

        return response()->json($ride);
        
    }

    /**
     * Driver sees available rides within their scanning range
     */
    public function availableRides(Request $request)
    {
        $driver = $request->user()->driver;
        if (!$driver) return response()->json(['error' => 'Only drivers can pick rides'], 403);

        $rides = Ride::where('status', 'pending')
            ->whereRaw('
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(origin_lat)) *
                    cos(radians(origin_lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(origin_lat))
                )) <= ?
            ', [
                $driver->current_driver_lat,
                $driver->current_driver_lng,
                $driver->current_driver_lat,
                $driver->scanning_range_km
            ])
            ->get();

            return response()->json($rides);

            $ride->passenger->notify(new RideNotification(

            'Ride Accepted',
            "Driver {$driver->user->name} accepted your ride",
            [
                'type' => 'ride_accepted',
                'ride_id' => $ride->id,
                'driver_name' => $driver->user->name,
                'driver_phone' => $driver->user->phone
            ]
        ));

        return response()->json($rides);
    }

    /**
     * Driver accepts a ride
     */
    public function acceptRide(Request $request, Ride $ride)
    {
        $driver = $request->user()->driver;
        if (!$driver) return response()->json(['error' => 'Only drivers can accept rides'], 403);

        if ($ride->driver_id && $ride->driver_id !== $driver->id) {
            return response()->json(['error' => 'Ride already assigned to another driver'], 403);
        }
        // In acceptRide method:
        $ride->passenger->notify(new RideNotification(
            'Ride Accepted',
            "Driver {$driver->user->name} has accepted your ride",
            ['type' => 'ride_accepted', 'ride_id' => $ride->id]
        ));

        $ride->driver_id = $driver->id;
        $ride->status = 'in_progress'; // Observer sets started_at
        $ride->started_at = now();
        $ride->save();

        // Notify passenger that ride is accepted
        RideAccepted::dispatch($ride);

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

        return response()->json($ride);
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

broadcast(new DriverLocationUpdated($driver, $ride->id, $routePolyline))->toOthers();

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


}
