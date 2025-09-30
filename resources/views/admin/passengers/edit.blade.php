@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white shadow-xl rounded-2xl p-6 my-8">
        <h2 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-2">Edit Passenger</h2>

        <form action="{{ route('admin.passengers.update', $passenger->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Name Field -->
            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $passenger->name) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Email Field -->
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="text" name="email" id="email" value="{{ old('email', $passenger->email) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Phone Field -->
            <div class="mb-5">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $passenger->phone) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Gender Field -->
            <div class="mb-6">
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" id="gender" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="male" {{ $passenger->gender === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $passenger->gender === 'female' ? 'selected' : '' }}>Female</option>
                </select>
                @error('gender') <p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.passengers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition duration-150 ease-in-out">
                    Back
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                    Update Passenger
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
