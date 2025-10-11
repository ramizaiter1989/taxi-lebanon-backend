<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use App\Models\User;


// ========================================
// API Controllers
// ========================================
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Admin\FareSettingsController;
use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Broadcast;

// ========================================
// PUBLIC ROUTES (No Authentication)
// ========================================

// Authentication: Register, Login, Logout
Route::post('register', [AuthController::class, 'register']); // Register new user (passenger/driver)
Route::post('login', [AuthController::class, 'login']); // Login and get bearer token
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']); // Logout and revoke token

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// OTP Verification: Send, Resend, Verify OTP codes
Route::prefix('otp')->group(function () {
    Route::post('send', [OtpController::class, 'sendOtp']); // Send OTP to phone number
    Route::post('resend', [OtpController::class, 'resendOtp']); // Resend OTP if expired/not received
    Route::post('verify', [OtpController::class, 'verifyOtp']); // Verify OTP code
});

// Email Verification: Verify email via signed URL
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($user->email))) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Email verified successfully',
        'token'   => $token,
    ]);
})->middleware('signed')->name('verification.verify');

// Resend Email Verification: Request new verification link
Route::post('/email/verification-notification', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $user = User::where('email', $request->email)->first();

    if (!$user) return response()->json(['message' => 'User not found'], 404);
    if ($user->hasVerifiedEmail()) return response()->json(['message' => 'Email already verified'], 400);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $user->notify(new \App\Notifications\VerifyEmailApi($verificationUrl));

    return response()->json(['message' => 'Verification link sent']);
});

// Directions API: Get route between two points using OpenRouteService
Route::get('/directions', function (Request $request) {
    $response = Http::withHeaders([
        'Authorization' => env('ORS_API_KEY'),
    ])->get("https://api.openrouteservice.org/v2/directions/driving-car", [
        'start' => $request->query('start'),
        'end'   => $request->query('end'),
    ]);

    return $response->json();
});

// Test Endpoint: Returns random number (for testing API connectivity)
Route::get('test/random', [TestController::class, 'randomNumber']);

// ========================================
// PROTECTED ROUTES (Require Authentication)
// ========================================
Route::middleware('auth:sanctum')->group(function () {

    // User Profile: Get authenticated user's profile
    Route::get('/user/profile', [AuthController::class, 'profile']);

    //passemger location stream
    Route::post('passenger/stream-location', [PassengerController::class, 'streamLocation']);

    // Get my current location when opening map
    Route::get('my-location', [DriverController::class, 'getMyLocation']);
    
    // Complete Driver Profile: Submit driver documents after registration (drivers only)
    Route::post('complete-driver-profile', [AuthController::class, 'completeDriverProfile']);

    // ========================================
    // RIDES MANAGEMENT
    // ========================================
Route::middleware('auth:sanctum')->prefix('rides')->group(function () {
    Route::post('/', [RideController::class, 'store']); // Passenger: Request a new ride
    Route::get('/', [RideController::class, 'index']); // Live rides (passenger -> own live; driver -> assigned live)
    Route::get('/history', [RideController::class, 'history']); // Completed/cancelled (history)
    Route::get('/{ride}', [RideController::class, 'show'])->where('ride', '[0-9]+'); // Single ride details (authorized)
    Route::get('/available', [RideController::class, 'availableRides']); // Driver: Get rides within scanning range
    Route::post('{ride}/accept', [RideController::class, 'acceptRide']); // Driver: Accept a ride request
    Route::post('{ride}/update-location', [RideController::class, 'updateLocation']); // Driver: Update location during ride
    Route::post('{ride}/arrived', [RideController::class, 'markArrived']); // Driver: Mark as arrived at destination
    Route::post('{ride}/cancel', [RideController::class, 'cancelRide']); // Passenger/Driver: Cancel ride
    Route::get('/live', [RideController::class, 'current']); // Get current live rides for admin 
    Route::post('estimate-fare', [RideController::class, 'estimateFare']); // Calculate estimated fare
    Route::patch('{ride}/status', [RideController::class, 'updateStatus']); // Update ride status
});

    // ========================================
    // DRIVERS MANAGEMENT
    // ========================================
    Route::prefix('drivers')->group(function () {
        Route::get('/', [DriverController::class, 'index']); // List all available drivers with live locations

        Route::prefix('{driver}')->group(function () {
            Route::post('go-online', [DriverController::class, 'goOnline']); // Driver: Go online and start accepting rides
            Route::post('go-offline', [DriverController::class, 'goOffline']); // Driver: Go offline and stop accepting rides
            Route::post('location', [DriverController::class, 'updateLocation']); // Driver: Update current GPS location
            Route::put('range', [DriverController::class, 'updateRange']); // Driver: Update scanning range (radius for accepting rides)
            Route::get('activity-logs', [DriverController::class, 'activityLogs']); // Driver: Get online/offline activity history
            Route::get('live-status', [DriverController::class, 'liveStatus']); // Driver: Get current online status and duration
            Route::get('profile', [DriverController::class, 'showProfile']); // Driver: Get driver profile details
            Route::put('profile', [DriverController::class, 'updateProfile']); // Driver: Update profile (vehicle, license, photos)
        });
    });

    // ========================================
    // PASSENGER OPERATIONS
    // ========================================
    Route::prefix('passenger')->group(function () {
        Route::get('drivers', [DriverController::class, 'driversForPassenger']); // Passenger: Get drivers assigned to their rides
        Route::post('location', [PassengerController::class, 'updateLocation']); // Passenger: Update current GPS location
        Route::get('rides', [PassengerController::class, 'myRides']); // Passenger: Get all my rides history
        Route::get('profile', [PassengerController::class, 'profile']); // Passenger: Get my profile
        Route::put('profile', [PassengerController::class, 'updateProfile']); // Passenger: Update my profile (name, phone, gender, photo)
    });

    // ========================================
    // ADMIN OPERATIONS
    // ========================================
    Route::prefix('admin')->group(function () {
        Route::get('live-locations', [AdminController::class, 'liveLocations']); // Admin: Get live locations of all online drivers & passengers
        
        // Admin: Passenger Management
        Route::prefix('passengers')->group(function () {
            Route::get('live', [PassengerController::class, 'livePassengers']); // Admin: Get all online passengers
        });
        
        // Admin: Fare Settings Management
        Route::prefix('fare-settings')->group(function () {
            Route::get('/', [FareSettingsController::class, 'index']); // Admin: Get current fare settings
            Route::put('/', [FareSettingsController::class, 'update']); // Admin: Update fare calculation settings
        });
    });

    // ========================================
    // PAYMENTS
    // ========================================
    Route::post('rides/{ride}/pay', [PaymentController::class, 'payRide']); // Process payment for completed ride via Stripe


    // Passenger: Favorite Places
    Route::prefix('passenger/favorite-places')->group(function () {
        Route::get('/', [PassengerController::class, 'getFavoritePlaces']);
        Route::post('/', [PassengerController::class, 'saveFavoritePlace']);
        Route::delete('{place}', [PassengerController::class, 'deleteFavoritePlace']);
    });
    
    // Passenger: Emergency
    Route::prefix('passenger/emergency')->group(function () {
        Route::post('rides/{ride}/sos', [PassengerController::class, 'emergencySOS']);
        Route::get('contacts', [PassengerController::class, 'getEmergencyContacts']);
        Route::post('contacts', [PassengerController::class, 'addEmergencyContact']);
    });
    
    // Passenger: Wallet
    Route::prefix('passenger/wallet')->group(function () {
        Route::get('balance', [PassengerController::class, 'getWalletBalance']);
        Route::post('add', [PassengerController::class, 'addToWallet']);
    });
    
    // Rides: Promo codes & Pool
    Route::post('rides/{ride}/apply-promo', [RideController::class, 'applyPromoCode']);
    Route::post('rides/pool', [RideController::class, 'requestPoolRide']);
    
    // Driver: Block passengers
    Route::prefix('drivers')->group(function () {
        Route::post('block/{passenger}', [DriverController::class, 'blockPassenger']);
        Route::delete('unblock/{passenger}', [DriverController::class, 'unblockPassenger']);
        Route::get('blocked-passengers', [DriverController::class, 'getBlockedPassengers']);
    });
    
    // FCM Token
    Route::post('update-fcm-token', [PassengerController::class, 'updateFcmToken']);

    
 // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AuthController::class, 'getNotifications']);
        Route::post('{notification}/read', [AuthController::class, 'markNotificationRead']);
        Route::post('read-all', [AuthController::class, 'markAllNotificationsRead']);
    });



});