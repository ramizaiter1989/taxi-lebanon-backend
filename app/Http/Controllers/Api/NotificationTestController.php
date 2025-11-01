<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\RideNotification;
use Illuminate\Http\Request;

class NotificationTestController extends Controller
{
    public function sendTest(Request $request)
    {
        $user = $request->user();
        
        $user->notify(new RideNotification(
            'Test Notification',
            'This is a test push notification!',
            ['type' => 'test', 'timestamp' => now()]
        ));

        return response()->json(['message' => 'Test notification sent!']);
    }
}