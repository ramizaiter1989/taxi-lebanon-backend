<?php

namespace App\Events;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;



class RideCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;
    public $cancelledBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Ride $ride, User $cancelledBy)
    {
        $this->ride = $ride;
        $this->cancelledBy = $cancelledBy;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('rides'),
            new PrivateChannel('ride.' . $this->ride->id),
        ];

        // Broadcast to passenger
        if ($this->ride->passenger_id) {
            $channels[] = new PrivateChannel('user.' . $this->ride->passenger_id);
        }

        // Broadcast to driver
        if ($this->ride->driver_id) {
            $channels[] = new PrivateChannel('driver.' . $this->ride->driver_id);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->ride->id,
            'status' => $this->ride->status,
            'cancelled_by_role' => $this->cancelledBy->role,
            'cancelled_by_name' => $this->cancelledBy->name,
            'cancellation_reason' => $this->ride->cancellation_reason,
            'cancellation_note' => $this->ride->cancellation_note,
            'cancelled_at' => $this->ride->cancelled_at?->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ride.cancelled';
    }
}