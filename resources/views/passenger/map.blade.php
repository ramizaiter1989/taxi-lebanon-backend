<!DOCTYPE html>
<html>
<head>
    <title>Passenger Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 500px; width: 100%; }
    </style>
</head>
<body>
<h2>Passenger Map</h2>
<p>Distance: <span id="distance">--</span> km | Duration: <span id="duration">--</span> min</p>

<form id="rideForm" method="POST" action="{{ route('passenger.ride.store') }}">
    @csrf
    <input type="hidden" name="current_lat" id="current_lat">
    <input type="hidden" name="current_lng" id="current_lng">
    <input type="hidden" name="destination_lat" id="destination_lat">
    <input type="hidden" name="destination_lng" id="destination_lng">
    <button type="submit">Confirm Ride</button>
</form>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
let map, currentLocation, destinationMarker, routeLine;

navigator.geolocation.getCurrentPosition(async function(position) {
    currentLocation = [position.coords.latitude, position.coords.longitude];
    document.getElementById('current_lat').value = currentLocation[0];
    document.getElementById('current_lng').value = currentLocation[1];

    map = L.map('map').setView(currentLocation, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker(currentLocation).addTo(map).bindPopup("Your Location").openPopup();

    map.on('click', async function(e) {
        const dest = [e.latlng.lat, e.latlng.lng];
        document.getElementById('destination_lat').value = dest[0];
        document.getElementById('destination_lng').value = dest[1];

        if(destinationMarker) map.removeLayer(destinationMarker);
        if(routeLine) map.removeLayer(routeLine);

        destinationMarker = L.marker(dest).addTo(map).bindPopup("Destination").openPopup();

        // 🚀 Call your Laravel proxy instead of ORS directly
        const response = await fetch(
            `/api/directions?start=${currentLocation[1]},${currentLocation[0]}&end=${dest[1]},${dest[0]}`
        );
        const data = await response.json();

        // Draw route
        const coords = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
        routeLine = L.polyline(coords, { color: 'blue' }).addTo(map);

        // Distance + duration
        const summary = data.features[0].properties.summary;
        document.getElementById('distance').innerText = (summary.distance / 1000).toFixed(2);
        document.getElementById('duration').innerText = (summary.duration / 60).toFixed(1);
    });
});
</script>

</body>
</html>
