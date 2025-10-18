<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray($request)
    {
        return [
        'id' => $this->id,
        'ride_id' => $this->ride_id,
        'sender_id' => $this->sender_id,
        'receiver_id' => $this->receiver_id,
        'message' => $this->message,
        'is_read' => (bool) $this->is_read, // ← Must be here!
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'sender' => [
            'id' => $this->sender->id,
            'name' => $this->sender->name,
            'role' => $this->sender->role ?? null,
        ],
        'receiver' => [
            'id' => $this->receiver->id,
            'name' => $this->receiver->name,
            'role' => $this->receiver->role ?? null,
        ],
    ];
    }
}
