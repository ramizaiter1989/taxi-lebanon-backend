<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Driver;
use App\Models\Otp;
use Twilio\Rest\Client;
use App\Traits\SendsOtpSms;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    use SendsOtpSms;

    public function register(Request $request)
{
    
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'phone' => 'nullable|string|max:20',
        'password' => 'required|string|confirmed|min:8',
        'role' => 'required|in:passenger,driver',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => Hash::make($request->password),
        'role' => $request->role,
    ]);
    $otpCode = rand(100000, 999999);
        Otp::create([
            'phone' => $request->phone,
            'code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        try {
            $this->sendSms($request->phone, $otpCode);
        } catch (\Exception $e) {
            Log::error("Failed to send OTP: " . $e->getMessage());
            return response()->json(['error' => 'Failed to send OTP. Try again later.'], 500);
        }

        return response()->json([
            'message' => $request->role === 'driver'
                ? 'Account created. Please complete your driver profile.'
                : 'Registration successful.',
            'user' => $user,
            'requires_driver_profile' => $user->role === 'driver',
        ], 201);
    }


   // Get authenticated user profile
public function profile(Request $request)
{
    return response()->json([
        'user' => $request->user()
    ]);
}

//additional friver details on registration
public function completeDriverProfile(Request $request)
{
    $request->validate([
        'license_number' => 'required|string|max:50',
        'vehicle_type' => 'required|string|max:50',
        'vehicle_number' => 'required|string|max:50',
        'car_photo_front' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'car_photo_back' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'car_photo_left' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'car_photo_right' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'license_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'id_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        'insurance_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    $user = User::Auth(); // only logged-in drivers can complete profile

    if ($user->role !== 'driver') {
        return response()->json(['message' => 'Only drivers can complete driver profile'], 403);
    }

    $driverData = [
        'user_id' => $user->id,
        'license_number' => $request->license_number,
        'vehicle_type' => $request->vehicle_type,
        'vehicle_number' => $request->vehicle_number,
        'availability_status' => false, // wait for admin approval
    ];

    // Car photos
    $carPhotos = [];
    foreach (['car_photo_front', 'car_photo_back', 'car_photo_left', 'car_photo_right'] as $field) {
        $carPhotos[$field] = $request->file($field)->store("drivers/{$user->id}/car", 'public');
    }
    $driverData['car_photo'] = json_encode($carPhotos);

    // Document photos
    foreach (['license_photo', 'id_photo', 'insurance_photo'] as $field) {
        $driverData[$field] = $request->file($field)->store("drivers/{$user->id}/documents", 'public');
    }

    Driver::create($driverData);

    return response()->json([
        'message' => 'Driver profile submitted. Awaiting admin approval.',
    ], 201);
}



    // Login API
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email not verified'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
 * Get user notifications
 */
public function getNotifications(Request $request)
{
    $notifications = $request->user()
        ->notifications()
        ->latest()
        ->paginate(20);
    
    return response()->json($notifications);
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
    
    if ($notification) {
        $notification->markAsRead();
    }
    
    return response()->json(['message' => 'Notification marked as read']);
}

/**
 * Mark all notifications as read
 */
public function markAllNotificationsRead(Request $request)
{
    $request->user()->unreadNotifications->markAsRead();
    return response()->json(['message' => 'All notifications marked as read']);
}

    // Logout API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}