@extends('layouts.app')

@section('title', 'Fare Settings')

@section('content')
<div class="max-w-2xl mx-auto mt-10 bg-white shadow-md rounded-xl p-6">
    <h1 class="text-2xl font-semibold mb-6 text-gray-800">Fare Settings</h1>

    <!-- Alert messages -->
    <div id="alert-container" class="mb-4 hidden">
        <div id="alert-message" class="p-3 rounded-lg text-white"></div>
    </div>

    <form id="fareSettingsForm" class="space-y-4">
        @csrf

        <div>
            <label class="block text-gray-700 font-medium">Base Fare (€)</label>
            <input type="number" step="0.01" id="base_fare" name="base_fare" class="w-full border-gray-300 rounded-lg shadow-sm p-2 focus:ring focus:ring-blue-200" required>
        </div>

        <div>
            <label class="block text-gray-700 font-medium">Per KM Rate (€)</label>
            <input type="number" step="0.01" id="per_km_rate" name="per_km_rate" class="w-full border-gray-300 rounded-lg shadow-sm p-2 focus:ring focus:ring-blue-200" required>
        </div>

        <div>
            <label class="block text-gray-700 font-medium">Per Minute Rate (€)</label>
            <input type="number" step="0.01" id="per_minute_rate" name="per_minute_rate" class="w-full border-gray-300 rounded-lg shadow-sm p-2 focus:ring focus:ring-blue-200" required>
        </div>

        <div>
            <label class="block text-gray-700 font-medium">Minimum Fare (€)</label>
            <input type="number" step="0.01" id="minimum_fare" name="minimum_fare" class="w-full border-gray-300 rounded-lg shadow-sm p-2">
        </div>

        <div>
            <label class="block text-gray-700 font-medium">Cancellation Fee (€)</label>
            <input ty
