<?php
// app/Events/MessageReadEvent.php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageReadEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $messageId;
    public $rideId;

    public function __construct($messageId, $rideId)
    {
        $this->messageId = $messageId;
        $this->rideId = $rideId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ride.' . $this->rideId);
    }

    public function broadcastAs()
    {
        return 'MessageReadEvent';
    }

    public function broadcastWith()
    {
        \Log::info('Broadcasting message read:', [
            'message_id' => $this->messageId,
            'ride_id' => $this->rideId
        ]);

        return [
            'messageId' => $this->messageId,
            'rideId' => $this->rideId
        ];
    }
}
