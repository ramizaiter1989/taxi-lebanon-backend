import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  server: {
    host: '0.0.0.0',
    port: 5173,
    https: false, // 🚫 disable https for local dev
    hmr: {
      host: 'localhost',
      protocol: 'ws', // use ws instead of wss
    },
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.jsx'],
      refresh: true,
    }),
  ],
});
