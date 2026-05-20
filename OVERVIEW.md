# EMD Tracker — Project Overview

> Architectural reference document. Use this to familiarize yourself or your team with the full app, and as a quick lookup during defense.

---

## High-level

A web application that helps the Events Management Department of NEU plan and track events. Replaces their manual spreadsheet/paper workflow with a centralized system accessible from any device on the school WiFi.

**Stack:** React + Laravel + MySQL. Runs locally via Laragon on a laptop, accessible to other devices over LAN.

---

## Architecture (How the pieces talk)

```
┌─────────────┐    HTTP API (JSON)    ┌──────────────┐    SQL    ┌──────────┐
│   Browser   │ ────────────────────▶ │   Laravel    │ ────────▶ │  MySQL   │
│  (React)    │ ◀──────────────────── │   Backend    │ ◀──────── │ Database │
└─────────────┘    Bearer token auth  └──────────────┘           └──────────┘
   port 5173                              port 8000                port 3306
   (Vite dev)                          (php artisan serve)       (Laragon)
```

The frontend never talks to the database directly. All data flows through Laravel's REST API. This separation is exactly what the professor's "no third-party feature APIs" rule requires — we built our own API.

---

## Backend (Laravel 12 / PHP 8.3)

**Location:** `C:\laragon\www\EMDTracker\backend\`

### Key folders

| Folder | What's in it |
|---|---|
| `app/Models/` | Eloquent models — `User`, `Event`, `Task`, `Document`, `Setting` |
| `app/Http/Controllers/Api/` | REST API endpoints — `AuthController`, `EventController`, `TaskController`, `DashboardController`, `AnalyticsController`, `UserController`, `DocumentController`, `ReportController` |
| `app/Http/Middleware/` | `EnsureUserHasRole` (role gates), `ForceJsonResponse` (forces JSON on `/api/*`) |
| `app/Services/EventClassifier.php` | **The "AI" — rule-based readiness classifier** |
| `database/migrations/` | Database schema definitions |
| `database/seeders/DatabaseSeeder.php` | Demo data (10 users, 5 events, 18 tasks) |
| `routes/api.php` | All API routes with their role guards |
| `resources/views/pdf/event-report.blade.php` | PDF template for event reports |

### Auth flow (Laravel Sanctum)

1. User POSTs `/api/login` with email + password
2. Laravel verifies credentials → returns a bearer token + user object
3. Frontend stores token in `localStorage`
4. Every subsequent request includes `Authorization: Bearer <token>` header
5. The `auth:sanctum` middleware validates the token; the `role:admin,officer` middleware checks role-based access

---

## Frontend (React + Vite + Tailwind v4)

**Location:** `C:\laragon\www\EMDTracker\frontend\`

### Key folders

| Folder | What's in it |
|---|---|
| `src/pages/` | One file per page — `LoginPage`, `AdminDashboard`, `OfficerDashboard`, `StaffDashboard`, `EventsPage`, `StaffTasksPage`, `AnalyticsPage`, `StaffManagementPage` |
| `src/components/` | Reusable bits — `AppLayout`, `StatCard`, `ReadinessBadge`, `EventCard`, `EventCalendarView`, `EventListView`, `EventDetailDrawer`, `EventFormDialog`, `TaskRow`, `TaskFormDialog`, `UserFormDialog`, `ChangePasswordDialog`, `ReadinessDonut`, `DateTimeDisplay` |
| `src/contexts/` | Global state — `AuthContext` (current user + token), `ToastContext` (notifications) |
| `src/lib/` | Helpers — `api.js` (axios instance), `download.js` (PDF/file downloads), `format.js` (date/time formatting) |
| `public/neu-logo.png` | The official NEU seal (also used as favicon) |

### App entry → routes

`App.jsx` is the root. It wraps everything in `ToastProvider` → `AuthProvider` → `BrowserRouter`, then defines all routes with role-based protection via `ProtectedRoute`.

---

## Database (MySQL)

Database name: `emd_tracker` — 6 tables.

```
users (id, name, email, password, role, is_active, ...)
  │
  │ created_by ┐                   ┌─ assigned_to ──┐
  │            ▼                   ▼                │
  │         events (id, name, description, venue,   │
  │            ▲    event_date, event_time, budget, │
  │            │    status, created_by)             │
  │            │                                    │
  │            │ event_id                           │
  │            │                                    │
  │            ├──── tasks (id, name, description,  │
  │            │       due_date, status, priority,  │
  │            │       assigned_to) ────────────────┘
  │            │
  │            ├──── documents (id, file_name, file_path,
  │            │       file_size, mime_type, uploaded_by)
  │            │
  │            └──── event_staff (event_id, user_id) ◀── pivot for event-level staff
  │
settings (id, key, value) ◀── seeded with school year config (unused but harmless)
```

**Design rationale:**
- `users` has 3 roles (admin/officer/staff) via an enum column — simple and clear
- `event_staff` pivot table lets multiple staff be assigned to an event independently of task assignments
- `tasks` has `assigned_to` directly (single staff per task — simpler for a 10-person team)
- `documents` are linked to events with `cascadeOnDelete` so deleting an event cleans up its docs
- All foreign keys use `cascadeOnDelete` or `nullOnDelete` to maintain referential integrity

---

## The "AI" Feature (Rule-Based Classifier)

**File:** `app/Services/EventClassifier.php` (~30 lines)

This is the centerpiece of the defense. It's clean if/elif logic — no machine learning, no external APIs, no data training:

```
1. If event status = "completed" → return "completed"
2. If event has 0 tasks → return "yellow" (edge case)
3. Compute: completion %, days remaining, unassigned %
4. Check RED conditions first:
   - completion < 40%, OR
   - days remaining ≤ 2, OR
   - majority of tasks unassigned
5. Check YELLOW conditions second:
   - completion < 70%, OR
   - days remaining ≤ 6, OR
   - any task unassigned
6. Otherwise → return "green"
```

The Event model's `readiness` accessor delegates to this service. Called automatically every time an event is fetched — the readiness color updates **live** as tasks change status.

---

## User Roles & Permissions

| Role | Can do | Cannot do |
|---|---|---|
| **Administrator** | Everything: manage users, events, tasks, documents, analytics, reports | (nothing restricted) |
| **Officer** | Create/edit events, tasks, documents; view analytics; assign staff | Manage user accounts; delete events |
| **Staff** | View their assigned events/tasks; update their own task status; change own password; download reports | Create events, assign others, view analytics, manage other users |

Role enforcement happens in **two places**:
- **Backend:** middleware on routes (`role:admin,officer`)
- **Frontend:** `ProtectedRoute` component checks user role before rendering pages

This double-gating means a malicious user can't bypass the UI to hit forbidden endpoints.

### Historical record protection

Once an event auto-transitions to "Completed" status (its date has passed), its tasks are **locked**:
- Staff and Officers can no longer change status, edit, delete, or add tasks
- Only an Administrator can modify completed-event task records (a "break glass" path for genuine corrections)
- Any status change an Admin makes to a completed event requires explicit confirmation
- Enforced on the backend (HTTP 403) — cannot be bypassed via the UI

This protects the integrity of post-event reports. Key files: `TaskController.php` (backend checks), `TaskRow.jsx` (UI lock state).

---

## The 4 Features (per briefing)

| # | Feature | Key files |
|---|---|---|
| 1 | **Event Planning & Scheduling** (Calendly-style calendar + list view) | `EventController.php`, `EventsPage.jsx`, `EventCalendarView.jsx`, `EventListView.jsx` |
| 2 | **Task & Staff Assignment Tracking** | `TaskController.php`, `TaskRow.jsx`, `TaskFormDialog.jsx`, `EventDetailDrawer.jsx` |
| 3 | **Rule-Based Event Readiness Classification + Analytics** | `EventClassifier.php`, `AnalyticsController.php`, `AnalyticsPage.jsx`, `ReadinessDonut.jsx` |
| 4 | **Reports & Document Management** | `ReportController.php`, `DocumentController.php`, `event-report.blade.php`, `EventDetailDrawer.jsx` |

---

## Files Panelists Might Ask About

| Question | File to show |
|---|---|
| "How does the AI classification work?" | `backend/app/Services/EventClassifier.php` |
| "How does authentication work?" | `backend/app/Http/Controllers/Api/AuthController.php` |
| "Show me the database schema." | `backend/database/migrations/` (browse the folder) |
| "How do roles work?" | `backend/app/Http/Middleware/EnsureUserHasRole.php` + `routes/api.php` |
| "How does the frontend talk to the backend?" | `frontend/src/lib/api.js` (axios + interceptors) |
| "How does role-based UI work?" | `frontend/src/components/ProtectedRoute.jsx` |
| "How is the readiness color shown in the calendar?" | `frontend/src/components/EventCalendarView.jsx` |
| "How are PDF reports generated?" | `backend/app/Http/Controllers/Api/ReportController.php` + `resources/views/pdf/event-report.blade.php` |
| "Where is the routes file?" | `backend/routes/api.php` |
| "How is data seeded for the demo?" | `backend/database/seeders/DatabaseSeeder.php` |

---

## Compliance with Professor's Rules

| Rule | How we comply |
|---|---|
| No external service APIs (OpenAI, Firebase, etc.) | ✅ Only our own Laravel REST API. No third-party service calls. |
| No machine learning | ✅ Classifier is hardcoded if/elif rules — visible in `EventClassifier.php` |
| No data training | ✅ No model, no training data, no inference |
| Local hosting only | ✅ Runs on Laragon (Apache + MySQL on the team's laptop) |
| LAN access | ✅ Vite + Laravel both bind to 0.0.0.0; accessible at `http://<laptop-ip>:5173` |
| Build everything from scratch | ✅ Backend, frontend, database all custom-built |
| UI component libraries allowed | ✅ Uses FullCalendar, Recharts, Tailwind — these are visual tools, not service APIs |
| Exactly 4 features | ✅ Strictly 4 features, no more |

---

## Numbers Worth Memorizing for Defense

- **6 database tables**
- **~25 API endpoints** total
- **4 main features** (per briefing)
- **3 user roles**
- **8 build phases** completed
- **~30 lines** of classifier logic (the "AI")
- **10 demo users** + **5 demo events** + **18 demo tasks** in the seeder
- **PHP 8.3 / Laravel 12 / React 18 / MySQL 8.4** — all current LTS-ish versions

---

## Demo Login Credentials

All accounts use password: **`password123`**

| Role | Email |
|---|---|
| Administrator | `admin@emd.test` |
| Officer | `maria.officer@emd.test` |
| Officer | `juan.officer@emd.test` |
| Staff | `anna.staff@emd.test` |
| Staff | `mark.staff@emd.test` |
| Staff | `joy.staff@emd.test` |
| Staff | `paolo.staff@emd.test` |
| Staff | `liza.staff@emd.test` |
| Staff | `ben.staff@emd.test` |
| Staff | `carla.staff@emd.test` |

---

## Recommended Demo Flow (for defense)

1. **Login as admin** → tour the dashboard stats
2. **Staff Management** → show user CRUD (admin-only, demonstrates role gating)
3. **Events** → show Calendly-style calendar with classification colors
4. **Click into Sports Fest** → show tasks → mark some Done → close drawer
5. **Click Freshmen Orientation** → mark tasks done → close drawer → watch it turn GREEN (live AI demo)
6. **Analytics** → show donut chart, top urgent list, period filter
7. **Open an event** → click **Generate Report** → show downloaded PDF
8. **Logout** → login as **staff** to show role gating (no Staff/Analytics in sidebar)
9. **Click user menu** → demonstrate Change Password

---

## Related Documents

- `README.md` — Setup guide for teammates and panel
- `ROADMAP.md` — Phased build log (all 8 phases checked off)
- `OVERVIEW.md` — This document
