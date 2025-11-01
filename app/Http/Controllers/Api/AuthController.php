<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Driver;
use App\Traits\SendsOtpSms;
use Exception;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    use SendsOtpSms;
    
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

        $user->markEmailAsVerified();

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
                    'message' => 'Registration successful, but OTP could not be sent.',
                    'error' => $e->getMessage(),
                    'token' => $user->createToken('auth_token')->plainTextToken,
                    'user' => $user,
                ], 500);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please enter the OTP sent to your phone.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->is_locked) {
            return response()->json(['message' => 'Your account has been locked. Please contact support.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'token' => $token,
            'user' => $user,
        ];

        if ($user->role === 'driver') {
            $driver = $user->driver;
            $response['profile_completed'] = $driver && $driver->isProfileCompleted();
        }

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'driver') {
            $user->load('driver');
        }

        return new UserResource($user);
    }

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
            'car_photo_front' => 'nullable|image|max:2048',
            'car_photo_back' => 'nullable|image|max:2048',
            'car_photo_left' => 'nullable|image|max:2048',
            'car_photo_right' => 'nullable|image|max:2048',
            'license_photo' => 'nullable|image|max:2048',
            'id_photo' => 'nullable|image|max:2048',
            'insurance_photo' => 'nullable|image|max:2048',
        ]);

        $driver = $user->driver ?? Driver::create(['user_id' => $user->id]);

        $photoFields = [
            'car_photo', 'car_photo_front', 'car_photo_back', 'car_photo_left',
            'car_photo_right', 'license_photo', 'id_photo', 'insurance_photo',
        ];

        foreach ($photoFields as $photoField) {
            if ($request->hasFile($photoField)) {
                $path = $request->file($photoField)->store('drivers', 'public');
                $validated[$photoField] = $path;
            }
        }

        $driver->update($validated);

        return response()->json([
            'message' => 'Driver profile completed successfully',
            'profile_completed' => true,
            'driver' => $driver,
        ]);
    }

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

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // ✅ FIXED: Better validation and logging
    public function updatePushToken(Request $request)
    {
        $validated = $request->validate([
            'expo_push_token' => 'required|string|starts_with:ExponentPushToken[',
        ]);

        $user = $request->user();
        
        // Log for debugging
        Log::info('Updating push token', [
            'user_id' => $user->id,
            'token' => $validated['expo_push_token']
        ]);

        $user->expo_push_token = $validated['expo_push_token'];
        $user->save();

        return response()->json([
            'message' => 'Push token updated successfully',
            'token' => $user->expo_push_token
        ]);
    }
}