<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if account is locked
        if ($user->is_locked) {
            return response()->json([
                'message' => 'Your account has been locked. Please contact support.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
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