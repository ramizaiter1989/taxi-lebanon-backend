<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Chat;

class NewMessageEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chat->receiver_id);
    }
    public function broadcastWith()
{
    return [
        'message' => $this->chat->message,
        'sender_id' => $this->chat->sender_id,
        // Add other fields as needed
    ];
}
}
