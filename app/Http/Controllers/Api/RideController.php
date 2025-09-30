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

        // Calculate fare dynamically
        $this->calculateFare($ride);

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
     * Passenger or driver cancels a ride
     */
    public function cancelRide(Request $request, Ride $ride)
    {
        $user = $request->user();
        if ($ride->passenger_id !== $user->id && $ride->driver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ride->status = 'cancelled';
        $ride->save();

        return response()->json($ride);
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
