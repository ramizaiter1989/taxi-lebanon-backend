<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Traits\SendsOtpSms;
use Exception;

class AuthController extends Controller
{
    //otp function from trails
    use SendsOtpSms;
    
    /**
     * Register a new user
     */
 public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20|unique:users',
            'role' => 'required|in:passenger,driver',
            'gender' => 'nullable|in:male,female',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'gender' => $validated['gender'] ?? 'female',
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        if ($user->phone) {
            $otpCode = rand(100000, 999999);
            \App\Models\Otp::create([
                'phone' => $user->phone,
                'code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
            ]);

            try {
                $this->sendSms($user->phone, $otpCode);
            } catch (Exception $e) {
                Log::error("OTP SMS failed: " . $e->getMessage());
                return response()->json([
                    'message' => 'Registration successful, but OTP could not be sent. Please try again or contact support.',
                    'error' => $e->getMessage(),
                    'token' => $user->createToken('auth_token')->plainTextToken,
                    'user' => $user,
                ], 500);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please verify your email and enter the OTP sent to your phone.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }


    /**
     * Logout user
     */
    public function logout(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Delete current access token
    $user->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}


    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // Load driver relationship if user is a driver
        if ($user->role === 'driver') {
            $user->load('driver');
        }

        return response()->json($user);
    }

    /**
     * Complete driver profile (after registration)
     */
    public function completeDriverProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['message' => 'Only drivers can complete driver profile'], 403);
        }

        $validated = $request->validate([
            'license_number' => 'required|string|max:50',
            'vehicle_type' => 'required|string|max:50',
            'vehicle_number' => 'required|string|max:50',
            'car_photo' => 'nullable|image|max:2048',
            'license_photo' => 'nullable|image|max:2048',
            'id_photo' => 'nullable|image|max:2048',
            'insurance_photo' => 'nullable|image|max:2048',
        ]);

        $driver = $user->driver;

        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        // Handle file uploads
        foreach (['car_photo', 'license_photo', 'id_photo', 'insurance_photo'] as $photoField) {
            if ($request->hasFile($photoField)) {
                $path = $request->file($photoField)->store('drivers', 'public');
                $validated[$photoField] = $path;
            }
        }

        $driver->update($validated);

        return response()->json([
            'message' => 'Driver profile completed successfully',
            'driver' => $driver,
        ]);
    }

    /**
     * Get user notifications
     */
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications->items(),
            'total' => $notifications->total(),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }
}