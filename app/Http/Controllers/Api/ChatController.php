<?php
namespace App\Http\Controllers\Api;

use App\Models\Chat;
use App\Models\Ride;
use App\Events\NewMessageEvent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

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

            // Defensive checks
            if (!$ride->driver) {
                return response()->json(['error' => 'No driver assigned to this ride yet'], 400);
            }

            $driverUserId = $ride->driver->user_id; // gets the actual user ID of the driver
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

            // Commented out if not using broadcasting
            // event(new NewMessageEvent($chat));

            return response()->json($chat, 201);
        }




    public function show(Ride $ride, Request $request)
    {
        $user = $request->user();

        if (!in_array($user->id, [$ride->driver->user_id, $ride->passenger_id])) {
            abort(403, 'Unauthorized');
        }

        $chats = Chat::where('ride_id', $ride->id)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($chats);
    }

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
