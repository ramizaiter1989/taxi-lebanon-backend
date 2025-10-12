import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import polyline from '@mapbox/polyline';

// Make polyline globally available
window.polyline = polyline;

// Fix default Leaflet marker icons for production
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: '/images/marker-icon-2x.png',
    iconUrl: '/images/marker-icon.png',
    shadowUrl: '/images/marker-shadow.png',
});

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

// ======================== ICONS ========================
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

// ======================== GLOBAL VARIABLES ========================
let map, clusterGroup;
let selfMarker = null;
let accuracyCircle = null;
let destinationMarker = null;
let routePolyline = null;
let selectedDestination = null;
let rideInfo = { distance: null, duration: null, destination_lat: null, destination_lng: null };
let currentRideId = null;
let activeRideMarkers = {};
let passengerTrackingMarker = null;
let driverTrackingMarker = null;
let allMarkers = {};
let watchId = null;
let userRole = window.userRole;
let userId = window.userId;
let driverId = window.driverId;
let userIcon = userRole === 'admin' ? icons.admin : userRole === 'driver' ? icons.driver : icons.passenger;

// ======================== HELPER FUNCTIONS ========================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
    if (type === 'success') notification.className += ' bg-green-500 text-white';
    else if (type === 'error') notification.className += ' bg-red-500 text-white';
    else notification.className += ' bg-blue-500 text-white';
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function updateLocationStatus(status, text) {
    const indicator = document.getElementById('status-indicator');
    const statusText = document.getElementById('status-text');
    if (!indicator || !statusText) return;

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

function updateUserLocation(lat, lng, accuracy = 50) {
    const newLatLng = [lat, lng];
    if (selfMarker) {
        selfMarker.setLatLng(newLatLng);
        if (accuracyCircle) accuracyCircle.setLatLng(newLatLng).setRadius(accuracy);
    } else {
        selfMarker = L.marker(newLatLng, { icon: userIcon })
            .bindPopup(`<b>You (${userRole}):</b> ${window.userLocation?.name || 'Current Location'}`);
        allMarkers['self'] = selfMarker;
        clusterGroup.addLayer(selfMarker);
        selfMarker.setZIndexOffset(1000);
        accuracyCircle = L.circle(newLatLng, {
            radius: accuracy,
            color: '#4285F4',
            fillColor: '#4285F4',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(map);
    }

    let endpoint, payload;
    if (userRole === 'driver') {
        endpoint = `/api/drivers/${driverId}/location`;
        payload = { current_driver_lat: lat, current_driver_lng: lng };
    } else if (userRole === 'passenger') {
        endpoint = '/api/passenger/location';
        payload = { lat, lng };
    } else return;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${window.apiToken}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(res => {
        if (!res.ok) throw new Error('Failed to update location');
        updateLocationStatus('active', 'Location Active');
    })
    .catch(err => {
        console.error('Error updating location:', err);
        updateLocationStatus('error', 'Update Failed');
    });
}

// ======================== MAP INITIALIZATION ========================
document.addEventListener('DOMContentLoaded', () => {
    const defaultLat = 34.1657, defaultLng = 35.9515;
    const initLat = window.userLocation?.lat || defaultLat;
    const initLng = window.userLocation?.lng || defaultLng;

    map = L.map('map').setView([initLat, initLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    clusterGroup = L.markerClusterGroup();
    map.addLayer(clusterGroup);

    // Self Marker
    if (window.userLocation) {
        selfMarker = L.marker([window.userLocation.lat, window.userLocation.lng], { icon: userIcon })
            .bindPopup(`<b>You (${userRole}):</b> ${window.userLocation.name}`);
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

    // Geolocation tracking
    if (navigator.geolocation) {
        updateLocationStatus('connecting', 'Getting location...');
        navigator.geolocation.getCurrentPosition(
            pos => {
                updateUserLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                map.setView([pos.coords.latitude, pos.coords.longitude], 15);
            },
            err => {
                console.error('Error getting location:', err);
                updateLocationStatus('error', 'Location Denied');
                alert('Enable location access to use the map.');
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
        watchId = navigator.geolocation.watchPosition(
            pos => updateUserLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy),
            err => console.error('Error watching location:', err),
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    }

    window.addEventListener('beforeunload', () => {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    });

    // Fetch initial live locations
    fetch('/api/admin/live-locations', {
        headers: { 'Authorization': `Bearer ${window.apiToken}`, 'Accept': 'application/json' },
    })
    .then(res => res.json())
    .then(data => {
        const locations = Array.isArray(data) ? data : Object.values(data);
        locations.forEach(loc => {
            if (!loc.lat || !loc.lng || !loc.availability_status) return;
            let icon = icons[loc.type] || icons.passenger;
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
