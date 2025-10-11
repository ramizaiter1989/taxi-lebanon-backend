<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver_id;
    public $driver_name;
    public $current_driver_lat;
    public $current_driver_lng;

    /**
     * Create a new event instance.
     */
    public function __construct($driverId, $driverName, $lat, $lng)
    {
        $this->driver_id = $driverId;
        $this->driver_name = $driverName;
        $this->current_driver_lat = $lat;
        $this->current_driver_lng = $lng;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('drivers-location'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'driver-location-updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driver_id,
            'driver_name' => $this->driver_name,
            'current_driver_lat' => $this->current_driver_lat,
            'current_driver_lng' => $this->current_driver_lng,
            'timestamp' => now()->toISOString(),
        ];
    }
}