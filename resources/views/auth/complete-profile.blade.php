@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Complete Your Driver Profile</h2>
            <p class="text-gray-600">Please provide all required information to start accepting rides</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('driver.complete-profile.store') }}" method="POST" enctype="multipart/form-data" id="driverProfileForm">
                @csrf

                <!-- Personal Information Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">1</span>
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- License Number -->
                        <div>
                            <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Driver License Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="license_number" id="license_number" required
                                   value="{{ old('license_number') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                                   placeholder="e.g., DL123456789">
                            @error('license_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Vehicle Type -->
                        <div>
                            <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Vehicle Type <span class="text-red-500">*</span>
                            </label>
                            <select name="vehicle_type" id="vehicle_type" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                <option value="">Select vehicle type</option>
                                <option value="sedan" {{ old('vehicle_type') == 'sedan' ? 'selected' : '' }}>Sedan</option>
                                <option value="suv" {{ old('vehicle_type') == 'suv' ? 'selected' : '' }}>SUV</option>
                                <option value="hatchback" {{ old('vehicle_type') == 'hatchback' ? 'selected' : '' }}>Hatchback</option>
                                <option value="van" {{ old('vehicle_type') == 'van' ? 'selected' : '' }}>Van</option>
                                <option value="luxury" {{ old('vehicle_type') == 'luxury' ? 'selected' : '' }}>Luxury</option>
                            </select>
                            @error('vehicle_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Vehicle Number -->
                        <div class="md:col-span-2">
                            <label for="vehicle_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Vehicle Registration Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="vehicle_number" id="vehicle_number" required
                                   value="{{ old('vehicle_number') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                                   placeholder="e.g., ABC-1234">
                            @error('vehicle_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Vehicle Photos Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">2</span>
                        Vehicle Photos (All 4 Sides)
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Car Photo Front -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Front View <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="car_photo_front" id="car_photo_front" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_front')">
                                <div class="text-center">
                                    <img id="preview_front" class="hidden mx-auto h-32 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-600">Click to upload front view</p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 5MB</p>
                                </div>
                            </div>
                            @error('car_photo_front')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Car Photo Back -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Back View <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="car_photo_back" id="car_photo_back" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_back')">
                                <div class="text-center">
                                    <img id="preview_back" class="hidden mx-auto h-32 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-600">Click to upload back view</p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 5MB</p>
                                </div>
                            </div>
                            @error('car_photo_back')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Car Photo Left -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Left Side <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="car_photo_left" id="car_photo_left" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_left')">
                                <div class="text-center">
                                    <img id="preview_left" class="hidden mx-auto h-32 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-600">Click to upload left side</p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 5MB</p>
                                </div>
                            </div>
                            @error('car_photo_left')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Car Photo Right -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Right Side <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="car_photo_right" id="car_photo_right" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_right')">
                                <div class="text-center">
                                    <img id="preview_right" class="hidden mx-auto h-32 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-600">Click to upload right side</p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 5MB</p>
                                </div>
                            </div>
                            @error('car_photo_right')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">3</span>
                        Required Documents
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- License Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Driver License <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="license_photo" id="license_photo" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_license')">
                                <div class="text-center">
                                    <img id="preview_license" class="hidden mx-auto h-24 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-xs text-gray-600 mt-1">Upload license</p>
                                </div>
                            </div>
                            @error('license_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ID Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                National ID <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="id_photo" id="id_photo" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_id')">
                                <div class="text-center">
                                    <img id="preview_id" class="hidden mx-auto h-24 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-xs text-gray-600 mt-1">Upload ID</p>
                                </div>
                            </div>
                            @error('id_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Insurance Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vehicle Insurance <span class="text-red-500">*</span>
                            </label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-500 transition cursor-pointer">
                                <input type="file" name="insurance_photo" id="insurance_photo" required accept="image/jpeg,image/png,image/jpg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       onchange="previewImage(this, 'preview_insurance')">
                                <div class="text-center">
                                    <img id="preview_insurance" class="hidden mx-auto h-24 w-auto rounded-lg mb-2">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-xs text-gray-600 mt-1">Upload insurance</p>
                                </div>
                            </div>
                            @error('insurance_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="mb-8">
                    <div class="flex items-start">
                        <input type="checkbox" id="terms" required
                               class="mt-1 h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="terms" class="ml-2 text-sm text-gray-700">
                            I agree to the <a href="#" class="text-purple-600 hover:underline">Terms and Conditions</a> and confirm that all information provided is accurate and up to date.
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-6 border-t">
                    <p class="text-sm text-gray-600">
                        <span class="text-red-500">*</span> Required fields
                    </p>
                    <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-8 py-3 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                        Submit for Approval
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Your profile will be reviewed by our admin team within 24-48 hours. You'll receive a notification once your account is approved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            // Hide the upload icon
            const parent = preview.parentElement;
            const svg = parent.querySelector('svg');
            const text = parent.querySelectorAll('p');
            if (svg) svg.classList.add('hidden');
            text.forEach(p => p.classList.add('hidden'));
        }
        reader.readAsDataURL(file);
    }
}

// Form submission handling
document.getElementById('driverProfileForm').addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';
});
</script>
@endsection