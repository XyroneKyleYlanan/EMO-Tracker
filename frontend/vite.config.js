import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  plugins: [react(), tailwindcss()],
  // Office mode: `npm run build` puts the finished app in backend/public/app,
  // where Laravel serves it, so the office computer needs no Node.js and runs
  // one server instead of two. `npm run dev` is unchanged.
  base: command === 'build' ? '/app/' : '/',
  build: {
    outDir: '../backend/public/app',
    emptyOutDir: true,
  },
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        // Tells Laravel which device each request came from (the login limit
        // counts per device), instead of every device looking like this computer.
        xfwd: true,
      },
      '/storage': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
}))
