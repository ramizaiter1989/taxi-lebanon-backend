<?php
namespace App\Events;

use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver;
    public $ride_id;
    public $current_route; // new property for polyline

    public function __construct(Driver $driver, $ride_id = null, $current_route = null)
    {
        $this->driver = $driver;
        $this->ride_id = $ride_id;
        $this->current_route = $current_route;
    }

    public function broadcastOn()
    {
        // Broadcast to a global drivers channel or ride-specific
        return $this->ride_id 
            ? new Channel('ride.' . $this->ride_id) 
            : new Channel('drivers-location');
    }

    public function broadcastAs(): string
    {
        return 'driver-location-updated';
    }

    public function broadcastWith()
    {
        return [
            'driver_id' => $this->driver->id,
            'name' => $this->driver->user->name ?? null,
            'vehicle_type' => $this->driver->vehicle_type,
            'vehicle_number' => $this->driver->vehicle_number,
            'lat' => $this->driver->current_driver_lat,
            'lng' => $this->driver->current_driver_lng,
            'availability_status' => $this->driver->availability_status,
            'ride_id' => $this->ride_id,
            'current_route' => $this->current_route,
        ];
    }
}
