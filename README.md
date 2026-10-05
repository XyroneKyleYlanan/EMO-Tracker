# EMO Tracker

A web application for event planning and task management with rule-based event readiness classification, built for the Events Management Office of New Era University.

**Capstone · BSIT · NEU**

---

## Tech Stack

- **Frontend:** React + Vite + Tailwind CSS v4
- **Backend:** Laravel 13 + Sanctum (PHP 8.3)
- **Database:** MySQL 8.4
- **Local server:** Laragon (LAN-only hosting)
- **Charts:** Recharts
- **Calendar:** FullCalendar
- **PDF generation:** barryvdh/laravel-dompdf

---

## 4 Core Features

1. **Event Planning & Scheduling** — Create, edit, and view events in calendar or list view (Calendly-style)
2. **Task & Staff Assignment Tracking** — Manage tasks per event, assign staff, track status
3. **Rule-Based Event Readiness Classification** — Hardcoded RED/YELLOW/GREEN logic + analytics dashboard with donut chart
4. **Reports & Document Management** — PDF event reports + file upload/download

## User Roles

- **Administrator** — Full access, manages staff accounts, deletes events
- **Officer** — Creates/manages events, tasks, and documents
- **Staff** — Views only the events they're assigned to and updates own task status

---

## First-time Setup

### Prerequisites
- [Laragon](https://laragon.org) (includes PHP 8.3, MySQL 8.4, Apache)
- [Node.js 20+](https://nodejs.org) and npm
- Composer (bundled with Laragon)

### 1. Clone the project

```bash
cd C:\laragon\www
git clone https://github.com/XyroneKyleYlanan/EMO-Tracker.git EMOTracker
```

The project must live inside Laragon's `www` folder — i.e., `C:\laragon\www\EMOTracker\`.

### 2. Start Laragon
Open Laragon → click **"Start All"**. This launches Apache and MySQL.

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
1. Starts the Laravel backend in its own window
2. Starts the React frontend in its own window
3. Opens `http://localhost:5173` in your browser after 5 seconds

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

````markdown
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

**4. Frontend setup:**

```bash
cd ~/Projects/EMOTracker/frontend
npm install
```

### Running the app (Mac)

From the project folder, run:

```bash
cd ~/Projects/EMOTracker
bash start.sh
```

It checks that MySQL is running, starts both servers, opens `http://localhost:5173`, and prints the address other devices on the same WiFi can use. Press **Control + C** to stop both servers.

The first time, macOS may ask whether to allow incoming connections for `node`. Click **Allow**, or phones on the WiFi won't be able to connect.

If MySQL isn't running (for example after a restart), start it with `brew services start mysql@8.4`.

The other commands in this README (resetting demo data, running tests) are the same on Mac. Just use your Mac path, e.g. `cd ~/Projects/EMOTracker/backend`.
````

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

## Resetting Demo Data (before defense)

To wipe everything and reload fresh demo data:

```bash
cd C:\laragon\www\EMOTracker\backend
php artisan migrate:fresh --seed
```

This recreates all tables and reseeds 5 events, 18 tasks, 10 users, and the sample Foundation Day document.

---

## Running the Tests

```bash
cd C:\laragon\www\EMOTracker\backend
php artisan test
```

The tests use a temporary in-memory database, so they never touch your demo data. They cover the readiness rules, role and record access, the completed-event lock, document storage, and PDF reports.

---

## Project Structure

```
C:\laragon\www\EMOTracker\
├── README.md                 This file
├── ROADMAP.md                Phased build log
├── OVERVIEW.md               Architectural reference
├── CAPSTONE_PAPER_REFERENCE.md   Reference doc for paper writing
├── start.bat                 One-click launcher (Windows)
├── start.sh                  One-command launcher (Mac)
├── backend/                  Laravel app
│   ├── app/
│   │   ├── Http/Controllers/Api/   API endpoints
│   │   ├── Http/Middleware/         Role + JSON middleware
│   │   ├── Models/                  Eloquent models
│   │   └── Services/                EventClassifier service
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

See `ROADMAP.md` for the phased build log. All 4 features and supporting infrastructure are complete.

---

## Important Notes (Per Professor's Rules)

- **No external service APIs.** All data and logic come from the self-built Laravel backend.
- **No machine learning or AI APIs.** The readiness classification is hardcoded rule-based logic in `App\Services\EventClassifier`.
- **LAN-only hosting.** App runs on a laptop via Laragon, accessible to other devices on the same WiFi network.
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
