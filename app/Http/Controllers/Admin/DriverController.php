<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class DriverController extends Controller
{
    // Admin index page
    public function indexAdmin()
    {
        $drivers = Driver::with('user')->get();
        return view('admin.drivers.index', compact('drivers'));
    }

    // Show edit form
   public function edit(Driver $driver)
    {
        // The Blade file needs the $driver object.
        return view('admin.drivers.edit', compact('driver'));
    }

    /**
     * Update the specified driver in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        // 1. Validation (Ensures fields are optional, as requested)
        $data = $request->validate([
            'license_number' => ['nullable', 'string', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'vehicle_type' => ['nullable', 'string', 'in:car,motorcycle,van,truck'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'availability_status' => ['nullable', 'boolean'],
            'current_driver_lat' => ['nullable', 'numeric'],
            'current_driver_lng' => ['nullable', 'numeric'],
            'scanning_range_km' => ['nullable', 'numeric', 'min:0'],
            'active_at' => ['nullable', 'date'],
            'inactive_at' => ['nullable', 'date', 'after_or_equal:active_at'],
            
            // Photo fields are nullable and must be image files if present
            'car_photo' => ['nullable', 'image', 'max:2048'],
            'license_photo' => ['nullable', 'image', 'max:2048'],
            'id_photo' => ['nullable', 'image', 'max:2048'],
            'insurance_photo' => ['nullable', 'image', 'max:2048'],
            'car_photo_front' => ['nullable', 'image', 'max:2048'], // optional
            'car_photo_back' => ['nullable', 'image', 'max:2048'], // optional
            'car_photo_left' => ['nullable', 'image', 'max:2048'], // optional
            'car_photo_right' => ['nullable', 'image', 'max:2048'], // optional
            // Checkboxes for photo removal
            'remove_car_photo' => ['nullable', 'boolean'],
            'remove_car_photo_front' => ['nullable', 'boolean'],
            'remove_car_photo_back' => ['nullable', 'boolean'],
            'remove_car_photo_left' => ['nullable', 'boolean'],
            'remove_car_photo_right' => ['nullable', 'boolean'],
            'remove_license_photo' => ['nullable', 'boolean'],
            'remove_id_photo' => ['nullable', 'boolean'],
            'remove_insurance_photo' => ['nullable', 'boolean'],
        ]);

        // 2. Prepare update data
        $updateData = [];

        // Simple text/number fields
        $simpleFields = [
            'license_number', 'vehicle_type', 'vehicle_number', 'rating', 
            'availability_status', 'current_driver_lat', 'current_driver_lng', 
            'scanning_range_km', 'active_at', 'inactive_at'
        ];

        foreach ($simpleFields as $field) {
            // Only update the field if it was explicitly submitted (not null)
            // For boolean/numeric fields that might legitimately be 0, we check if the key exists.
            if ($request->has($field)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // 3. Handle File Uploads and Removals
        $photoFields = ['car_photo', 'license_photo', 'id_photo', 'insurance_photo'];

        foreach ($photoFields as $field) {
            // Check if the admin wants to remove the existing photo
            $removeField = 'remove_' . $field;
            if ($request->has($removeField) && $data[$removeField]) {
                if ($driver->$field) {
                    Storage::disk('public')->delete($driver->$field);
                }
                $updateData[$field] = null; // Set the database field to null
            } 
            // Check if a new file was uploaded
            elseif ($request->hasFile($field)) {
                // Delete old photo if it exists
                if ($driver->$field) {
                    Storage::disk('public')->delete($driver->$field);
                }
                // Store the new file and save the path
                $updateData[$field] = $request->file($field)->store('drivers/photos', 'public');
            }
            // If neither remove nor new file, we do nothing to allow partial update
        }

        // 4. Update the Driver model
        $driver->update($updateData);

        return redirect()->route('admin.drivers.edit', $driver->id)
                         ->with('success', 'Driver details updated successfully!');
    }

    // Delete driver
    public function destroy(Driver $driver)
    {
        $this->authorize('delete', $driver); // admin only

        $driver->delete();

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver deleted successfully.');
    }

    // Toggle lock/unlock driver quickly
    public function toggleLock(Driver $driver)
    {
        $this->authorize('update', $driver); // admin only

        if ($driver->user) {
            $driver->user->is_locked = !$driver->user->is_locked;
            $driver->user->save();
        }

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver lock status updated.');
    }
}
