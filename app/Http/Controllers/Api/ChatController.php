<?php

namespace App\Http\Controllers\Api;

use App\Models\Chat;
use App\Models\Ride;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
class ChatController extends Controller
{


public function store(Request $request)
{
    $request->validate([
        'ride_id' => 'required|exists:rides,id',
        'message' => 'required|string',
    ]);

    $ride = Ride::with('driver')->findOrFail($request->ride_id);
    $sender = $request->user();

    if (!$ride->driver) {
        return response()->json(['error' => 'No driver assigned to this ride yet'], 400);
    }

    $driverUserId = $ride->driver->user_id;
    $passengerUserId = $ride->passenger_id;

    if (!in_array($sender->id, [$driverUserId, $passengerUserId])) {
        abort(403, 'Unauthorized');
    }

    $receiverId = ($sender->id === $driverUserId) ? $passengerUserId : $driverUserId;

    $chat = Chat::create([
        'ride_id' => $ride->id,
        'sender_id' => $sender->id,
        'receiver_id' => $receiverId,
        'message' => $request->message,
    ]);

    $chat->load(['sender', 'receiver']);

    return new ChatResource($chat);
}

public function show(Ride $ride, Request $request)
{
    $user = $request->user();
    $ride->load('driver');

    if (!$ride->driver) {
        return response()->json(['error' => 'No driver assigned'], 400);
    }

    $driverUserId = $ride->driver->user_id;
    $passengerUserId = $ride->passenger_id;

    if (!in_array($user->id, [$driverUserId, $passengerUserId])) {
        abort(403, 'Unauthorized');
    }

    $chats = Chat::where('ride_id', $ride->id)
        ->where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
        })
        ->with(['sender:id,name,role', 'receiver:id,name,role'])
        ->orderBy('created_at', 'asc')
        ->get();

    return ChatResource::collection($chats);
}


    // Optional: Mark messages as read
    public function markAsRead(Request $request, Ride $ride)
    {
        $user = $request->user();

        Chat::where('ride_id', $ride->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
