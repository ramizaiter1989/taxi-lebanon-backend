<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\RideController;
use App\Http\Controllers\PassengerController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Admin\PassengerController as AdminPassengerController;
use Illuminate\Support\Facades\Broadcast;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Home page - accessible by anyone
Route::get('/', function () {
    return view('welcome');
});
Route::get('/fare-estimator', function () {
    return view('admin.rides.fare-settings ');
});
Route::get('/test-mail', function() {
    Mail::raw('This is a test email', function($message) {
        $message->to('ramizaiter1989@gmail.com') // you can use any email
                ->subject('Laravel Mail Test');
    });

    return 'Mail sent!';
});

/*
|--------------------------------------------------------------------------
| Auth & User Routes (Breeze)
|--------------------------------------------------------------------------
*/

// Authenticated users only
Route::middleware(['auth', 'verified'])->group(function () {

    // User dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

     // Passenger management within rides
    Route::get('/passenger', [PassengerController::class, 'index'])->name('passenger.map');
    Route::post('/passenger/ride', [PassengerController::class, 'storeRide'])->name('passenger.ride.store');

    Route::get('/my-rides', [PassengerController::class, 'myRides'])->name('passenger.rides');

    Route::get('/passenger/profile', [PassengerController::class, 'profile'])->name('passenger.profile');
    Route::post('/passenger/profile', [PassengerController::class, 'updateProfile'])->name('passenger.updateProfile');
    
});

// Load Breeze auth routes: /login, /register, /logout, /forgot-password, etc.
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Accessible only by authenticated users with 'admin' role
| via AdminMiddleware. All routes are prefixed with /admin
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Admin dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Drivers management
    Route::get('/drivers', [DriverController::class, 'indexAdmin'])->name('drivers.index');
    Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
    Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
    Route::put('/drivers/{driver}/toggle-lock', [DriverController::class, 'toggleLock'])->name('drivers.toggleLock');


    // Rides management
    Route::get('/rides', [RideController::class, 'index'])->name('rides.index');
    Route::get('/rides/{ride}', [RideController::class, 'show'])->name('rides.show');
    Route::put('/rides/{ride}', [RideController::class, 'update'])->name('rides.update');
    Route::delete('/rides/{ride}', [RideController::class, 'destroy'])->name('rides.destroy');

    //admin passenger management
    Route::get('/passengers', [AdminPassengerController::class, 'index'])->name('passengers.index');
    Route::get('/passengers/{passenger}/edit', [AdminPassengerController::class, 'edit'])->name('passengers.edit');
    Route::put('/passengers/{passenger}', [AdminPassengerController::class, 'update'])->name('passengers.update');
    Route::delete('/passengers/{passenger}', [AdminPassengerController::class, 'destroy'])->name('passengers.destroy');

    Route::patch('/passengers/{passenger}/lock', [AdminPassengerController::class, 'lock'])->name('passengers.lock');
    Route::patch('/passengers/{passenger}/unlock', [AdminPassengerController::class, 'unlock'])->name('passengers.unlock');

   
});
