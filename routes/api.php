<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Admin\FareSettingsController;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use App\Http\Controllers\Auth\RegisteredUserController;

// ------------------------
// Public API routes
// ------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


// Email verification
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);
    if (! hash_equals((string) $hash, sha1($user->email))) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Email verified successfully',
        'token' => $token
    ]);
})->middleware('signed')->name('verification.verify');

// Resend verification
Route::post('/email/verification-notification', function(Request $request){
    $request->validate(['email'=>'required|email']);
    $user = User::where('email',$request->email)->first();

    if(!$user) return response()->json(['message'=>'User not found'], 404);
    if($user->hasVerifiedEmail()) return response()->json(['message'=>'Email already verified'], 400);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id'=>$user->id,'hash'=>sha1($user->email)]
    );

    $user->notify(new \App\Notifications\VerifyEmailApi($verificationUrl));

    return response()->json(['message'=>'Verification link sent']);
});

// Directions API
Route::get('/directions', function (Request $request) {
    $start = $request->query('start');
    $end   = $request->query('end');
    $apiKey = env('ORS_API_KEY');

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => $apiKey,
    ])->get("https://api.openrouteservice.org/v2/directions/driving-car", [
        'start' => $start,
        'end'   => $end,
    ]);

    return $response->json();
});

// ------------------------
// Protected API routes
// ------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);

    // Rides
    Route::prefix('rides')->group(function () {
        Route::get('/', [RideController::class, 'index']); // List rides
        Route::post('/', [RideController::class, 'store']); // Request ride
        Route::get('/available', [RideController::class, 'availableRides']); // Driver sees available rides
        Route::post('{ride}/accept', [RideController::class, 'acceptRide']); // Driver accepts
        Route::post('{ride}/update-location', [RideController::class, 'updateLocation']); // Update driver location
        Route::post('{ride}/arrived', [RideController::class, 'markArrived']); // Driver marks arrived
        Route::post('{ride}/cancel', [RideController::class, 'cancelRide']); // Cancel ride
        Route::get('estimate', [RideController::class, 'estimateFare']); // Fare estimation
        Route::patch('{ride}/status', [RideController::class, 'updateStatus']); // Update ride status
    });

    // Drivers
    Route::prefix('drivers')->group(function () {
        Route::get('/', [DriverController::class, 'index']);
        Route::patch('driver/{driver}/online', [DriverController::class, 'goOnline']);
        Route::patch('driver/{driver}/offline', [DriverController::class, 'goOffline']);
        Route::post('driver/{driver}/update-location', [DriverController::class, 'updateLocation']);
        Route::patch('driver/{driver}/update-range', [DriverController::class, 'updateRange']);
        Route::get('driver/{driver}/logs', [DriverController::class, 'activityLogs']);
        Route::get('driver/{driver}/summary', [DriverController::class, 'activitySummary']);
        Route::get('driver/{driver}/status', [DriverController::class, 'liveStatus']);

        // New routes for profile management
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('driver/{driver}/profile', [DriverController::class, 'showProfile']);
        Route::put('driver/{driver}/profile', [DriverController::class, 'updateProfile']);
    });
    });

    // Passenger
    Route::get('passenger/drivers', [DriverController::class, 'driversForPassenger']);
    Route::post('/passenger/location', [\App\Http\Controllers\Api\PassengerController::class, 'updateLocation']);
    Route::get('/admin/passengers/live', [\App\Http\Controllers\Api\PassengerController::class, 'livePassengers'])
        ->middleware('can:view-admin-dashboard');

    // Payments
    Route::post('payments/{ride}', [PaymentController::class, 'payRide']);

    // Admin Fare Settings
    Route::prefix('admin/fare-settings')->group(function () {
        Route::get('/', [FareSettingsController::class, 'index']);
        Route::post('/', [FareSettingsController::class, 'update']);
    });
});
