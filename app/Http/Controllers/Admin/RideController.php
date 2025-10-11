<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index()
    {
        $rides = Ride::with(['passenger', 'driver'])->paginate(10);
        return view('admin.rides.index', compact('rides'));
    }

    public function show(Ride $ride)
    {
        return view('admin.rides.show', compact('ride'));
    }

    public function update(Request $request, Ride $ride)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,arrived,cancelled',
        ]);

        $ride->update($request->only('status'));

        return redirect()->route('admin.rides.index')->with('success', 'Ride updated successfully.');
    }

    public function destroy(Ride $ride)
    {
        $ride->delete();
        return redirect()->route('admin.rides.index')->with('success', 'Ride deleted successfully.');
    }
    
}
