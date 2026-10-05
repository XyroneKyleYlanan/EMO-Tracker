#!/usr/bin/env bash
# EMO Tracker - Mac launcher
# Starts the Laravel backend and the React frontend, then opens the browser.
# Double-click this file in Finder (or run:  bash start.command).
# Stop it with Control + C, or by closing the Terminal window.

cd "$(dirname "$0")"

echo ""
echo " ============================================================"
echo "  EMO Tracker - Starting up..."
echo " ============================================================"
echo ""

# MySQL must be running before Laravel can reach the database.
if ! mysqladmin ping -u root >/dev/null 2>&1; then
  echo " MySQL is not running. Start it with:  brew services start mysql@8.4"
  exit 1
fi

# Control + C or closing the window stops both servers together.
trap 'kill 0' INT TERM HUP EXIT

(cd backend && php artisan serve) &
(cd frontend && npm run dev) &

sleep 5
open http://localhost:5173

echo ""
echo "  Frontend: http://localhost:5173"
echo "  Backend:  http://127.0.0.1:8000"
echo "  On the same WiFi: http://$(ipconfig getifaddr en0):5173"
echo ""
echo "  Press Control + C (or close this window) to stop the app."
echo ""

wait
