<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SafeRide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .blob { animation: blob 7s infinite; }
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        .input-focus:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .photo-preview { max-height: 200px; object-fit: cover; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 min-h-screen py-12 px-4">
    
    <!-- Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="blob absolute w-96 h-96 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full opacity-20 blur-3xl -top-20 -left-20"></div>
        <div class="blob absolute w-96 h-96 bg-gradient-to-r from-pink-400 to-blue-400 rounded-full opacity-20 blur-3xl top-40 -right-20" style="animation-delay: 2s;"></div>
        <div class="blob absolute w-96 h-96 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full opacity-20 blur-3xl bottom-20 left-1/3" style="animation-delay: 4s;"></div>
    </div>

    <div class="max-w-2xl mx-auto relative z-10">
        <!-- Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-12">
            
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 rounded-2xl shadow-xl mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">Create Account</h1>
                <p class="text-gray-600">Start your SafeRide journey today</p>
            </div>

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" id="registerForm">
                @csrf

                <!-- Role Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">I am a</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="passenger" class="peer sr-only" checked>
                            <div class="flex items-center justify-center gap-2 p-4 border-2 border-gray-200 rounded-2xl transition-all peer-checked:border-purple-500 peer-checked:bg-purple-500 peer-checked:text-white hover:border-purple-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="font-semibold">Passenger</span>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="role" value="driver" class="peer sr-only">
                            <div class="flex items-center justify-center gap-2 p-4 border-2 border-gray-200 rounded-2xl transition-all peer-checked:border-purple-500 peer-checked:bg-purple-500 peer-checked:text-white hover:border-purple-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span class="font-semibold">Driver</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Name -->
                <div class="mb-5">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="input-focus w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Enter your full name">
                    </div>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="input-focus w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="your@email.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="mb-5">
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                            class="input-focus w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Enter your phone number">
                    </div>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gender -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gender <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="male" class="peer sr-only" {{ old('gender') == 'male' ? 'checked' : '' }}>
                            <div class="p-3 text-center border-2 border-gray-200 rounded-xl transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 font-semibold">Male</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="female" class="peer sr-only" {{ old('gender') == 'female' ? 'checked' : '' }}>
                            <div class="p-3 text-center border-2 border-gray-200 rounded-xl transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 font-semibold">Female</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="other" class="peer sr-only" {{ old('gender') == 'other' ? 'checked' : '' }}>
                            <div class="p-3 text-center border-2 border-gray-200 rounded-xl transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 font-semibold">Other</div>
                        </label>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password" type="password" name="password" required
                            class="input-focus w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Enter your password">
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="input-focus w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Confirm your password">
                    </div>
                </div>

                <!-- Driver-specific Fields -->
                <div id="driverFields" class="hidden">
                    <!-- License Number -->
                    <div class="mb-5">
                        <label for="license_number" class="block text-sm font-semibold text-gray-700 mb-2">License Number</label>
                        <input id="license_number" type="text" name="license_number" value="{{ old('license_number') }}"
                            class="input-focus w-full px-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Enter your license number">
                        @error('license_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vehicle Number -->
                    <div class="mb-5">
                        <label for="vehicle_number" class="block text-sm font-semibold text-gray-700 mb-2">Vehicle Number</label>
                        <input id="vehicle_number" type="text" name="vehicle_number" value="{{ old('vehicle_number') }}"
                            class="input-focus w-full px-4 py-3 border border-gray-300 rounded-xl transition-all outline-none"
                            placeholder="Enter your vehicle number">
                        @error('vehicle_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Section: Vehicle Photos -->
                    <div class="border-t-2 border-gray-200 pt-6 mt-6">
                        <div class="flex items-center gap-3 mb-5">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <h3 class="text-lg font-bold text-gray-800">Vehicle Photos (All 4 Sides)</h3>
                        </div>

                        <!-- Car Photo Front -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Car Photo - Front</label>
                            <input type="file" name="car_photo_front" accept="image/*" class="file-input" data-preview="preview-front">
                            <div id="preview-front" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>

                        <!-- Car Photo Back -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Car Photo - Back</label>
                            <input type="file" name="car_photo_back" accept="image/*" class="file-input" data-preview="preview-back">
                            <div id="preview-back" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>

                        <!-- Car Photo Left -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Car Photo - Left Side</label>
                            <input type="file" name="car_photo_left" accept="image/*" class="file-input" data-preview="preview-left">
                            <div id="preview-left" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>

                        <!-- Car Photo Right -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Car Photo - Right Side</label>
                            <input type="file" name="car_photo_right" accept="image/*" class="file-input" data-preview="preview-right">
                            <div id="preview-right" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Required Documents -->
                    <div class="border-t-2 border-gray-200 pt-6 mt-6">
                        <div class="flex items-center gap-3 mb-5">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-bold text-gray-800">Required Documents</h3>
                        </div>

                        <!-- License Photo -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Driver License Photo</label>
                            <input type="file" name="license_photo" accept="image/*" class="file-input" data-preview="preview-license">
                            <div id="preview-license" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>

                        <!-- ID Photo -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ID Photo</label>
                            <input type="file" name="id_photo" accept="image/*" class="file-input" data-preview="preview-id">
                            <div id="preview-id" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>

                        <!-- Insurance Photo -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Insurance Photo</label>
                            <input type="file" name="insurance_photo" accept="image/*" class="file-input" data-preview="preview-insurance">
                            <div id="preview-insurance" class="mt-3 hidden">
                                <img class="photo-preview rounded-xl border-2 border-gray-200 w-full">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                    Create Account
                </button>

                <!-- Sign In Link -->
                <div class="text-center mt-6">
                    <span class="text-gray-600">Already have an account? </span>
                    <a href="{{ route('login') }}" class="font-bold text-purple-600 hover:text-purple-700">Sign In</a>
                </div>
            </form>
        </div>
    </div>
    <!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-xl">
        <h2 class="text-2xl font-bold mb-4 text-center">Verify OTP</h2>
        <p class="text-gray-600 mb-4 text-center">Enter the OTP sent to your phone</p>
        <input type="text" id="otpCode" placeholder="Enter OTP"
               class="w-full border border-gray-300 rounded-xl px-4 py-3 mb-4 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none">
        <p id="otpError" class="text-red-600 text-sm mb-3 hidden"></p>
        <button id="verifyOtpBtn" class="w-full bg-purple-500 text-white py-3 rounded-xl font-bold hover:bg-purple-600 transition">Verify OTP</button>
        <p class="text-gray-500 mt-3 text-sm text-center">
            Didn't receive OTP? <button id="resendOtpBtn" class="text-purple-600 font-bold hover:underline">Resend</button>
        </p>
    </div>
</div>


    <script>
        // Toggle driver fields based on role selection
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const driverFields = document.getElementById('driverFields');
        
        roleInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'driver') {
                    driverFields.classList.remove('hidden');
                    // Make driver fields required
                    document.querySelectorAll('#driverFields input[type="text"], #driverFields input[type="file"]').forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                } else {
                    driverFields.classList.add('hidden');
                    // Remove required from driver fields
                    document.querySelectorAll('#driverFields input[type="text"], #driverFields input[type="file"]').forEach(field => {
                        field.removeAttribute('required');
                    });
                }
            });
        });

        // Image preview functionality
        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const previewId = this.getAttribute('data-preview');
                const previewContainer = document.getElementById(previewId);
                const img = previewContainer.querySelector('img');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.classList.add('hidden');
                }
            });
        });

        // Add styling to file inputs
        document.querySelectorAll('.file-input').forEach(input => {
            input.className = 'file-input block w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer';
        });
    </script>
    <script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault(); // prevent normal form submission

    const formData = new FormData(this);

    try {
      // Register
const response = await fetch('/register', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: formData
});

        let data;
try {
    data = await response.json();
} catch {
    data = { error: 'Server returned non-JSON response' };
}

        if (response.ok) {
            // Show OTP modal
            document.getElementById('otpModal').classList.remove('hidden');
            // Store phone for OTP verification
            window.registerPhone = formData.get('phone');
        } else {
            // Show validation errors
            alert(JSON.stringify(data.errors));
        }
    } catch (err) {
        console.error(err);
        alert('Something went wrong. Try again.');
    }
});
</script>
<script>
document.getElementById('verifyOtpBtn').addEventListener('click', async function() {
    const otp = document.getElementById('otpCode').value;
    const errorEl = document.getElementById('otpError');
    errorEl.classList.add('hidden');

    if (!otp) {
        errorEl.textContent = 'Please enter OTP';
        errorEl.classList.remove('hidden');
        return;
    }

    try {
       // OTP verify
const response = await fetch('/api/verify-otp', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
        phone: window.registerPhone,
        code: otp
    })
});


        const data = await response.json();

        if (response.ok) {
            alert('OTP verified! Registration complete.');
            window.location.href = '/login'; // redirect to login
        } else {
            errorEl.textContent = data.error || 'Invalid OTP';
            errorEl.classList.remove('hidden');
        }
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Something went wrong';
        errorEl.classList.remove('hidden');
    }
});
</script>
<script>
document.getElementById('resendOtpBtn').addEventListener('click', async function() {
    try {
       // Resend OTP
const response = await fetch('/api/resend-otp', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ phone: window.registerPhone })
});


        const data = await response.json();
        if (response.ok) {
            alert('OTP resent successfully!');
        } else {
            alert(data.error || 'Failed to resend OTP');
        }
    } catch (err) {
        console.error(err);
        alert('Something went wrong');
    }
});
</script>

</body>
</html>