<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Changed from ShouldBroadcast
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\ChatResource;

class NewMessageEvent implements ShouldBroadcastNow // This makes it broadcast instantly!
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

    // Optional: Add this to see what data is being broadcast
    public function broadcastWith()
    {
        \Log::info('Broadcasting message:', [
            'chat_id' => $this->chat->id ?? 'unknown',
            'ride_id' => $this->chat->ride_id ?? 'unknown'
        ]);
        
        return [
            'chat' => $this->chat
        ];
    }
}