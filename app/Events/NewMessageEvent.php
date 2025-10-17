<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat->load(['sender', 'receiver']);
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->chat->ride_id);
    }

    public function broadcastAs()
    {
        return 'NewMessageEvent';
    }
}
