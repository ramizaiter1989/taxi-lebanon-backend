<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
USE Illuminate\Support\Facades\Broadcast;
USE Illuminate\Support\Facades\Auth;
use App\Models\Driver;

class RideAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;
    public $driver;

    public function __construct(Ride $ride, $driver)
    {
        $this->ride = $ride;
        $this->driver = $driver;
    }

    // In your RideAccepted event or wherever you notify passengers
    public function broadcastOn()
    {
        return new Channel('passenger-' . $this->ride->passenger_id); // Public channel
    }

    public function broadcastWith()
    {
        return [
            'ride_id' => $this->ride->id,
            'driver_id' => $this->ride->driver_id,
            'status' => $this->ride->status,
        ];
    }
}
