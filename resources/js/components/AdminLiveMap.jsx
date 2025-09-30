import React, { useEffect, useState, useRef } from "react";
import { MapContainer, TileLayer, Marker, Popup } from "react-leaflet";
import MarkerClusterGroup from "react-leaflet-markercluster";
import L from "leaflet";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: "pusher",
  key: import.meta.env.VITE_PUSHER_KEY,
  cluster: import.meta.env.VITE_PUSHER_CLUSTER,
  forceTLS: true,
});

const driverIcon = new L.Icon({
  iconUrl: "/public/images/car-icon.png",
  iconSize: [30, 30],
});
const passengerIcon = new L.Icon({
  iconUrl: "/public/images/passenger.png",
  iconSize: [30, 30],
});

// Cluster icon
const createClusterIcon = (cluster) => {
  const markers = cluster.getAllChildMarkers();
  const driverCount = markers.filter(m => m.options.icon.options.iconUrl.includes("driver")).length;
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
  const markersRef = useRef({}); // keep refs to markers
  const [filter, setFilter] = useState("all");

  // Fetch initial locations
  useEffect(() => {
    fetch("/api/admin/live-locations")
      .then(res => res.json())
      .then(data => {
        setLocations(data);
        // create initial marker refs
        const refs = {};
        data.forEach(loc => {
          refs[`${loc.type}-${loc.id}`] = { ...loc };
        });
        markersRef.current = refs;
      });
  }, []);

  // Real-time updates
  useEffect(() => {
    echo.channel("drivers")
      .listen("DriverLocationUpdated", (e) => {
        const key = `driver-${e.driver.id}`;
        markersRef.current[key] = {
          type: "driver",
          id: e.driver.id,
          name: e.driver.name,
          lat: e.lat,
          lng: e.lng,
          last_update: new Date().toISOString(),
        };
        setLocations(Object.values(markersRef.current));
      });

    echo.channel("passengers")
      .listen("PassengerLocationUpdated", (e) => {
        const key = `passenger-${e.id}`;
        markersRef.current[key] = {
          type: "passenger",
          id: e.id,
          name: e.name,
          lat: e.current_lat,
          lng: e.current_lng,
          last_update: new Date().toISOString(),
        };
        setLocations(Object.values(markersRef.current));
      });
  }, []);

  // Filtered locations
  const filteredLocations = locations.filter(loc => filter === "all" ? true : loc.type === filter);

  return (
    <div>
      <div style={{ margin: "10px" }}>
        <button onClick={() => setFilter("all")}>All</button>
        <button onClick={() => setFilter("driver")}>Drivers</button>
        <button onClick={() => setFilter("passenger")}>Passengers</button>
      </div>
      <div style={{ margin: "10px" }}>hello</div>

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
