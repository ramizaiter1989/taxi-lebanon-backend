import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import polyline from "@mapbox/polyline";

window.polyline = polyline;

// Axios setup
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Assign Pusher to window



window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});
window.Pusher = Pusher;