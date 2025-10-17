<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Chat;

class NewMessageEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $chat;

    public function __construct(Chat $chat) {
        $this->chat = $chat;
    }

    public function broadcastOn() {
        return new Channel('ride.' . $this->chat->ride_id);
    }
}
