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

# A new version of the code may need database changes; they're applied here,
# after a backup. Usually this just says the database is up to date.
if ! (cd backend && php artisan app:update-database); then
  echo ""
  echo " The database couldn't be updated, so EMO Tracker wasn't started."
  echo " The message above says why."
  exit 1
fi

# Control + C or closing the window stops both servers together.
trap 'kill 0' INT TERM HUP EXIT

(cd backend && php artisan serve) &
(cd frontend && npm run dev) &

sleep 5
open http://localhost:5173

# The address other devices on the same network use (WiFi is usually en0).
LAN_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null)

echo ""
echo "  Frontend: http://localhost:5173"
echo "  Backend:  http://127.0.0.1:8000"
if [ -n "$LAN_IP" ]; then
  echo "  On the same WiFi: http://$LAN_IP:5173"
fi
echo ""
echo "  Press Control + C (or close this window) to stop the app."
echo ""

wait
