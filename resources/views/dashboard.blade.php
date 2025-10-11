@extends('layouts.app')

@section('title', ucfirst(auth()->user()->role) . ' Dashboard')

@section('content')
<div class="flex flex-col h-screen">
    <!-- CSRF Token for Broadcasting Auth -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    window.apiToken = '{{ auth()->user()->createToken("map-token")->plainTextToken }}';
    window.userRole = '{{ auth()->user()->role }}';
    window.userId = {{ auth()->id() }};
    window.driverId = {{ auth()->user()->driver?->id ?? 'null' }};
</script>
    
    <!-- Top bar -->
    <div class="bg-white shadow p-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">{{ ucfirst(auth()->user()->role) }} Dashboard</h1>
            <p class="text-gray-600">Welcome, {{ auth()->user()->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <div id="location-status" class="flex items-center gap-2 px-3 py-1 rounded-full bg-gray-100">
                <span class="w-2 h-2 rounded-full bg-gray-400" id="status-indicator"></span>
                <span class="text-sm text-gray-600" id="status-text">Connecting...</span>
            </div>
        </div>
    </div>

    <!-- Map container -->
    <div id="map" class="flex-1 min-h-[500px]"></div>
</div>

<style>
    .location-button.active {
        background-color: #4285F4 !important;
        color: white !important;
    }
    
    #status-indicator.active {
        background-color: #10b981;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endsection

@push('scripts')
<!-- Polyline library for routes -->
<script src="https://cdn.jsdelivr.net/npm/@mapbox/polyline@1.1.1/src/polyline.js"></script>

<!-- Pass data to JavaScript -->
<script>
    // IMPORTANT: Pass user location to JavaScript
    @if(auth()->user()->role === 'admin')
        window.userLocation = {
            lat: {{ auth()->user()->current_lat ?? 34.1657 }},
            lng: {{ auth()->user()->current_lng ?? 35.9515 }},
            name: '{{ auth()->user()->name }}'
        };
    @elseif(auth()->user()->role === 'driver' && auth()->user()->driver)
        window.userLocation = {
            lat: {{ auth()->user()->driver->current_driver_lat ?? 34.1657 }},
            lng: {{ auth()->user()->driver->current_driver_lng ?? 35.9515 }},
            name: '{{ auth()->user()->name }}'
        };
    @elseif(auth()->user()->role === 'passenger')
        window.userLocation = {
            lat: {{ auth()->user()->current_lat ?? 34.1657 }},
            lng: {{ auth()->user()->current_lng ?? 35.9515 }},
            name: '{{ auth()->user()->name }}'
        };
    @endif

    // Pass authentication data
    window.userRole = '{{ auth()->user()->role }}';
    window.userId = {{ auth()->user()->id }};
    @if(auth()->user()->driver)
        window.driverId = {{ auth()->user()->driver->id }};
    @endif
    
    // Generate API token for this session
    window.apiToken = '{{ auth()->user()->createToken("dashboard-token")->plainTextToken }}';
</script>

<!-- Load compiled Vite JS -->
@vite('resources/js/dashboard.js')
@endpush