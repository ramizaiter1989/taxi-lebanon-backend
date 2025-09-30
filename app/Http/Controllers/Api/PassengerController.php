<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\PassengerLocationUpdated;

class PassengerController extends Controller
{
    // Passenger updates their location
    public function updateLocation(Request $request)
{
    $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
    ]);

    $user = $request->user();
    if ($user->role !== 'passenger') {
        return response()->json(['error' => 'Only passengers can update location'], 403);
    }

    $user->update([
        'current_lat' => $request->lat,
        'current_lng' => $request->lng,
        'last_location_update' => now(),
    ]);

    // Only broadcast if status is true
    if($user->status){
    broadcast(new PassengerLocationUpdated($user))->toOthers();
}


    return response()->json(['message' => 'Location updated']);
}


    // Admin fetches all online passengers
    public function livePassengers()
    {
        // Define online: status true + last update within 5 minutes
        $onlinePassengers = User::where('role', 'passenger')
            ->where('status', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->where('last_location_update', '>=', now()->subMinutes(5))
            ->get(['id','name','current_lat','current_lng','last_location_update']);

        return response()->json($onlinePassengers);
    }
}
