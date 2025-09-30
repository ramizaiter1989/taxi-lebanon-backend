@extends('layouts.app')

@section('title', 'Edit Driver: ' . $driver->user->name)

@section('content')
<div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 bg-white shadow-xl rounded-2xl my-10">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">Edit Driver Details</h1>
    
    <!-- Driver Info Context -->
    <div class="mb-6 p-4 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
        <p class="font-semibold text-lg text-indigo-700">Driver: {{ $driver->user->name }} (ID: {{ $driver->user_id }})</p>
        <p class="text-sm text-indigo-600">Email: {{ $driver->user->email }} | Phone: {{ $driver->user->phone ?? 'N/A' }}</p>
    </div>

    <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Core Driver Details Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            
            <!-- License Number -->
            <div>
                <label for="license_number" class="block text-sm font-medium text-gray-700">License Number</label>
                <input type="text" name="license_number" id="license_number" value="{{ old('license_number', $driver->license_number) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('license_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Vehicle Type -->
            <div>
                <label for="vehicle_type" class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                <select name="vehicle_type" id="vehicle_type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @php $types = ['car', 'motorcycle', 'van', 'truck']; @endphp
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ old('vehicle_type', $driver->vehicle_type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            
            <!-- Vehicle Number -->
            <div>
                <label for="vehicle_number" class="block text-sm font-medium text-gray-700">Vehicle Plate Number</label>
                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number', $driver->vehicle_number) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('vehicle_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Rating -->
            <div>
                <label for="rating" class="block text-sm font-medium text-gray-700">Rating (3.0 to 5.0)</label>
                <input type="number" step="0.1" min="0" max="5" name="rating" id="rating" value="{{ old('rating', $driver->rating) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('rating')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- Location and Availability Section -->
        <h2 class="text-xl font-semibold text-gray-700 mb-4 mt-8 border-b pb-2">Location & Status</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <!-- Current Lat -->
            <div>
                <label for="current_driver_lat" class="block text-sm font-medium text-gray-700">Current Latitude</label>
                <input type="number" step="any" name="current_driver_lat" id="current_driver_lat" value="{{ old('current_driver_lat', $driver->current_driver_lat) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('current_driver_lat')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            
            <!-- Current Lng -->
            <div>
                <label for="current_driver_lng" class="block text-sm font-medium text-gray-700">Current Longitude</label>
                <input type="number" step="any" name="current_driver_lng" id="current_driver_lng" value="{{ old('current_driver_lng', $driver->current_driver_lng) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('current_driver_lng')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            
            <!-- Scanning Range -->
            <div>
                <label for="scanning_range_km" class="block text-sm font-medium text-gray-700">Scanning Range (km)</label>
                <input type="number" step="any" min="0" name="scanning_range_km" id="scanning_range_km" value="{{ old('scanning_range_km', $driver->scanning_range_km) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('scanning_range_km')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Availability Status -->
            <div class="md:col-span-3">
                <label for="availability_status" class="block text-sm font-medium text-gray-700">Availability Status</label>
                <select name="availability_status" id="availability_status" class="mt-1 block w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="1" {{ old('availability_status', $driver->availability_status) ? 'selected' : '' }}>Available (True)</option>
                    <option value="0" {{ !old('availability_status', $driver->availability_status) ? 'selected' : '' }}>Unavailable (False)</option>
                </select>
                @error('availability_status')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        
        <!-- Active Timestamps -->
        <h2 class="text-xl font-semibold text-gray-700 mb-4 mt-8 border-b pb-2">Active Durations</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label for="active_at" class="block text-sm font-medium text-gray-700">Last Active At</label>
                <input type="datetime-local" name="active_at" id="active_at" 
                       value="{{ old('active_at', $driver->active_at ? $driver->active_at->format('Y-m-d\TH:i') : '') }}" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('active_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="inactive_at" class="block text-sm font-medium text-gray-700">Last Inactive At</label>
                <input type="datetime-local" name="inactive_at" id="inactive_at" 
                       value="{{ old('inactive_at', $driver->inactive_at ? $driver->inactive_at->format('Y-m-d\TH:i') : '') }}" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('inactive_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- Photo Upload Section -->
        <h2 class="text-xl font-semibold text-gray-700 mb-4 mt-8 border-b pb-2">Documents & Photos</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            
            @php $photoFields = ['car_photo' => 'Car Photo', 'license_photo' => 'License Photo', 'id_photo' => 'ID Photo', 'insurance_photo' => 'Insurance Photo']; @endphp
            
            @foreach($photoFields as $field => $label)
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                <div class="mt-1 flex flex-col space-y-2">
                    <!-- Current Photo Display (assuming they are URLs) -->
                    @if($driver->$field)
                    <div class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200">
                        <img src="{{ asset('storage/' . $driver->$field) }}" alt="Current {{ $label }}" class="w-full h-full object-cover">
                        <span class="absolute top-0 right-0 bg-green-500 text-white text-xs font-semibold px-2 py-0.5 rounded-bl-lg">Current</span>
                    </div>
                    
                    <!-- Checkbox to Remove Existing Photo -->
                    <div class="flex items-center mt-2">
                        <input type="checkbox" name="remove_{{ $field }}" id="remove_{{ $field }}" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <label for="remove_{{ $field }}" class="ml-2 block text-sm text-red-600 font-medium">Remove existing {{ $label }}</label>
                    </div>

                    @else
                    <div class="text-sm text-gray-500">No current photo uploaded.</div>
                    @endif
                    
                    <!-- New File Input -->
                    <input type="file" name="{{ $field }}" id="{{ $field }}" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                @error($field)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @endforeach
        </div>

        <!-- Save Button -->
        <div class="flex justify-end mt-10">
            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out transform hover:scale-[1.01]">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-3m-4-7l4-4m0 0l-4-4m4 4h-9"></path></svg>
                Save Driver Changes
            </button>
        </div>
    </form>
</div>
@endsection
