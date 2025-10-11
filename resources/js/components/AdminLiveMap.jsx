import React, { useEffect, useState, useRef } from "react";
import { MapContainer, TileLayer, Marker, Popup } from "react-leaflet";
import MarkerClusterGroup from "react-leaflet-markercluster";
import L from "leaflet";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Initialize Echo
const echo = new Echo({
  broadcaster: "pusher",
  key: import.meta.env.VITE_PUSHER_KEY,
  cluster: import.meta.env.VITE_PUSHER_CLUSTER,
  forceTLS: true,
  // Optional: if your backend uses auth for private channels
  // authEndpoint: "https://your-api.com/broadcasting/auth",
  // auth: {
  //   headers: {
  //     Authorization: `Bearer ${localStorage.getItem("token")}`,
  //   },
  // },
});

const driverIcon = new L.Icon({
  iconUrl: "/images/car-icon.png",
  iconSize: [30, 30],
});
const passengerIcon = new L.Icon({
  iconUrl: "/images/passenger.png",
  iconSize: [30, 30],
});

// Cluster icon
const createClusterIcon = (cluster) => {
  const markers = cluster.getAllChildMarkers();
  const driverCount = markers.filter(m => m.options.icon.options.iconUrl.includes("car")).length;
  const passengerCount = markers.length - driverCount;

  let html = `<div style="background-color:gray; border-radius:50%; color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">${markers.length}</div>`;
  if (driverCount && !passengerCount) {
    html = `<div style="background-color:blue; border-radius:50%; color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">${driverCount}</div>`;
  } else if (!driverCount && passengerCount) {
    html = `<div style="background-color:green; border-radius:50%; color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">${passengerCount}</div>`;
  } else if (driverCount && passengerCount) {
    html = `<div style="background: linear-gradient(45deg, blue 50%, green 50%); border-radius:50%; color:white; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">${markers.length}</div>`;
  }

  return L.divIcon({
    html,
    className: "custom-cluster",
    iconSize: L.point(40, 40, true),
  });
};

const AdminLiveMap = () => {
  const [locations, setLocations] = useState([]);
  const markersRef = useRef({});
  const [filter, setFilter] = useState("all");

  // Fetch initial locations
  useEffect(() => {
    fetch("/api/admin/live-locations")
  .then(async (res) => {
    const data = await res.json();
    console.log("Fetched live locations:", data);

    // Ensure it's an array — Laravel should return an array but we double-check
    const list = Array.isArray(data)
      ? data
      : Object.values(data || {}); // convert object values to array if needed

    setLocations(list);

    const refs = {};
    list.forEach((loc) => {
      refs[`${loc.type}-${loc.id}`] = { ...loc };
    });
    markersRef.current = refs;
  })
  .catch((err) => console.error("Error fetching live locations:", err));

  }, []);

  // Real-time updates (NEW CHANNELS)
  useEffect(() => {
    // Listen to all drivers
    echo.channel("drivers-location")
      .listen(".driver-location-updated", (data) => {
        const key = `driver-${data.driver_id}`;
        markersRef.current[key] = {
          type: "driver",
          id: data.driver_id,
          name: data.name || `Driver ${data.driver_id}`,
          lat: data.lat,
          lng: data.lng,
          last_update: new Date().toISOString(),
        };
        setLocations(Object.values(markersRef.current));
      });

    // Listen to all passengers
    echo.channel("passengers-location")
      .listen(".passenger-location-updated", (data) => {
        const key = `passenger-${data.id}`;
        markersRef.current[key] = {
          type: "passenger",
          id: data.id,
          name: data.name || `Passenger ${data.id}`,
          lat: data.current_lat,
          lng: data.current_lng,
          last_update: new Date().toISOString(),
        };
        setLocations(Object.values(markersRef.current));
      });

    // Cleanup
    return () => {
      echo.leaveChannel("drivers-location");
      echo.leaveChannel("passengers-location");
    };
  }, []);

  // Filtered locations
  const filteredLocations = locations.filter(loc =>
    filter === "all" ? true : loc.type === filter
  );

  return (
    <div>
      <div style={{ margin: "10px" }}>
        <button onClick={() => setFilter("all")}>All</button>
        <button onClick={() => setFilter("driver")}>Drivers</button>
        <button onClick={() => setFilter("passenger")}>Passengers</button>
      </div>

      <MapContainer center={[33.8938, 35.5018]} zoom={12} style={{ height: "90vh", width: "100%" }}>
        <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

        <MarkerClusterGroup chunkedLoading iconCreateFunction={createClusterIcon}>
          {filteredLocations.map(loc => (
            <Marker
              key={`${loc.type}-${loc.id}`}
              position={[loc.lat, loc.lng]}
              icon={loc.type === "driver" ? driverIcon : passengerIcon}
            >
              <Popup>
                <strong>{loc.name}</strong> <br />
                {loc.type} <br />
                Last Update: {new Date(loc.last_update).toLocaleTimeString()}
              </Popup>
            </Marker>
          ))}
        </MarkerClusterGroup>
      </MapContainer>
    </div>
  );
};

export default AdminLiveMap;
