<?php

namespace App\Http\Middleware;

use Closure;

class EnsureDriverProfileCompleted
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // Only check if user is a driver
        if ($user->role === 'driver') {
            $driver = $user->driver;
            
            // Check if driver record exists
            if (!$driver) {
                return response()->json([
                    'message' => 'Driver profile not found',
                    'profile_completed' => false
                ], 403);
            }
            
            // Check if required fields are completed
            if ($user->role === 'driver' && (!$user->driver || !$user->driver->isProfileCompleted())) {
                    return response()->json([
                        'message' => 'Driver profile not completed',
                        'profile_completed' => false
                    ], 403);
                }
            }

        return $next($request);
    }
}