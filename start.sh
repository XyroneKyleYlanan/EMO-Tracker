```
bash
#!/usr/bin/env bash
# EMO Tracker - Mac launcher
# Starts the Laravel backend and the React frontend, then opens the browser.
# Run it with:  bash start.sh      Stop it with:  Control + C

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

# Control + C (or closing the window) stops both servers together.
trap 'kill 0' INT TERM EXIT

(cd backend && php artisan serve) &
(cd frontend && npm run dev) &

sleep 5
open http://localhost:5173

echo ""
echo "  Frontend: http://localhost:5173"
echo "  Backend:  http://127.0.0.1:8000"
echo "  On the same WiFi: http://$(ipconfig getifaddr en0):5173"
echo ""
echo "  Press Control + C to stop the app."
echo ""

wait
```
