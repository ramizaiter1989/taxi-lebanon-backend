@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>

    <!-- Map container -->
    <div id="map" style="height: 600px; width: 100%;"></div>
</div>
@endsection

@push('scripts')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Mapbox Polyline -->
<script src="https://cdn.jsdelivr.net/npm/@mapbox/polyline@1.1.1/src/polyline.js"></script>

<script>
    // Laravel API token for requests
    window.apiToken = "{{ auth()->user()->createToken('api-token')->plainTextToken }}";

    document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('map').setView([34.1657, 35.9515], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let driverMarkers = {};

    fetch('/api/drivers', {
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
        },
    })
    .then(res => res.json())
    .then(drivers => {
        drivers.forEach(driver => {
            const driverIcon = L.icon({
                iconUrl: '/images/car-icon.png',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            driverMarkers[driver.id] = L.marker([driver.current_driver_lat, driver.current_driver_lng], {icon: driverIcon})
                .addTo(map)
                .bindPopup('Driver: ' + driver.name);

            if(driver.current_route){
                const coords = window.polyline.decode(driver.current_route);
                L.polyline(coords, {color: 'blue', weight: 3, opacity: 0.7}).addTo(map);
            }
        });
    });
});
</script>
@endpush
