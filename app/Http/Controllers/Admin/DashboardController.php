<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\User;
use App\Models\Driver;

class DashboardController extends Controller
{
    public function index()
    {
        // Example statistics
        $totalUsers = User::count();
        $totalDrivers = Driver::count();
        $totalRides = Ride::count();
        $activeRides = Ride::where('status', 'ongoing')->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalDrivers',
            'totalRides',
            'activeRides'
        ));
    }
}
