{{-- resources/views/ors-map.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ORS Map / Route Demo</title>



  <style>
    body, html { height: 100%; margin: 0; padding: 0; }
    #map { height: 70vh; width: 100%; }
    .controls { padding: 12px; max-width: 900px; margin: 12px auto; display: flex; gap: 8px; flex-wrap: wrap; }
    .controls input[type="text"] { padding: 8px; flex: 1 1 200px; }
    .controls button { padding: 8px 12px; }
  </style>

  <!-- Expose ORS key to JS -->
  <meta name="ors-key" content="{{ config('services.ors.key') }}">
</head>
<body>

  <div class="controls">
    <input id="from" type="text" placeholder="From: lat,lng (e.g. 52.5200,13.4050)" value="52.5200,13.4050">
    <input id="to"   type="text" placeholder="To: lat,lng (e.g. 52.5170,13.3889)" value="52.5170,13.3889">
    <select id="profile">
      <option value="driving-car">Driving</option>
      <option value="cycling-regular">Cycling</option>
      <option value="foot-walking">Walking</option>
    </select>
    <button id="routeBtn">Get Route</button>
    <button id="fitBtn">Fit to Route</button>
  </div>

  <div id="map"></div>

  <!-- Leaflet JS -->


  <script>
    // Grab ORS key
    const ORS_KEY = document.querySelector('meta[name="ors-key"]').getAttribute('content');

    // Init map
    const map = L.map('map').setView([52.52, 13.405], 13);

    // Add OSM tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Layers for route and markers
    const routeLayer = L.geoJSON(null, { style: { weight: 6, opacity: 0.8 } }).addTo(map);
    let startMarker = null;
    let endMarker = null;

    // Helpers
    function parseLatLngPair(text) {
      const parts = text.split(',').map(s => parseFloat(s.trim()));
      if (parts.length !== 2 || parts.some(isNaN)) throw new Error('Invalid coordinate pair');
      return [parts[0], parts[1]]; // lat, lng
    }

    async function getRoute(fromLatLng, toLatLng, profile = 'driving-car') {
      // ORS expects [lng, lat] pairs in coordinates array
      const url = `https://api.openrouteservice.org/v2/directions/${encodeURIComponent(profile)}/geojson`;
      const body = {
        coordinates: [
          [fromLatLng[1], fromLatLng[0]],
          [toLatLng[1], toLatLng[0]]
        ],
        // optional: instructions: false, geometry_simplify: false
      };

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': ORS_KEY
        },
        body: JSON.stringify(body)
      });

      if (!res.ok) {
        const text = await res.text();
        throw new Error('ORS request failed: ' + res.status + ' — ' + text);
      }

      const geojson = await res.json();
      return geojson;
    }

    function showRoute(geojson) {
      // Clear previous
      routeLayer.clearLayers();
      if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
      if (endMarker) { map.removeLayer(endMarker); endMarker = null; }

      // ORS GeoJSON contains FeatureCollection with geometry
      routeLayer.addData(geojson);

      // pick coordinates for start/end from geometry coordinates (they are [lng,lat])
      try {
        const coords = geojson.features[0].geometry.coordinates;
        const start = coords[0]; // [lng, lat]
        const end = coords[coords.length - 1];
        startMarker = L.marker([start[1], start[0]]).addTo(map).bindPopup('Start').openPopup();
        endMarker = L.marker([end[1], end[0]]).addTo(map).bindPopup('End');
      } catch (e) {
        console.warn('Could not get start/end from response', e);
      }
    }

    // UI bindings
    document.getElementById('routeBtn').addEventListener('click', async () => {
      try {
        const from = parseLatLngPair(document.getElementById('from').value);
        const to = parseLatLngPair(document.getElementById('to').value);
        const profile = document.getElementById('profile').value;

        // Optionally show a quick spinner / disable while fetching
        document.getElementById('routeBtn').disabled = true;
        document.getElementById('routeBtn').textContent = 'Routing...';

        const geojson = await getRoute(from, to, profile);
        showRoute(geojson);

        // Fit map to route bounds
        const bounds = routeLayer.getBounds();
        if (bounds.isValid()) map.fitBounds(bounds, { padding: [40, 40] });

      } catch (err) {
        alert('Error: ' + err.message);
        console.error(err);
      } finally {
        document.getElementById('routeBtn').disabled = false;
        document.getElementById('routeBtn').textContent = 'Get Route';
      }
    });

    document.getElementById('fitBtn').addEventListener('click', () => {
      const bounds = routeLayer.getBounds();
      if (bounds.isValid()) map.fitBounds(bounds, { padding: [40, 40] });
      else alert('No route to fit to yet.');
    });

    // Optionally, draw initial route on load
    (async () => {
      try {
        document.getElementById('routeBtn').click();
      } catch (e) { /* ignore */ }
    })();

  </script>
</body>
</html>
