<?php

namespace App\Http\Controllers\Api;

use App\Models\Chat;
use App\Models\Ride;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Events\NewMessageEvent;
use App\Events\MessageReadEvent;

class ChatController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:rides,id',
            'message' => 'required|string|max:1000',
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
            'is_read' => false, // Explicitly set to false
        ]);

        $chat->load(['sender', 'receiver']);

        // Fire the broadcast event (instant if using ShouldBroadcastNow)
        broadcast(new NewMessageEvent($chat))->toOthers();

        // Log for debugging
        \Log::info('Message created and broadcast', [
            'chat_id' => $chat->id,
            'ride_id' => $chat->ride_id,
            'sender' => $chat->sender->name,
        ]);

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

public function markAsRead(Request $request, Ride $ride)
{
    $user = $request->user();

    // Fetch the unread messages first
    $messages = Chat::where('ride_id', $ride->id)
        ->where('receiver_id', $user->id)
        ->where('is_read', false)
        ->get();

    // Update and broadcast for each message
    foreach ($messages as $message) {
        $message->update(['is_read' => true]);
        broadcast(new MessageReadEvent($message->id, $ride->id))->toOthers();
    }

    \Log::info('Messages marked as read', [
        'user_id' => $user->id,
        'ride_id' => $ride->id,
        'count' => $messages->count()
    ]);

    return response()->json([
        'message' => 'Messages marked as read',
        'updated_count' => $messages->count()
    ]);
}


    // Optional: Get unread message count for a ride
    public function unreadCount(Request $request, Ride $ride)
    {
        $user = $request->user();

        $count = Chat::where('ride_id', $ride->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    // Optional: Get all unread messages across all rides
    public function allUnreadCount(Request $request)
    {
        $user = $request->user();

        $count = Chat::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'total_unread' => $count
        ]);
    }
}