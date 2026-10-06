# EMO Tracker — Project Overview

> Architectural reference document. Use this to familiarize yourself or your team with the full app, and as a quick lookup during defense.

---

## High-level

A web application that helps the Events Management Office of NEU plan and track events. Replaces their manual spreadsheet/paper workflow with a centralized system accessible from any device on the school WiFi.

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

## Backend (Laravel 13 / PHP 8.3)

**Location:** `C:\laragon\www\EMOTracker\backend\`

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

## Frontend (React 19 / Vite / Tailwind v4)

**Location:** `C:\laragon\www\EMOTracker\frontend\`

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

Database name: `emo_tracker` — 6 tables.

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
  │            └──── documents (id, file_name, file_path,
  │                    file_size, mime_type, uploaded_by)
  │
settings (id, key, value) ◀── seeded with school year config (unused but harmless)
```

**Design rationale:**
- `users` has 3 roles (admin/officer/staff) via an enum column — simple and clear
- An event's People is everyone with a task on it, so no separate staff list is stored (the old `event_staff` table was removed in Capstone 2)
- `tasks` has `assigned_to` directly (one owner per task — clear accountability in a small office); anyone can own a task, whatever their role
- `documents` are linked to events with `cascadeOnDelete` so deleting an event cleans up its docs
- All foreign keys use `cascadeOnDelete` or `nullOnDelete` to maintain referential integrity

---

## The "AI" Feature (Rule-Based Classifier)

**File:** `app/Services/EventClassifier.php`

This is the centerpiece of the defense. It's clean if/elif logic — no machine learning, no external APIs, no data training:

```
1. If event is cancelled → "cancelled"; if completed → "completed"
2. If the EMO only schedules it (doesn't prepare it) → "scheduled"
3. If event has 0 tasks → return "yellow" (nothing planned yet)
4. If every task is done → return "green" (nothing left to do, however close the date)
5. Compute: days to go, % done, overdue tasks, open tasks with no owner
6. Check RED conditions first:
   - any task is overdue (past its due date, not done), OR
   - days to go ≤ 2 and work is still open, OR
   - days to go ≤ 7 and (% done < 40 OR most open tasks have no owner)
7. Check YELLOW conditions second:
   - days to go ≤ 14 and % done < 70, OR
   - any open task has no owner
8. Otherwise → return "green"
```

Progress is only judged as the event gets close, so an event planned weeks ahead isn't flagged just because work hasn't started; an overdue task is flagged at any time. Each result comes with a short reason ("1 task is overdue", "60% done, 14 days to go") shown in the event panel, on Home and in the PDF report.

The Event model's `readiness` accessor delegates to this service. Called automatically every time an event is fetched — the readiness color updates **live** as tasks change status.

---

## User Roles & Permissions

| Role | Can do | Cannot do |
|---|---|---|
| **Administrator** | Everything: manage user accounts, event details (date, time, venue), venues, tasks, documents, analytics, reports, Excel export | (nothing restricted) |
| **Officer** | Event preparation: add, edit and assign tasks; upload and delete documents; view analytics; export the Schedule to Excel | Edit event details or delete events; manage user accounts or venues |
| **Staff** | See every event and the Schedule; update the status of their own tasks; download reports and documents; change own password | Create or edit events or tasks, assign tasks, view analytics, manage users |

Everyone, whatever their role, can be given tasks and has a **My Tasks** page: the EMO is a small office where everyone handles events.

Role enforcement happens in **two places**:
- **Backend:** middleware on routes (`role:admin,officer`)
- **Frontend:** `ProtectedRoute` component checks user role before rendering pages

This double-gating means a malicious user can't bypass the UI to hit forbidden endpoints. On top of the role checks, the backend also checks each record: staff get HTTP 403 for events they aren't assigned to (and their tasks, documents, and reports). Deactivating an account signs it out everywhere, and an administrator can't deactivate or demote themselves, so there's always at least one active admin.

### Historical record protection

At the start of every API request, the backend marks events whose date has passed as "Completed" (`CompletePastEvents` middleware), so the lock never depends on which page someone opened first. Once an event is Completed, the event and its tasks are **locked**:
- Staff and Officers can no longer edit the event, or change status, edit, delete, or add tasks
- Only an Administrator can modify completed events and their task records (a "break glass" path for genuine corrections). If an Administrator moves a completed event to a future date, it reopens as Upcoming
- Any status change an Admin makes to a completed event requires explicit confirmation
- Enforced on the backend (HTTP 403) — cannot be bypassed via the UI

This protects the integrity of post-event reports. Key files: `CompletePastEvents.php`, `EventController.php`, `TaskController.php` (backend checks), `TaskRow.jsx` and `EventDetailDrawer.jsx` (UI lock state).

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
| "How do you know it works?" | `backend/tests/` (run `php artisan test`) |
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
- **33 API routes** (~30 endpoints, since updates accept both PUT and PATCH)
- **30 automated tests** (`php artisan test`)
- **4 main features** (per briefing)
- **3 user roles**
- **8 build phases** completed
- **About 100 lines** of classifier logic (the "AI"), including the reason shown with each result
- **10 demo users** + **5 demo events** + **18 demo tasks** in the seeder
- **PHP 8.3 / Laravel 13 / React 19 / MySQL 8.4** — all current versions

---

## Demo Login Credentials

All accounts use password: **`password123`**

| Role | Email |
|---|---|
| Administrator | `admin@emo.test` |
| Officer | `maria.officer@emo.test` |
| Officer | `juan.officer@emo.test` |
| Staff | `anna.staff@emo.test` |
| Staff | `mark.staff@emo.test` |
| Staff | `joy.staff@emo.test` |
| Staff | `paolo.staff@emo.test` |
| Staff | `liza.staff@emo.test` |
| Staff | `ben.staff@emo.test` |
| Staff | `carla.staff@emo.test` |

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
