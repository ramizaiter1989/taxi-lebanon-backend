import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Initialize Laravel Echo
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
    }
});
window.Pusher = Pusher;

// Icons
const icons = {
    driver: L.icon({
        iconUrl: '/images/car-icon.png',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40],
    }),
    passenger: L.icon({
        iconUrl: '/images/passenger.png',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40],
    }),
    admin: L.icon({
        iconUrl: '/images/admin.png',
        iconSize: [50, 50],
        iconAnchor: [25, 50],
        popupAnchor: [0, -50],
    }),
    destination: L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    }),
    pickup: L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    })
};

// Global variables
let map;
let clusterGroup;
let selfMarker = null;
let accuracyCircle = null;
let destinationMarker = null;
let routePolyline = null;
let selectedDestination = null;
let rideInfo = {
    distance: null,
    duration: null,
    destination_lat: null,
    destination_lng: null
};
let currentRideId = null;
let activeRideMarkers = {};
let passengerTrackingMarker = null;
let driverTrackingMarker = null;
let allMarkers = {};
let watchId = null;
// User info
let userRole;
let userId;
let driverId;
let userIcon;

// Helper Functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;

    if (type === 'success') {
        notification.className += ' bg-green-500 text-white';
    } else if (type === 'error') {
        notification.className += ' bg-red-500 text-white';
    } else {
        notification.className += ' bg-blue-500 text-white';
    }

    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function setDestination(lat, lng) {
    if (!selfMarker) {
        showNotification('Please wait for your location to be detected', 'error');
        return;
    }
    if (destinationMarker) {
        map.removeLayer(destinationMarker);
    }
    if (routePolyline) {
        map.removeLayer(routePolyline);
    }
    destinationMarker = L.marker([lat, lng], { icon: icons.destination })
        .bindPopup('<b>Destination</b><br>Calculating route...')
        .addTo(map);
    selectedDestination = { lat, lng };
    rideInfo.destination_lat = lat;
    rideInfo.destination_lng = lng;
    fetchRoute(selfMarker.getLatLng().lat, selfMarker.getLatLng().lng, lat, lng);
}

function fetchRoute(startLat, startLng, endLat, endLng, color = '#3b82f6') {
    const start = `${startLng},${startLat}`;
    const end = `${endLng},${endLat}`;
    showNotification('Calculating route...', 'info');
    fetch(`/api/directions?start=${start}&end=${end}`, {
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Failed to fetch route');
        return res.json();
    })
    .then(data => {
        if (data.features && data.features[0]) {
            const route = data.features[0];
            const coords = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);

            if (routePolyline) {
                map.removeLayer(routePolyline);
            }
            routePolyline = L.polyline(coords, {
                color: color,
                weight: 4,
                opacity: 0.7
            }).addTo(map);

            const distance = route.properties.segments[0].distance;
            const duration = route.properties.segments[0].duration;

            rideInfo.distance = distance;
            rideInfo.duration = duration;
            const distanceKm = (distance / 1000).toFixed(2);
            const durationMin = Math.ceil(duration / 60);

            if (userRole === 'passenger' && destinationMarker) {
                fetch('/api/rides/estimate-fare', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${window.apiToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        distance: distanceKm,
                        duration: durationMin
                    })
                })
                .then(res => res.json())
                .then(fareData => {
                    if (destinationMarker) {
                        destinationMarker.setPopupContent(
                            `<b>Destination</b><br>
                            Distance: ${distanceKm} km<br>
                            Duration: ${durationMin} min<br>
                            Estimated Fare: $${fareData.estimated_fare}<br>
                            <button onclick="window.requestRide()" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                Request Ride
                            </button>`
                        );
                    }
                })
                .catch(() => {
                    if (destinationMarker) {
                        destinationMarker.setPopupContent(
                            `<b>Destination</b><br>
                            Distance: ${distanceKm} km<br>
                            Duration: ${durationMin} min<br>
                            <button onclick="window.requestRide()" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                Request Ride
                            </button>`
                        );
                    }
                });
            }

            map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
            showNotification(`Route found: ${distanceKm} km, ${durationMin} min`, 'success');
        } else {
            throw new Error('No route found');
        }
    })
    .catch(err => {
        console.error('Error fetching route:', err);
        showNotification('Failed to calculate route. Please try again.', 'error');
        if (destinationMarker) {
            destinationMarker.setPopupContent('<b>Destination</b><br>Route calculation failed');
        }
    });
}


function displayRideRequest(rideData) {
    const rideKey = `ride-${rideData.id}`;

    // Remove old markers if they exist
    if (activeRideMarkers[rideKey]) {
        if (activeRideMarkers[rideKey].pickup) map.removeLayer(activeRideMarkers[rideKey].pickup);
        if (activeRideMarkers[rideKey].destination) map.removeLayer(activeRideMarkers[rideKey].destination);
        if (activeRideMarkers[rideKey].route) map.removeLayer(activeRideMarkers[rideKey].route);
    }
    // Create pickup marker
    const pickupMarker = L.marker([rideData.origin_lat, rideData.origin_lng], {
        icon: icons.pickup
    }).addTo(map);
    // Create destination marker
    const destMarker = L.marker([rideData.destination_lat, rideData.destination_lng], {
        icon: icons.destination
    }).addTo(map);
    // Calculate distance from driver to pickup
    const driverPos = selfMarker ? selfMarker.getLatLng() : null;
    let distanceToPickup = '';
    if (driverPos) {
        const dist = map.distance(driverPos, [rideData.origin_lat, rideData.origin_lng]) / 1000;
        distanceToPickup = `<br>Distance to pickup: ${dist.toFixed(2)} km`;
    }
    pickupMarker.bindPopup(`
    <b>Ride Request #${rideData.id}</b><br>
    Passenger: ${rideData.passenger?.name || 'Unknown'}${distanceToPickup}<br>
    <button onclick="window.acceptRide(${rideData.id})" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
        Accept Ride
    </button>
    `).openPopup();

    // Draw route between pickup and destination
    let routeLine = L.polyline([
        [rideData.origin_lat, rideData.origin_lng],
        [rideData.destination_lat, rideData.destination_lng]
    ], {
        color: '#10b981',
        weight: 3,
        opacity: 0.6,
        dashArray: '10, 10'
    }).addTo(map);
    activeRideMarkers[rideKey] = {
        pickup: pickupMarker,
        destination: destMarker,
        route: routeLine
    };
    // Fit bounds to show the entire ride
    const bounds = L.latLngBounds([
        [rideData.origin_lat, rideData.origin_lng],
        [rideData.destination_lat, rideData.destination_lng]
    ]);
    if (driverPos) bounds.extend(driverPos);
    map.fitBounds(bounds, { padding: [50, 50] });
}


function createPassengerTracker(rideData) {
    if (passengerTrackingMarker) {
        map.removeLayer(passengerTrackingMarker);
    }

    if (rideData.origin_lat && rideData.origin_lng) {
        passengerTrackingMarker = L.marker([rideData.origin_lat, rideData.origin_lng], {
            icon: icons.passenger
        })
        .bindPopup('<b>Passenger Location</b>')
        .addTo(map);
    }
}

function updateLocationStatus(status, text) {
    const indicator = document.getElementById('status-indicator');
    const statusText = document.getElementById('status-text');

    if (indicator && statusText) {
        if (status === 'active') {
            indicator.classList.add('active');
            statusText.textContent = text || 'Location Active';
            statusText.classList.remove('text-gray-600');
            statusText.classList.add('text-green-600');
        } else if (status === 'error') {
            indicator.classList.remove('active');
            indicator.style.backgroundColor = '#ef4444';
            statusText.textContent = text || 'Location Error';
            statusText.classList.remove('text-gray-600', 'text-green-600');
            statusText.classList.add('text-red-600');
        } else {
            indicator.classList.remove('active');
            statusText.textContent = text || 'Connecting...';
        }
    }
}

function updateUserLocation(latitude, longitude, accuracy) {
    const newLatLng = [latitude, longitude];
    if (selfMarker) {
        selfMarker.setLatLng(newLatLng);
        if (accuracyCircle) {
            accuracyCircle.setLatLng(newLatLng);
            accuracyCircle.setRadius(accuracy || 50);
        }
    } else {
        selfMarker = L.marker(newLatLng, { icon: userIcon })
            .bindPopup(`<b>You (${userRole}):</b> ${window.userLocation?.name || 'Current Location'}`)
            .addTo(map);
        allMarkers['self'] = selfMarker;
        clusterGroup.addLayer(selfMarker);
        selfMarker.setZIndexOffset(1000);
        accuracyCircle = L.circle(newLatLng, {
            radius: accuracy || 50,
            color: '#4285F4',
            fillColor: '#4285F4',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(map);
    }
    let endpoint, payload;
    if (userRole === 'driver') {
        endpoint = `/api/drivers/${driverId}/location`;
        payload = {
            current_driver_lat: latitude,
            current_driver_lng: longitude
        };
    } else if (userRole === 'passenger') {
        endpoint = '/api/passenger/location';
        payload = {
            lat: latitude,
            lng: longitude
        };
    } else {
        return;
    }
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                console.error('Location update error:', err);
                throw new Error(err.message || 'Failed to update location');
            });
        }
        return response.json();
    })
    .then(() => updateLocationStatus('active', 'Location Active'))
    .catch(err => {
        console.error('Error updating location:', err);
        updateLocationStatus('error', 'Update Failed');
    });
}

// Global window functions
window.requestRide = function() {
    if (currentRideId) {
        showNotification('You already have an active ride. Please complete or cancel it first.', 'error');
        return;
    }
    if (!selectedDestination || !selfMarker) {
        showNotification('Please select a destination first', 'error');
        return;
    }
    const pickup = selfMarker.getLatLng();

    const rideData = {
        origin_lat: pickup.lat,
        origin_lng: pickup.lng,
        destination_lat: selectedDestination.lat,
        destination_lng: selectedDestination.lng
    };
    showNotification('Creating ride request...', 'info');
    fetch('/api/rides', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(rideData)
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => {
                throw new Error(err.message || 'Failed to create ride request');
            });
        }
        return res.json();
    })
    .then(data => {
        currentRideId = data.id;
        showNotification('Ride request created! Waiting for driver...', 'success');

        if (destinationMarker) {
            const distanceKm = (rideInfo.distance / 1000).toFixed(2);
            const durationMin = Math.ceil(rideInfo.duration / 60);

            destinationMarker.setPopupContent(
                `<b>Destination</b><br>
                Distance: ${distanceKm} km<br>
                Duration: ${durationMin} min<br>
                <div class="mt-2 text-blue-600 font-semibold">Waiting for driver...</div>
                <button onclick="window.cancelRide()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                    Cancel Request
                </button>`
            );
        }
    })
    .catch(err => {
        console.error('Error creating ride request:', err);
        showNotification(err.message || 'Failed to create ride request', 'error');
    });
};

window.cancelRide = function() {
    if (!currentRideId) return;
    if (!confirm('Are you sure you want to cancel this ride?')) return;
    fetch(`/api/rides/${currentRideId}/cancel`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(() => {
        showNotification('Ride cancelled successfully', 'info');
        currentRideId = null;
        if (destinationMarker) map.removeLayer(destinationMarker);
        if (routePolyline) map.removeLayer(routePolyline);
        if (driverTrackingMarker) {
            map.removeLayer(driverTrackingMarker);
            driverTrackingMarker = null;
        }
        destinationMarker = null;
        routePolyline = null;
        selectedDestination = null;
        // Re-enable Request Ride buttons
        document.querySelectorAll('button').forEach(btn => {
            if (btn.textContent.includes('Request Ride')) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    })
    .catch(err => {
        console.error('Error cancelling ride:', err);
        showNotification('Failed to cancel ride', 'error');
    });
};

window.acceptRide = function(rideId) {
    console.log('Accepting ride with ID:', rideId); // Debug log
    if (!rideId) {
        showNotification('Invalid ride ID', 'error');
        return;
    }
    showNotification('Accepting ride...', 'info');
    fetch(`/api/rides/${rideId}/accept`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => {
                throw new Error(err.message || 'Failed to accept ride');
            });
        }
        return res.json();
    })
    .then(data => {
        showNotification('Ride accepted! Navigate to passenger.', 'success');
        currentRideId = rideId;
        // Remove the accepted ride from the map
        const rideKey = `ride-${rideId}`;
        if (activeRideMarkers[rideKey]) {
            if (activeRideMarkers[rideKey].pickup) map.removeLayer(activeRideMarkers[rideKey].pickup);
            if (activeRideMarkers[rideKey].destination) map.removeLayer(activeRideMarkers[rideKey].destination);
            if (activeRideMarkers[rideKey].route) map.removeLayer(activeRideMarkers[rideKey].route);
            delete activeRideMarkers[rideKey];
        }
 // Fetch and display the route from driver to passenger pickup
        const driverPos = selfMarker.getLatLng();
        const pickupPos = [data.origin_lat, data.origin_lng];
        const destinationPos = [data.destination_lat, data.destination_lng];

        fetchRoute(driverPos.lat, driverPos.lng, pickupPos[0], pickupPos[1], '#FF5733'); // Orange route: Driver to Pickup
        fetchRoute(pickupPos[0], pickupPos[1], destinationPos[0], destinationPos[1], '#33FF57'); // Green route: Pickup to Destination


        // Fetch updated list of available rides
        fetch('/api/rides/available', {
            headers: {
                'Authorization': `Bearer ${window.apiToken}`,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(rides => {
            rides.forEach(ride => {
                displayRideRequest(ride);
            });
        })
        .catch(err => {
            console.error('Error fetching available rides:', err);
        });
    })
    .catch(err => {
        console.error('Error accepting ride:', err);
        showNotification(err.message || 'Failed to accept ride', 'error');
    });
};


window.markArrived = function(rideId) {
    fetch(`/api/rides/${rideId}/arrived`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        showNotification(`Ride completed! Fare: $${data.fare}`, 'success');
        currentRideId = null;
        const rideKey = `ride-${rideId}`;
        if (activeRideMarkers[rideKey]) {
            if (activeRideMarkers[rideKey].pickup) map.removeLayer(activeRideMarkers[rideKey].pickup);
            if (activeRideMarkers[rideKey].destination) map.removeLayer(activeRideMarkers[rideKey].destination);
            if (activeRideMarkers[rideKey].route) map.removeLayer(activeRideMarkers[rideKey].route);
            delete activeRideMarkers[rideKey];
        }
        if (passengerTrackingMarker) {
            map.removeLayer(passengerTrackingMarker);
            passengerTrackingMarker = null;
        }
        // Re-enable Request Ride buttons
        document.querySelectorAll('button').forEach(btn => {
            if (btn.textContent.includes('Request Ride')) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    })
    .catch(err => {
        console.error('Error marking arrived:', err);
        showNotification('Failed to complete ride', 'error');
    });
};

// Initialize Everything
document.addEventListener('DOMContentLoaded', () => {
    // Set user info
    userRole = window.userRole;
    userId = window.userId;
    driverId = window.driverId;
    userIcon = userRole === 'admin' ? icons.admin :
               userRole === 'driver' ? icons.driver :
               icons.passenger;
    // Initialize map
    map = L.map('map').setView([34.1657, 35.9515], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);
    clusterGroup = L.markerClusterGroup();
    map.addLayer(clusterGroup);

    // Location Tracking Control
    const LocationControl = L.Control.extend({
        options: { position: 'topleft' },
        onAdd: function(m) {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            const button = L.DomUtil.create('a', 'location-button', container);
            button.innerHTML = '📍';
            button.href = '#';
            button.title = 'Center on my location';
            button.style.fontSize = '20px';
            button.style.width = '34px';
            button.style.height = '34px';
            button.style.lineHeight = '30px';
            button.style.textAlign = 'center';
            button.style.textDecoration = 'none';
            button.style.backgroundColor = 'white';
            L.DomEvent.on(button, 'click', function(e) {
                L.DomEvent.preventDefault(e);
                if (selfMarker) {
                    map.setView(selfMarker.getLatLng(), 16);
                    selfMarker.openPopup();
                }
            });
            return container;
        }
    });
    map.addControl(new LocationControl());

    // Destination Selection Control (Passengers Only)
    if (userRole === 'passenger') {
        const DestinationControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function(m) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                const button = L.DomUtil.create('a', 'destination-button', container);
                button.innerHTML = '📍🎯';
                button.href = '#';
                button.title = 'Select destination';
                button.style.fontSize = '20px';
                button.style.width = '34px';
                button.style.height = '34px';
                button.style.lineHeight = '30px';
                button.style.textAlign = 'center';
                button.style.textDecoration = 'none';
                button.style.backgroundColor = '#3b82f6';
                button.style.color = 'white';
                button.style.fontWeight = 'bold';
                let isSelecting = false;
                L.DomEvent.on(button, 'click', function(e) {
                    L.DomEvent.preventDefault(e);

                    if (currentRideId) {
                        showNotification('You already have an active ride request', 'error');
                        return;
                    }

                    isSelecting = !isSelecting;

                    if (isSelecting) {
                        button.style.backgroundColor = '#ef4444';
                        map.getContainer().style.cursor = 'crosshair';
                        showNotification('Tap anywhere on the map to set your destination', 'info');
                    } else {
                        button.style.backgroundColor = '#3b82f6';
                        map.getContainer().style.cursor = '';
                    }
                });
                map.on('click', function(e) {
                    if (isSelecting && selfMarker) {
                        const destLat = e.latlng.lat;
                        const destLng = e.latlng.lng;

                        setDestination(destLat, destLng);

                        isSelecting = false;
                        button.style.backgroundColor = '#3b82f6';
                        map.getContainer().style.cursor = '';
                    }
                });
                return container;
            }
        });
        map.addControl(new DestinationControl());
    }

    // Initialize self marker if user location is available
    if (window.userLocation) {
        selfMarker = L.marker(
            [window.userLocation.lat, window.userLocation.lng],
            { icon: userIcon }
        )
        .bindPopup(`<b>You (${userRole}):</b> ${window.userLocation.name}`)
        .openPopup();
        allMarkers['self'] = selfMarker;
        clusterGroup.addLayer(selfMarker);
        selfMarker.setZIndexOffset(1000);
        accuracyCircle = L.circle([window.userLocation.lat, window.userLocation.lng], {
            radius: 50,
            color: '#4285F4',
            fillColor: '#4285F4',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(map);
    }

    // Real-time Location Tracking
    if (navigator.geolocation) {
        updateLocationStatus('connecting', 'Getting location...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                updateUserLocation(latitude, longitude, accuracy);
                map.setView([latitude, longitude], 15);
                updateLocationStatus('active', 'Location Active');
            },
            (error) => {
                console.error('Error getting initial location:', error);
                updateLocationStatus('error', 'Location Denied');
                alert('Please enable location access to use this feature.');
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                updateUserLocation(latitude, longitude, accuracy);
            },
            (error) => {
                console.error('Error watching location:', error);
                updateLocationStatus('error', 'Tracking Error');
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0,
                distanceFilter: 10
            }
        );
    } else {
        console.error('Geolocation is not supported by this browser.');
        updateLocationStatus('error', 'Not Supported');
        alert('Your browser does not support location tracking.');
    }

    window.addEventListener('beforeunload', () => {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
        }
    });

    // Fetch initial live locations
    fetch('/api/admin/live-locations', {
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Accept': 'application/json',
        },
    })
    .then(res => res.json())
    .then(data => {
        const locations = Array.isArray(data) ? data : Object.values(data);
        locations.forEach(loc => {
            if (!loc.lat || !loc.lng || !loc.availability_status) return;
            let icon;
            if (loc.type === 'driver') icon = icons.driver;
            else if (loc.type === 'passenger') icon = icons.passenger;
            else if (loc.type === 'admin') icon = icons.admin;
            const marker = L.marker([loc.lat, loc.lng], { icon })
                .bindPopup(`<b>${loc.type.charAt(0).toUpperCase() + loc.type.slice(1)}:</b> ${loc.name}`);
            allMarkers[`${loc.type}-${loc.id}`] = marker;
            clusterGroup.addLayer(marker);
        });
    })
    .catch(err => console.error('Error fetching live locations:', err));

    // Fetch available rides for drivers
    if (userRole === 'driver') {
        fetch('/api/rides/available', {
            headers: {
                'Authorization': `Bearer ${window.apiToken}`,
                'Accept': 'application/json',
            }
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            return res.json();
        })
        .then(rides => {
            if (Array.isArray(rides)) {
                rides.forEach((ride, index) => {
                    displayRideRequest(ride);
                });

                if (rides.length > 0) {
                    showNotification(`${rides.length} ride(s) available nearby`, 'info');
                } else {
                    showNotification('No rides available at the moment', 'info');
                }
            }
        })
        .catch(err => {
            console.error('Error fetching available rides:', err);
            showNotification('Failed to load available rides', 'error');
        });
    }

    // Real-time WebSocket updates
    window.Echo.channel('drivers-location')
        .listen('.driver-location-updated', e => {
            const dId = e.driver_id;
            const lat = e.current_driver_lat;
            const lng = e.current_driver_lng;
            const key = `driver-${dId}`;
            if (userRole === 'driver' && driverId && dId == driverId) return;
            if (allMarkers[key]) {
                allMarkers[key].setLatLng([lat, lng]);
            } else {
                const marker = L.marker([lat, lng], { icon: icons.driver })
                    .bindPopup(`Driver: ${e.driver_name || dId}`);
                allMarkers[key] = marker;
                clusterGroup.addLayer(marker);
            }
            if (userRole === 'passenger' && driverTrackingMarker && currentRideId) {
                driverTrackingMarker.setLatLng([lat, lng]);
                if (e.current_route && window.polyline) {
                    try {
                        const coords = window.polyline.decode(e.current_route);
                        if (routePolyline) {
                            routePolyline.setLatLngs(coords);
                        }
                    } catch (err) {
                        console.error('Error decoding route:', err);
                    }
                }
            }
        });

    window.Echo.channel('passengers-location')
        .listen('.passenger-location-updated', e => {
            const passengerId = e.id;
            const lat = e.current_lat;
            const lng = e.current_lng;
            const key = `passenger-${passengerId}`;
            if (userRole === 'passenger' && passengerId == userId) return;
            if (userRole === 'driver' && currentRideId && passengerTrackingMarker) {
                passengerTrackingMarker.setLatLng([lat, lng]);
            } else {
                if (allMarkers[key]) {
                    allMarkers[key].setLatLng([lat, lng]);
                } else {
                    const marker = L.marker([lat, lng], { icon: icons.passenger })
                        .bindPopup(`Passenger: ${e.name || passengerId}`);
                    allMarkers[key] = marker;
                    clusterGroup.addLayer(marker);
                }
            }
        });

    // Passenger-specific WebSocket listeners
    if (userRole === 'passenger') {
        window.Echo.channel(`passenger-${userId}`)
            .listen('RideAccepted', e => {
                const driverName = e.driver?.user?.name || 'Unknown';
                const distance = e.distance ? `${e.distance.toFixed(2)} km` : 'N/A';
                const time = e.estimated_time ? `${e.estimated_time.toFixed(1)} min` : 'N/A';
                showNotification(
                    `Driver ${driverName} accepted your ride! Distance: ${distance}, ETA: ${time}`,
                    'success'
                );

                if (destinationMarker) {
                    destinationMarker.setPopupContent(
                        `<b>Destination</b><br>
                        Driver is on the way!<br>
                        Driver: ${driverName}<br>
                        <div class="mt-2 text-green-600 font-semibold">Ride Accepted</div>
                        <button onclick="window.cancelRide()" class="mt-2 bg-red-500 text-white px-3 py-1 rounded text-sm">
                            Cancel Ride
                        </button>`
                    );
                }

                if (e.driver?.current_driver_lat && e.driver?.current_driver_lng) {
                    if (driverTrackingMarker) {
                        driverTrackingMarker.setLatLng([e.driver.current_driver_lat, e.driver.current_driver_lng]);
                    } else {
                        driverTrackingMarker = L.marker([e.driver.current_driver_lat, e.driver.current_driver_lng], {
                            icon: icons.driver
                        })
                        .bindPopup(`<b>Your Driver</b><br>${driverName}`)
                        .addTo(map);
                    }

                    const bounds = L.latLngBounds([
                        [e.driver.current_driver_lat, e.driver.current_driver_lng],
                        destinationMarker.getLatLng()
                    ]);
                    if (selfMarker) bounds.extend(selfMarker.getLatLng());
                    map.fitBounds(bounds, { padding: [50, 50] });
                }

                // Disable Request Ride buttons
                document.querySelectorAll('button').forEach(btn => {
                    if (btn.textContent.includes('Request Ride')) {
                        btn.disabled = true;
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                });
            })
            .listen('DriverLocationUpdated', e => {
                if (currentRideId && driverTrackingMarker) {
                    driverTrackingMarker.setLatLng([e.lat, e.lng]);
                    if (e.current_route && window.polyline) {
                        try {
                            const coords = window.polyline.decode(e.current_route);
                            if (routePolyline) {
                                routePolyline.setLatLngs(coords);
                            } else {
                                routePolyline = L.polyline(coords, {
                                    color: '#3b82f6',
                                    weight: 4,
                                    opacity: 0.7
                                }).addTo(map);
                            }
                        } catch (err) {
                            console.error('Error decoding route:', err);
                        }
                    }
                }
            })
            .listen('RideArrived', e => {
                showNotification(`Driver has arrived! Fare: ${e.fare || 'N/A'}`, 'success');

                if (destinationMarker) {
                    destinationMarker.setPopupContent(
                        `<b>Destination</b><br>
                        <div class="mt-2 text-green-600 font-semibold">Ride Completed!</div>
                        <div class="mt-1">Fare: ${e.fare || 'N/A'}</div>`
                    );
                }

                setTimeout(() => {
                    currentRideId = null;
                    if (destinationMarker) map.removeLayer(destinationMarker);
                    if (routePolyline) map.removeLayer(routePolyline);
                    if (driverTrackingMarker) map.removeLayer(driverTrackingMarker);
                    destinationMarker = null;
                    routePolyline = null;
                    driverTrackingMarker = null;
                    selectedDestination = null;

                    // Re-enable Request Ride buttons
                    document.querySelectorAll('button').forEach(btn => {
                        if (btn.textContent.includes('Request Ride')) {
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    });
                }, 5000);
            })
            .listen('RideCancelled', e => {
                showNotification('Ride was cancelled', 'info');

                currentRideId = null;
                if (destinationMarker) map.removeLayer(destinationMarker);
                if (routePolyline) map.removeLayer(routePolyline);
                if (driverTrackingMarker) map.removeLayer(driverTrackingMarker);
                destinationMarker = null;
                routePolyline = null;
                driverTrackingMarker = null;
                selectedDestination = null;

                // Re-enable Request Ride buttons
                document.querySelectorAll('button').forEach(btn => {
                    if (btn.textContent.includes('Request Ride')) {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                });
            });
    }

    // Driver-specific WebSocket listeners
    if (userRole === 'driver') {
        window.Echo.channel('drivers')
            .listen('RideRequested', e => {
                showNotification('New ride request available!', 'info');
                displayRideRequest({
                    id: e.id,
                    passenger_id: e.passenger_id,
                    passenger: { name: e.passenger?.name || 'Unknown' },
                    origin_lat: e.origin_lat,
                    origin_lng: e.origin_lng,
                    destination_lat: e.destination_lat,
                    destination_lng: e.destination_lng
                });
            })
            .listen('RideRemoved', e => {
                const rideKey = `ride-${e.rideId}`;
                if (activeRideMarkers[rideKey]) {
                    if (activeRideMarkers[rideKey].pickup) map.removeLayer(activeRideMarkers[rideKey].pickup);
                    if (activeRideMarkers[rideKey].destination) map.removeLayer(activeRideMarkers[rideKey].destination);
                    if (activeRideMarkers[rideKey].route) map.removeLayer(activeRideMarkers[rideKey].route);
                    delete activeRideMarkers[rideKey];
                }
                showNotification(`Ride #${e.rideId} was accepted by another driver.`, 'info');
            })
            .listen('RideCancelled', e => {
                const rideKey = `ride-${e.id}`;
                if (activeRideMarkers[rideKey]) {
                    if (activeRideMarkers[rideKey].pickup) map.removeLayer(activeRideMarkers[rideKey].pickup);
                    if (activeRideMarkers[rideKey].destination) map.removeLayer(activeRideMarkers[rideKey].destination);
                    if (activeRideMarkers[rideKey].route) map.removeLayer(activeRideMarkers[rideKey].route);
                    delete activeRideMarkers[rideKey];
                }

                if (currentRideId === e.id) {
                    showNotification('Passenger cancelled the ride', 'info');
                    currentRideId = null;
                    if (passengerTrackingMarker) {
                        map.removeLayer(passengerTrackingMarker);
                        passengerTrackingMarker = null;
                    }
                }
            });

        window.Echo.channel(`driver-${driverId}`)
            .listen('PassengerLocationUpdated', e => {
                if (currentRideId && passengerTrackingMarker) {
                    passengerTrackingMarker.setLatLng([e.lat, e.lng]);
                }
            });
    }

    // Admin-specific listeners
    if (userRole === 'admin') {
        window.Echo.channel('drivers')
            .listen('RideRequested', e => {
                showNotification(`New ride: Passenger ${e.passenger?.name || e.passenger_id}`, 'info');
            })
            .listen('RideAccepted', e => {
                console.log('Admin: Ride accepted', e);
            })
            .listen('RideArrived', e => {
                console.log('Admin: Ride completed', e);
            });
    }
});
