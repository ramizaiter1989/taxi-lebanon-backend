<?php
// app/Events/RideRemoved.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rideId;

    public function __construct($rideId)
    {
        $this->rideId = $rideId;
    }

    public function broadcastOn()
    {
        return new Channel('drivers');
    }
}
