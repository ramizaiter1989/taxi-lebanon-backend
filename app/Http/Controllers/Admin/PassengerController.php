<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    // 📝 Show all passengers
    public function index()
    {
        $passengers = User::where('role', 'passenger')->latest()->paginate(10);
        return view('admin.passengers.index', compact('passengers'));
    }

    // ✏️ Edit passenger form
    public function edit(User $passenger)
    {
        return view('admin.passengers.edit', compact('passenger'));
    }

    // ✅ Update passenger
    public function update(Request $request, User $passenger)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . $passenger->id,
            'phone'  => 'nullable|string|max:20|unique:users,phone,' . $passenger->id,
            'gender' => 'in:male,female',
        ]);

        $passenger->update($request->only(['name', 'email', 'phone', 'gender']));

        return redirect()->route('admin.passengers.index')->with('success', 'Passenger updated successfully.');
    }

    // 🔒 Lock/Unlock passenger account
    public function lock(User $passenger)
{
    $passenger->update(['is_locked' => true]);
    return redirect()->route('admin.passengers.index')->with('success', 'Passenger account locked.');
}

public function unlock(User $passenger)
{
    $passenger->update(['is_locked' => false]);
    return redirect()->route('admin.passengers.index')->with('success', 'Passenger account unlocked.');
}


    // 🗑️ Delete passenger
    public function destroy(User $passenger)
    {
        $passenger->delete();
        return redirect()->route('admin.passengers.index')->with('success', 'Passenger deleted successfully.');
    }
    
}
