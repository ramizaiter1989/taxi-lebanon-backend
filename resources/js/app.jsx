import "./bootstrap";
import "../css/app.css";

import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

// ====== React setup ======
import React from "react";
import { createRoot } from "react-dom/client";
import FareEstimator from "./components/FareEstimator";
import AdminLiveMap from "./components/AdminLiveMap";

// Mount FareEstimator if #app exists
const fareContainer = document.getElementById("app");
if (fareContainer) {
    const root = createRoot(fareContainer);

    const origin = { lat: 40.7128, lng: -74.006 };
    const destination = { lat: 40.73061, lng: -73.935242 };
    const token = document.head.querySelector('meta[name="csrf-token"]')?.content;

    root.render(
        <React.StrictMode>
            <FareEstimator origin={origin} destination={destination} token={token} />
        </React.StrictMode>
    );
}

// Mount AdminLiveMap if #admin-map exists
const adminContainer = document.getElementById("admin-live-map");
if (adminContainer) {
    const root = createRoot(adminContainer);
    root.render(
        <React.StrictMode>
            <AdminLiveMap />
        </React.StrictMode>
    );
}
