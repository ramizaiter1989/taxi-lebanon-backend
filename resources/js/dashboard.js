import L from "leaflet";
import Echo from "laravel-echo";
import Pusher from "pusher-js"; // ✅ important

// Make Pusher globally available
window.Pusher = Pusher;

// Initialize Laravel Echo
window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// Map logic
document.addEventListener("DOMContentLoaded", () => {
    const map = L.map("map").setView([51.1657, 10.4515], 6);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    let driverMarkers = {};

    fetch("/api/drivers")
        .then((res) => res.json())
        .then((drivers) => {
            drivers.forEach((driver) => {
                if (driver.current_driver_lat && driver.current_driver_lng) {
                    driverMarkers[driver.id] = L.marker([
                        driver.current_driver_lat,
                        driver.current_driver_lng,
                    ])
                        .addTo(map)
                        .bindPopup(`Driver #${driver.id}`);
                }
            });
        });

    // Live updates
    window.Echo.channel("drivers-location").listen(
        ".driver-location-updated",
        (e) => {
            const driverId = e.driver_id;
            const lat = e.current_driver_lat;
            const lng = e.current_driver_lng;

            if (driverMarkers[driverId]) {
                driverMarkers[driverId].setLatLng([lat, lng]);
            } else {
                driverMarkers[driverId] = L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup(`Driver #${driverId}`);
            }
        }
    );
});
console.log(import.meta.env.VITE_PUSHER_APP_KEY);
drivers.forEach((driver) => {
    let iconUrl = "/images/car-icon.png";

    new google.maps.Marker({
        position: {
            lat: driver.current_driver_lat,
            lng: driver.current_driver_lng,
        },
        map: map,
        title: driver.name,
        icon: {
            url: iconUrl,
            scaledSize: new google.maps.Size(40, 40),
        },
    });
});

