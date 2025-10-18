<?php
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\BenzConsumptionController;

// ========================================
// PUBLIC API ROUTES (No Authentication)
// ========================================

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:api');
// In routes/api.php - PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat', [ChatController::class, 'store']);
    Route::get('/chat/{ride}', [ChatController::class, 'show']);
    Route::post('/chat/{ride}/mark-read', [ChatController::class, 'markAsRead']);
});

// OTP Verification (Public)
Route::prefix('otp')->group(function () {
    Route::post('send', [OtpController::class, 'sendOtp']);
    Route::post('resend', [OtpController::class, 'resendOtp']);
    Route::post('verify', [OtpController::class, 'verifyOtp']);
});

// Email Verification (Public)
// Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])
//     ->middleware(['signed', 'throttle:6,1'])
//     ->name('verification.verify');
// Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
//     ->middleware('auth:sanctum')
//     ->name('verification.send');

// Password Reset (Public)
// Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
//     ->middleware('guest')
//     ->name('password.email');
// Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
//     ->middleware('guest')
//     ->name('password.store');

// ========================================
// PROTECTED API ROUTES (Require Auth)
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update']);
    Route::post('/benz-consumptions', [BenzConsumptionController::class, 'store'])->middleware('admin');

    // Drivers and admins can see the list
    Route::get('/benz-consumptions', [BenzConsumptionController::class, 'index']);
    // Admin Routes (Require 'admin' role)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('live-locations', [AdminController::class, 'liveLocations']);
        Route::get('statistics', [AdminController::class, 'statistics']);
        Route::get('users', [AdminController::class, 'allUsers']);

        Route::prefix('fare-settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FareSettingsController::class, 'index']);
            Route::put('/', [\App\Http\Controllers\Admin\FareSettingsController::class, 'update']);
        });
    });

    // Driver Routes
    Route::prefix('driver')->group(function () {
        Route::get('profile', [DriverController::class, 'showProfile']);
        Route::put('profile', [DriverController::class, 'updateProfile']);
        Route::post('go-online', [DriverController::class, 'goOnline']);
        Route::post('go-offline', [DriverController::class, 'goOffline']);
        Route::post('location', [DriverController::class, 'updateLocation']);
        Route::put('range', [DriverController::class, 'updateRange']);
        Route::get('activity-logs', [DriverController::class, 'activityLogs']);
        Route::get('my-location', [DriverController::class, 'getMyLocation']);
        Route::get('/', [DriverController::class, 'index']);// should be just for admin
        Route::post('block/{passenger}', [DriverController::class, 'blockPassenger']);
        Route::delete('unblock/{passenger}', [DriverController::class, 'unblockPassenger']);
        Route::get('blocked-passengers', [DriverController::class, 'getBlockedPassengers']);
    });

    // Passenger Routes
    Route::prefix('passenger')->group(function () {
        Route::get('profile', [PassengerController::class, 'profile']);
        Route::put('profile', [PassengerController::class, 'updateProfile']);
        Route::post('location', [PassengerController::class, 'updateLocation']);
        Route::get('rides', [RideController::class, 'index']);
        Route::get('rides/history', [RideController::class, 'history']);
        Route::get('rides/live', [RideController::class, 'current']);
        Route::prefix('favorite-places')->group(function () {
            Route::get('/', [PassengerController::class, 'getFavoritePlaces']);
            Route::post('/', [PassengerController::class, 'saveFavoritePlace']);
            Route::delete('{place}', [PassengerController::class, 'deleteFavoritePlace']);
        });
        Route::prefix('emergency')->group(function () {
            Route::post('rides/{ride}/sos', [PassengerController::class, 'emergencySOS']);
            Route::get('contacts', [PassengerController::class, 'getEmergencyContacts']);
            Route::post('contacts', [PassengerController::class, 'addEmergencyContact']);
        });
        Route::prefix('wallet')->group(function () {
            Route::get('balance', [PassengerController::class, 'getWalletBalance']);
            Route::post('add', [PassengerController::class, 'addToWallet']);
        });
    });

    // Ride Routes
    Route::prefix('rides')->group(function () {
        Route::post('/', [RideController::class, 'store']);
        Route::get('/', [RideController::class, 'index']);
        Route::get('history', [RideController::class, 'history']);
        Route::get('live', [RideController::class, 'current']);
        Route::get('available', [RideController::class, 'availableRides']);
        Route::post('{ride}/accept', [RideController::class, 'acceptRide']);
        Route::post('{ride}/update-location', [RideController::class, 'updateLocation']);
        Route::post('{ride}/arrived', [RideController::class, 'markArrived']);
        Route::post('{ride}/cancel', [RideController::class, 'cancelRide']);
        Route::post('estimate-fare', [RideController::class, 'estimateFare']);
        Route::patch('{ride}/status', [RideController::class, 'updateStatus']);
        Route::post('{ride}/apply-promo', [RideController::class, 'applyPromoCode']);
        Route::post('pool', [RideController::class, 'requestPoolRide']);
        Route::post('{ride}/pay', [PaymentController::class, 'payRide']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AuthController::class, 'getNotifications']);
        Route::post('{notification}/read', [AuthController::class, 'markNotificationRead']);
        Route::post('read-all', [AuthController::class, 'markAllNotificationsRead']);
    });
});
