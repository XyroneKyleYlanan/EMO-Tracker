# EMD Tracker — Project Roadmap

> Capstone project (Capstone 1 build complete; Capstone 2 in progress). Web app for the Events Management Department of New Era University.
> Tech stack: React + Vite + Tailwind (frontend) · Laravel + Sanctum (backend) · MySQL · Laragon (LAN).
> Always pick up from the last unchecked task. Mark `[x]` when complete.

---

## Project Locations
- Project root: `C:\laragon\www\EMDTracker\`
- Backend: `C:\laragon\www\EMDTracker\backend\` (Laravel)
- Frontend: `C:\laragon\www\EMDTracker\frontend\` (React + Vite)
- Backend URL (dev): `http://emdtracker.test/` or `http://localhost/EMDTracker/backend/public/`
- Frontend URL (dev): `http://localhost:5173/`

## Confirmed Decisions (do not revisit)
- Stack: React + Tailwind + Laravel + MySQL + Laragon (LAN-only)
- Auth: Laravel Sanctum, tokens in localStorage
- Classification priority: RED → YELLOW → GREEN; 0-task event = YELLOW; all tasks done = GREEN; past event = "Completed"
- Calendar: FullCalendar or react-big-calendar (UI library, allowed)
- Charts: Recharts (UI library, allowed)
- PDF: barryvdh/laravel-dompdf (Composer package, local generation)
- Brand: Muted NEU dark green primary, gold sparingly as accent, distinct red/amber/emerald hues for status badges
- Mobile: list view default below 768px; calendar grid default on desktop
- Account creation: admin-only; staff can change own password
- Document upload: PDF/DOCX/XLSX/JPG/PNG, max 10 MB, stored privately in `storage/app/private` and downloaded only through the authenticated API (moved from `storage/app/public` in Phase 9)
- School year: configurable in admin settings, default August–May

---

## Phase 0 — Environment & Project Setup
- [x] Verify PHP 8.x, Composer, Node.js, npm, Laragon, MySQL
- [x] Create project folder at `C:\laragon\www\EMDTracker\`
- [x] Create ROADMAP.md
- [x] Initialize Laravel backend in `backend/`
- [x] Install Laravel Sanctum + HasApiTokens trait on User model
- [x] Configure backend `.env` (DB connection, app URL, app name)
- [x] Create MySQL database `emd_tracker`
- [x] Run initial migrations to confirm DB connection
- [x] Initialize React + Vite frontend in `frontend/`
- [x] Install Tailwind CSS v4 + @tailwindcss/vite plugin
- [x] Install axios
- [x] Configure frontend `.env` (VITE_API_BASE_URL)
- [x] Add /api/ping route + ping UI; verify backend (curl 200 OK)
- [x] User confirms browser shows green status card at http://localhost:5173

**Phase 0 complete — 2026-05-16. Full stack operational.**

## Phase 1 — Database Schema & Migrations
- [x] `users` table (extended: role enum, is_active)
- [x] `events` table (name, description, venue, event_date, event_time, budget, status, created_by)
- [x] `tasks` table (event_id, name, description, due_date, status, priority, assigned_to)
- [x] `event_staff` pivot (event_id, user_id, unique)
- [x] `documents` table (event_id, uploaded_by, file_name, file_path, file_size, mime_type)
- [x] `settings` table (key unique, value)
- [x] All Eloquent models with fillable, casts, relationships, role helpers
- [x] DatabaseSeeder: 1 admin + 2 officers + 7 staff + 5 events + 18 tasks + 1 document + 3 settings
- [x] Seed data covers all readiness categories for demo (Completed, On Track, At Risk, Critical, 0-task YELLOW)

**Phase 1 complete — 2026-05-16. Schema and demo data verified.**

## Phase 2 — Authentication & Role-Based Access (Sanctum)
- [x] POST /api/login, /api/logout, /api/change-password, GET /api/me
- [x] EnsureUserHasRole middleware + role alias registered in bootstrap/app.php
- [x] Admin-only user CRUD endpoints (apiResource users)
- [x] ForceJsonResponse middleware prepended to api group (fixes Accept header issue)
- [x] AuthenticationException renders as 401 JSON (no redirect to /login web route)
- [x] React Router setup with /login, /admin, /officer, /staff routes
- [x] AuthProvider with token in localStorage + persistent user state
- [x] axios interceptor (Bearer token + 401 redirect)
- [x] ProtectedRoute with allowedRoles role gating
- [x] Branded login page (NEU green theme)
- [x] Placeholder dashboards per role + logout button
- [x] All 3 roles tested in browser, role gate verified

**Phase 2 complete — 2026-05-16. Authentication and role-based access verified end-to-end.**

## Phase 3 — Dashboards & Layout Shell
- [x] Backend: DashboardController with admin/officer/staff endpoints + role gating
- [x] AppLayout shell: sticky top bar + sidebar (md+) + bottom nav (mobile)
- [x] Inline SVG icon library (icons.jsx)
- [x] Reusable StatCard + QuickNavCard components
- [x] AdminDashboard: 4 stat cards + 4 quick-action cards
- [x] OfficerDashboard: same stats (read-only) + 2 quick actions
- [x] StaffDashboard: personal stats + My Tasks list + My Events grid
- [x] ComingSoon placeholder for routes not yet built
- [x] Loading skeletons on all data fetches
- [x] Mobile responsive verified

**Phase 3 complete — 2026-05-16. App now looks like a real product.**

## Phase 4 — Feature 1: Events (Calendar + List View)
- [x] Event readiness accessor on Event model (RED → YELLOW → GREEN priority, 0-task = YELLOW, past = Completed)
- [x] EventController: index/show/store/update/destroy + auto-complete past upcoming events on index
- [x] Routes: read for all auth, write for admin+officer, delete admin-only
- [x] UserController index/show relaxed to admin+officer for staff assignment dropdown
- [x] FullCalendar (@fullcalendar/react + daygrid + interaction) installed
- [x] EventsPage with calendar/list view toggle (mobile auto-switches to list <768px)
- [x] EventCalendarView with custom eventContent (smaller pills, hover tooltip, lift animation)
- [x] EventListView with EventCard grid
- [x] ReadinessBadge component (On Track / At Risk / Critical / Completed)
- [x] EventDetailDrawer (slides from right, shows event + tasks + staff + docs placeholder)
- [x] EventFormDialog (create/edit form with staff multi-select)
- [x] Polished FullCalendar styling to match NEU brand (light theme, green active state)
- [x] Shared format.js utility (12-hour time, friendly dates) used everywhere

**Phase 4 complete — 2026-05-16. Centerpiece feature working with full polish.**

## Phase 5 — Feature 2: Tasks & Staff Assignment
- [x] TaskController: index (per event) / store / update / updateStatus / destroy / myTasks
- [x] Staff can update status of own tasks only; admin/officer can update any
- [x] Admin+officer can create/edit/delete tasks
- [x] TaskRow component with inline status dropdown + edit/delete (permission-aware)
- [x] TaskFormDialog (create/edit with assignee + priority + status)
- [x] EventDetailDrawer updated to use TaskRow + "+ Add task" + onChanged callback
- [x] StaffTasksPage with stat cards + status filter tabs + TaskRow list
- [x] EventsPage refetches on drawer onChanged → calendar colors update live as tasks complete

**Phase 5 complete — 2026-05-16. Live readiness updates verified.**

## Phase 6 — Feature 3: Rule-Based Classification + Analytics
- [x] EventClassifier service class (App\Services\EventClassifier) with RED → YELLOW → GREEN priority
- [x] Event model readiness accessor delegates to EventClassifier
- [x] GET /api/analytics?period=week|month|all (admin + officer only)
- [x] AnalyticsController returns stats + distribution + top 5 urgent
- [x] Recharts installed for donut chart
- [x] AnalyticsPage with period filter buttons + 4 stat cards + donut + top urgent list
- [x] ReadinessDonut component with center "Total Events" overlay + clean hover tooltip
- [x] Routes wired for /admin/analytics and /officer/analytics
- [x] Edge cases verified: 0-task events show YELLOW, past events show Completed

**Phase 6 complete — 2026-05-16. AI feature + analytics dashboard fully live.**

## Phase 7 — Feature 4: Reports & Document Management
- [x] barryvdh/laravel-dompdf installed (v3.1)
- [x] storage:link symlink created for public file access
- [x] DocumentController: index/store/download/destroy
- [x] ReportController + Blade PDF template with EMD branding, readiness badge, stats, staff, tasks, docs
- [x] Routes: upload/delete admin+officer; download/report for all authenticated
- [x] Validation: PDF/DOC/DOCX/XLS/XLSX/JPG/PNG, max 10MB, on both frontend and backend
- [x] Frontend: document upload/list/delete UI in EventDetailDrawer (multipart/form-data)
- [x] Frontend: "Generate Report" button in drawer footer with blob download
- [x] Shared downloadFile helper in lib/download.js for authenticated PDF/file downloads
- [x] PDF polish: single readiness badge at top, status in details, bigger title, dark text

**Phase 7 complete — 2026-05-16. All four briefing features implemented.**

## Phase 8 — Polish, Responsiveness, LAN Demo Prep
- [x] Loading skeletons + empty states in all data fetches (done throughout phases)
- [x] Form validation messages (handled per-form via field-level errors)
- [x] Toast notification system (ToastProvider + useToast hook) + slide-in animation
- [x] Toasts wired into TaskRow, EventDetailDrawer, EventsPage for status/upload/delete/save
- [x] Vite dev server proxies /api and /storage to Laravel (relative API URL works from any device)
- [x] Vite binds host:true → accessible on LAN as http://<laptop-ip>:5173
- [x] LAN test verified from second device on same WiFi
- [x] README.md with setup, demo credentials, reset command, project structure
- [x] Demo data reset documented: `php artisan migrate:fresh --seed`

**Phase 8 complete — 2026-05-16. Project shipped.**

---

# 🎯 PROJECT COMPLETE — 2026-05-16

All 8 phases done. All 4 required features delivered. Full stack working end-to-end on LAN.

### Next steps before defense:
1. Reset demo data: `php artisan migrate:fresh --seed`
2. Verify both servers start (Laravel `php artisan serve` + frontend `npm run dev`)
3. Test the demo flow end-to-end with each role
4. Practice the "AI feature live demo" — log in as admin, open Sports Fest, mark tasks done, watch calendar color change
5. Print or PDF the ROADMAP and feature briefing for the panel

---

# Capstone 2

## Phase 9 — Post-review hardening
- [x] Deactivated accounts rejected on every route (Sanctum token check) + tokens revoked on deactivation
- [x] Staff limited to events they're assigned to (event staff or task) across events, tasks, documents, reports, staff dashboard
- [x] Past events marked Completed at the start of every API request (`CompletePastEvents` middleware), not only when /events or /analytics loads
- [x] Completed events locked for officers at the event level too; event status now follows the date (admin rescheduling reopens)
- [x] Classifier: all tasks done = GREEN regardless of days remaining
- [x] Timezone set to Asia/Manila; event/task dates serialized as plain `YYYY-MM-DD`
- [x] PDF report filename slugged (names containing "/" no longer crash)
- [x] Admin can't deactivate or demote themselves (backend + disabled form fields)
- [x] Documents moved to private storage; `file_path` hidden from API responses
- [x] Seeder generates the sample Foundation Day PDF so its download works on a fresh clone
- [x] Only active staff can be newly assigned to events and tasks
- [x] Analytics "Top 5 most urgent" ranked by readiness, then date
- [x] Login rate limited (10 attempts/minute)
- [x] Frontend: dates shown correctly in any browser timezone, stay logged in if backend is briefly down, mobile nav spacing
- [x] Automated test suite: 30 PHPUnit tests (`php artisan test`)

## Phase 10 — Capstone 2 improvements
- [x] Task detail view: click a task (event drawer or My Tasks) to see its description, assignee, due date, priority, and event; Edit shortcut for admin/officer

---

## Notes & Decisions Log
- 2026-05-16: Project initialized. Briefing locked. Color palette: NEU green + muted gold (gold as accent only).
- 2026-05-16: Groupmate suggested Audit Trail (activity log). Deferred — briefing's "exactly 4 features" rule makes adding a 5th risky. Worth revisiting post-defense as a future enhancement or asking adviser if it can count as admin tooling.
- 2026-05-16: Post-completion polish: NEU logo integrated (top bar, login, PDF), date/time display added to all dashboards, copyright footer, StaffManagementPage built (admin user CRUD), ChangePasswordDialog + user menu dropdown.
- 2026-05-16: School year settings UI skipped — not in briefing, not visible to panel, settings rows sit unused but harmless.
- 2026-05-16: Fixed my-tasks endpoint not loading assignee relation (showed "Unassigned" incorrectly on Staff My Tasks page).
- 2026-05-16: Added historical record protection — completed events lock their tasks for staff/officer; admin-only override with explicit confirmation on status changes. Enforced backend (403) + frontend (disabled UI). Strengthens data integrity story for defense.
- 2026-09-28: Capstone 2 kickoff review found access-control gaps, a classifier edge case (fully done events within 2 days showed Critical), and UTC timezone drift. Fixed in Phase 9 with tests. Previously uploaded files in `storage/app/public/documents` are no longer read; reset with `php artisan migrate:fresh --seed` or re-upload.
