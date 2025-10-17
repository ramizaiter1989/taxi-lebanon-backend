<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\ChatResource;

class NewMessageEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = new ChatResource($chat->load(['sender', 'receiver']));
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ride.' . $this->chat->ride_id);
    }

    public function broadcastAs()
    {
        return 'NewMessageEvent';
    }
}
