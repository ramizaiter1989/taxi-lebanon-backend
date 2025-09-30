<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;

    public function __construct(Ride $ride)
    {
        $this->ride = $ride;
    }

    public function broadcastOn()
    {
        // Broadcast to drivers channel
        return new Channel('drivers');
    }

    public function broadcastWith()
    {
        return [
            'ride_id' => $this->ride->id,
            'origin_lat' => $this->ride->origin_lat,
            'origin_lng' => $this->ride->origin_lng,
            'destination_lat' => $this->ride->destination_lat,
            'destination_lng' => $this->ride->destination_lng,
        ];
    }
}
