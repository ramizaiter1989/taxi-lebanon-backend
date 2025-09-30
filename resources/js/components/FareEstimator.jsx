import React, { useState } from "react";
import axios from "axios";

export default function FareEstimator({ origin, destination, token }) {
  const [distance, setDistance] = useState(0); // in km
  const [duration, setDuration] = useState(0); // in minutes
  const [estimatedFare, setEstimatedFare] = useState(null);
  const [loading, setLoading] = useState(false);

  const getDirections = async () => {
    try {
      const response = await axios.get("/api/directions", {
        params: {
          start: `${origin.lat},${origin.lng}`,
          end: `${destination.lat},${destination.lng}`,
        },
      });

      const route = response.data.features[0].properties.segments[0];
      setDistance(route.distance / 1000); // meters → km
      setDuration(route.duration / 60);   // seconds → minutes
    } catch (error) {
      console.error("Directions API error:", error);
    }
  };

  const estimateFare = async () => {
    setLoading(true);
    try {
      const response = await axios.get("/api/fare-estimate", {
        params: {
          distance,
          duration,
        },
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      setEstimatedFare(response.data.estimated_fare);
    } catch (error) {
      console.error("Fare estimate error:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleEstimate = async () => {
    await getDirections();
    await estimateFare();
  };

  return (
    <div className="fare-estimator">
      <button onClick={handleEstimate} disabled={loading}>
        {loading ? "Calculating..." : "Estimate Fare"}
      </button>

      {estimatedFare !== null && (
        <p>
          Estimated Fare: <strong>${estimatedFare}</strong>
        </p>
      )}

      <p>
        Distance: {distance.toFixed(2)} km | Duration: {duration.toFixed(1)} min
      </p>
    </div>
  );
}
