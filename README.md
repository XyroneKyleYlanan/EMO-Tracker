# EMO Tracker

A web application for event planning and task management with rule-based event readiness classification, built for the Events Management Office of New Era University.

**Capstone · BSIT · NEU**

---

## Tech Stack

- **Frontend:** React + Vite + Tailwind CSS v4
- **Backend:** Laravel 13 + Sanctum (PHP 8.3)
- **Database:** MySQL 8.4
- **Local server:** Laragon on Windows, Homebrew on Mac (LAN-only hosting)
- **Charts:** Recharts
- **Calendar:** FullCalendar
- **PDF generation:** barryvdh/laravel-dompdf

---

## Features

1. **Event Planning & Scheduling** — Calendar and list views, plus a Schedule page laid out like the EMO's sheet (one tab per year, colored by building). Every event is Internal or External, shows its status (Upcoming, Ongoing, Completed, Cancelled), and notes when it was rescheduled
2. **Task Assignment & Tracking** — Tasks per event, each with an owner, due date, priority and status; everyone has a My tasks page
3. **Rule-Based Event Readiness Classification** — Hardcoded On Track / At Risk / Critical rules that also say why, plus an Analytics page
4. **Reports & Document Management** — PDF event reports, Excel export of the Schedule, and file upload and download (PDF, Word, Excel, JPG, PNG, up to 10 MB)
5. **Venue Double-Booking Warning** — Warns when an event overlaps another booking at the same venue and time

Also included: automatic daily backups (database and documents), and a one-time import of the EMO's schedule spreadsheet.

## User Roles

- **Administrator** — Full access: manages the schedule (adds, edits, cancels and deletes events), venues and user accounts; the only role that can delete documents
- **Officer** — Prepares events: adds and edits tasks, uploads documents, makes reports, views Analytics, exports the Schedule
- **Staff** — Sees every event and the Schedule, and updates the status of their own tasks

---

## First-time Setup

### Prerequisites
- [Laragon](https://laragon.org) (includes PHP 8.3 and MySQL 8.4)
- [Node.js 20+](https://nodejs.org) and npm
- Composer (bundled with Laragon)

### 1. Clone the project

```bash
cd C:\laragon\www
git clone https://github.com/XyroneKyleYlanan/EMO-Tracker.git EMOTracker
```

The project must live inside Laragon's `www` folder — i.e., `C:\laragon\www\EMOTracker\`.

### 2. Start Laragon
Open Laragon → click **"Start All"**. This starts MySQL (the app doesn't use Laragon's Apache).

### 3. Backend setup
Open **Terminal → Laragon Terminal** (this gives you the correct PHP path automatically).

```bash
cd C:\laragon\www\EMOTracker\backend

composer install
cp .env.example .env  # if .env doesn't exist
php artisan key:generate
```

### 4. Create the database
In Laragon, click **Database** → create database `emo_tracker`.

Or via terminal:
```bash
mysql -u root -e "CREATE DATABASE emo_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run migrations + seed demo data
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

Only run `migrate:fresh` on a new, empty database: it deletes everything first.

### 6. Frontend setup
Open a second terminal:

```bash
cd C:\laragon\www\EMOTracker\frontend
npm install
```

---

## Running the App

First make sure **Laragon is running** (open Laragon → "Start All").

### Easy way — one-click launcher (recommended)

Double-click **`start.bat`** in the project root (`C:\laragon\www\EMOTracker\start.bat`).

It automatically:
1. Finds Laragon's PHP and applies any database changes that came with a new version (saving a backup first)
2. Starts the Laravel backend in its own window
3. Starts the React frontend in its own window
4. Opens `http://localhost:5173` in your browser after 5 seconds

To stop the app, close the two CMD windows that opened.

### Manual way — two terminals

If you prefer to run the servers yourself:

**Terminal 1 — Backend**
```bash
cd C:\laragon\www\EMOTracker\backend
php artisan serve
```
Runs Laravel on `http://127.0.0.1:8000`.

**Terminal 2 — Frontend**
```bash
cd C:\laragon\www\EMOTracker\frontend
npm run dev
```
Runs Vite on `http://localhost:5173`.

### Open the app
- Local: **http://localhost:5173**
- LAN (from any device on the same WiFi): **http://YOUR-LAPTOP-IP:5173** (e.g., `http://192.168.1.5:5173`)

Find your laptop's IP with `ipconfig` (look for IPv4 Address under your WiFi adapter).

---

## Running on Mac

The steps above are for Windows with Laragon. On a Mac, use [Homebrew](https://brew.sh) instead.

### First-time setup (Mac)

**1. Install the tools** (one time):

```bash
brew install php@8.3 composer mysql@8.4 node
brew services start mysql@8.4
echo 'export PATH="/opt/homebrew/opt/php@8.3/bin:/opt/homebrew/opt/mysql@8.4/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

The `export PATH` line is needed because Homebrew doesn't link versioned PHP and MySQL automatically.

**2. Clone the project** (any folder works on Mac, e.g. `~/Projects`):

```bash
cd ~/Projects
git clone https://github.com/XyroneKyleYlanan/EMO-Tracker.git EMOTracker
```

**3. Backend setup:**

```bash
cd ~/Projects/EMOTracker/backend
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE emo_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
```

Only run `migrate:fresh` on a new, empty database: it deletes everything first.

**4. Frontend setup:**

```bash
cd ~/Projects/EMOTracker/frontend
npm install
```

### Running the app (Mac)

In Finder, open the `EMOTracker` folder and double-click **`start.command`**. A Terminal window opens, checks that MySQL is running, applies any database changes (saving a backup first), starts both servers, opens `http://localhost:5173`, and prints the address other devices on the same WiFi can use. To stop the app, press **Control + C** in that window, or just close it.

If macOS says the file can't be opened, right-click it, choose **Open**, then click **Open** again. You only need to do this once.

You can also start it from Terminal:

```bash
cd ~/Projects/EMOTracker
bash start.command
```

The first time, macOS may ask whether to allow incoming connections for `node`. Click **Allow**, or phones on the WiFi won't be able to connect.

If MySQL isn't running (for example after a restart), start it with `brew services start mysql@8.4`.

The other commands in this README (resetting demo data, running tests) are the same on Mac. Just use your Mac path, e.g. `cd ~/Projects/EMOTracker/backend`.

---

## Updating to a New Version

1. In the project folder, run `git pull`.
2. In `backend`, run `composer install`. In `frontend`, run `npm install`.
3. Start the app with `start.bat` (or `start.command` on Mac). It applies any database changes itself, after saving a backup. If you start the servers by hand instead, first run `php artisan app:update-database` in `backend`.

Never use `php artisan migrate:fresh` to update: it deletes all the data.

---

## Demo Login Credentials

All demo accounts use password: **`password123`**

| Email | Role |
|---|---|
| `admin@emo.test` | Administrator |
| `maria.officer@emo.test` | Officer |
| `juan.officer@emo.test` | Officer |
| `anna.staff@emo.test` | Staff |
| `mark.staff@emo.test` | Staff |
| `joy.staff@emo.test` | Staff |
| `paolo.staff@emo.test` | Staff |
| `liza.staff@emo.test` | Staff |
| `ben.staff@emo.test` | Staff |
| `carla.staff@emo.test` | Staff |

---

## Resetting Demo Data

**Careful:** `php artisan migrate:fresh --seed` deletes everything in the database it runs on, including the EMO's real schedule. Only run it on a separate demo database:

1. Create a database named `emo_tracker_demo` (same as step 4 above, with that name).
2. In `backend/.env`, change `DB_DATABASE=emo_tracker` to `DB_DATABASE=emo_tracker_demo`.
3. In `backend`, run `php artisan migrate:fresh --seed`. This loads 38 sample events, 18 tasks, the 10 demo accounts and a sample document.
4. To switch back to the real data, change `DB_DATABASE` back to `emo_tracker` and restart the app.

Deleted something by mistake? The app backs up each database once a day. `php artisan backup:list` shows the backups, and `php artisan backup:restore` brings back the newest one.

---

## Running the Tests

```bash
cd C:\laragon\www\EMOTracker\backend
php artisan test
```

The tests use a temporary in-memory database, so they never touch your demo data. The 110 tests cover the readiness rules, event statuses, the double-booking check, roles and access, the completed-event lock, backups, the schedule import and export, documents and PDF reports.

---

## Project Structure

```
C:\laragon\www\EMOTracker\
├── README.md                 This file
├── ROADMAP.md                Phased build log
├── docs/                     Architectural overview (OVERVIEW.md), demo accounts
│   └── paper/                Capstone paper reference, copy-ready tables, screenshots, diagrams
├── start.bat                 One-click launcher (Windows)
├── start.command             Double-click launcher (Mac)
├── backend/                  Laravel app
│   ├── app/
│   │   ├── Http/Controllers/Api/   API endpoints
│   │   ├── Http/Middleware/         Roles, JSON, finished events, daily backup
│   │   ├── Models/                  Eloquent models
│   │   └── Services/                Readiness, overlaps, import/export, backups
│   ├── database/
│   │   ├── migrations/              Schema definitions
│   │   └── seeders/                 Demo data
│   ├── resources/views/pdf/         PDF Blade templates
│   └── routes/api.php               API routes
└── frontend/                 React + Vite app
    └── src/
        ├── components/              Reusable UI
        ├── contexts/                Auth + Toast providers
        ├── lib/                     api.js, download.js, format.js
        └── pages/                   Page-level components
```

---

## Roadmap & Progress

See `ROADMAP.md` for the phased build log and what's next. Materials for the Capstone 2 paper are in [`docs/paper`](docs/paper/).

---

## Important Notes (Per Professor's Rules)

- **No external service APIs.** All data and logic come from the self-built Laravel backend.
- **No machine learning or AI APIs.** The readiness classification is hardcoded rule-based logic in `App\Services\EventClassifier`.
- **LAN-only hosting.** App runs on a laptop (Laragon on Windows, Homebrew on Mac), accessible to other devices on the same WiFi network.
- **UI component libraries are allowed** (FullCalendar, Recharts, Tailwind, axios). These are visual tools, not feature APIs.

---

## Team

- Ylanan, Xyrone Kyle E.
- Bacena, Phil Jade P.
- Sy, Joana Daphne T.
- Zabala, Jean Simone L.

**Adviser:** Prof. Teresita C. Alcantara

---

*For internal use by the Events Management Office, New Era University.*
