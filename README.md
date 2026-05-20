# EMD Tracker

A web application for event planning and task management with rule-based event readiness classification, built for the Events Management Department of New Era University.

**Capstone 1 · BSIT · NEU**

---

## Tech Stack

- **Frontend:** React + Vite + Tailwind CSS v4
- **Backend:** Laravel 12 + Sanctum (PHP 8.3)
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
- **Staff** — Views assigned events and updates own task status

---

## First-time Setup

### Prerequisites
- [Laragon](https://laragon.org) (includes PHP 8.3, MySQL 8.4, Apache)
- [Node.js 20+](https://nodejs.org) and npm
- Composer (bundled with Laragon)

### 1. Clone or copy the project
Place the project folder in `C:\laragon\www\EMDTracker\`.

### 2. Start Laragon
Open Laragon → click **"Start All"**. This launches Apache and MySQL.

### 3. Backend setup
Open **Terminal → Laragon Terminal** (this gives you the correct PHP path automatically).

```bash
cd C:\laragon\www\EMDTracker\backend

composer install
cp .env.example .env  # if .env doesn't exist
php artisan key:generate
```

### 4. Create the database
In Laragon, click **Database** → create database `emd_tracker`.

Or via terminal:
```bash
mysql -u root -e "CREATE DATABASE emd_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run migrations + seed demo data
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 6. Frontend setup
Open a second terminal:

```bash
cd C:\laragon\www\EMDTracker\frontend
npm install
```

---

## Running the App (Development)

You need **two terminals running simultaneously**:

### Terminal 1 — Backend
```bash
cd C:\laragon\www\EMDTracker\backend
php artisan serve
```
This runs Laravel on `http://127.0.0.1:8000`.

### Terminal 2 — Frontend
```bash
cd C:\laragon\www\EMDTracker\frontend
npm run dev
```
This runs Vite on `http://localhost:5173`.

### Open the app
- Local: **http://localhost:5173**
- LAN (from any device on the same WiFi): **http://YOUR-LAPTOP-IP:5173** (e.g., `http://192.168.1.5:5173`)

Find your laptop's IP with `ipconfig` (look for IPv4 Address under your WiFi adapter).

---

## Demo Login Credentials

All demo accounts use password: **`password123`**

| Email | Role |
|---|---|
| `admin@emd.test` | Administrator |
| `maria.officer@emd.test` | Officer |
| `juan.officer@emd.test` | Officer |
| `anna.staff@emd.test` | Staff |
| `mark.staff@emd.test` | Staff |
| `joy.staff@emd.test` | Staff |
| `paolo.staff@emd.test` | Staff |
| `liza.staff@emd.test` | Staff |
| `ben.staff@emd.test` | Staff |
| `carla.staff@emd.test` | Staff |

---

## Resetting Demo Data (before defense)

To wipe everything and reload fresh demo data:

```bash
cd C:\laragon\www\EMDTracker\backend
php artisan migrate:fresh --seed
```

This recreates all tables and reseeds 5 events, 18 tasks, 10 users covering every readiness category (Completed, On Track, At Risk, Critical).

---

## Project Structure

```
C:\laragon\www\EMDTracker\
├── backend/                  Laravel app
│   ├── app/
│   │   ├── Http/Controllers/Api/   API endpoints
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

*For internal use by the Events Management Department, New Era University.*
