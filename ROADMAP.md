# EMO Tracker — Project Roadmap

> Capstone project (Capstone 1 build complete; Capstone 2 in progress). Web app for the Events Management Office of New Era University.
> Tech stack: React + Vite + Tailwind (frontend) · Laravel + Sanctum (backend) · MySQL · Laragon (LAN).
> Always pick up from the last unchecked task. Mark `[x]` when complete.

---

## Project Locations
- Project root: `C:\laragon\www\EMOTracker\`
- Backend: `C:\laragon\www\EMOTracker\backend\` (Laravel)
- Frontend: `C:\laragon\www\EMOTracker\frontend\` (React + Vite)
- Backend URL (dev): `http://emotracker.test/` or `http://localhost/EMOTracker/backend/public/`
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
- [x] Create project folder at `C:\laragon\www\EMOTracker\`
- [x] Create ROADMAP.md
- [x] Initialize Laravel backend in `backend/`
- [x] Install Laravel Sanctum + HasApiTokens trait on User model
- [x] Configure backend `.env` (DB connection, app URL, app name)
- [x] Create MySQL database `emo_tracker`
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
- [x] ReportController + Blade PDF template with EMO branding, readiness badge, stats, staff, tasks, docs
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
- [x] Schedule page (client request): sheet-style view of every NEU event, one tab per year, grouped by month, colored by building, searchable; phone card layout; everyone views, admin edits
- [x] Managed venue list linked to buildings (with the client's sheet colors); admins add new venues straight from the event form; free-text room/details
- [x] New event fields: department (with suggestions), end date (multi-day), end time, control #, remarks; times optional
- [x] "The EMO prepares this event" switch: off = "Scheduled" (left out of readiness charts); adding a task switches it on; existing readiness rules unchanged for prepared events
- [x] Cancelled status: cancelled events stay on the schedule, struck through, and are left out of readiness
- [x] Event details are admin-only; officers handle preparation (tasks, documents, reports)
- [x] Events page defaults to EMO-prepared events, with an "All events" toggle
- [x] Sheet-style Schedule table: gridlines, full building colors matching the legend, centered year title, consistent readiness/cancelled badges
- [x] Year tabs show the current and upcoming years (the EMO's focus); past years move into a "Past years" dropdown; past events are never deleted
- [x] Schedule and Analytics ignore out-of-order responses when switching year/period quickly
- [x] Venues page (admin, from the Schedule): add/rename/recolor buildings (12-color light palette), rename/move venues, merge duplicates; venues used by events can't be deleted
- [x] Export to Excel (admin, officer): one year of the Schedule laid out like the EMO's sheet (building colors, gridlines, cancelled struck through)
- [x] Automatic daily backups (database + uploaded documents, first use each day, kept 14 per database; `BACKUP_PATH` can point to a USB drive); `php artisan backup:run`, `backup:list`, `backup:restore`; named snapshots (`backup:run --name=...`) are never deleted automatically
- [x] One-time import of the EMO's schedule spreadsheet: `php artisan schedule:import <file> --dry-run` reads the hand-typed sheet and lists rows to review; safe to re-run
- [x] Security updates: Composer 44 advisories → 0 (Laravel 13.7 → 13.34), npm 11 vulnerabilities → 0 (incl. a Vite file-access bypass)
- [x] Home pages rebuilt around "what needs my attention?": admin/officer get meaningful stats, a Needs attention list (Critical/At Risk prepared events) and This week from the Schedule, every row opens the event; staff Home shows only open tasks (clickable) and clickable events; quick-action cards removed (they duplicated the sidebar; "Reports" led nowhere)
- [x] Text cleanup for the schedule (`php artisan schedule:tidy --dry-run`): consistent title capitalization that keeps acronyms, brand names and Filipino particles; known typos; spacing; rooms ("rm201" → "Room 201"); remarks. Also applied automatically by the importer
- [x] Everyone in the office can be assigned tasks, whatever their role (active accounts only), and everyone can see and open every event; roles still decide who edits (admin: event details, officers: preparation, staff: their own tasks)
- [x] My Tasks for admins and officers too (menu item, plus "My open tasks" on Home)
- [x] An event's People list is automatic (everyone with a task on it), replacing the manual Assigned Staff picker; a staff member's "My Events" are the events where they have a task
- [x] Readiness rules refined: progress is judged only as the event gets close (At Risk within 14 days if under 70% done; Critical within 7 days if under 40% done or most open tasks have no owner, or within 2 days with work left), and an overdue task makes an event Critical at any time. A new event planned weeks ahead no longer shows Critical just because work hasn't started. Each label now says why ("1 task is overdue", "60% done, 14 days to go") in the event panel, on Home and in the PDF report
- [x] Task form notes when a due date falls after the event (tasks lock once the event is over)
- [x] Budget removed everywhere (the EMO doesn't track budgets): database column, form, validation, event panel, PDF, demo data, docs
- [x] Event status lifecycle: Upcoming → Ongoing → Completed automatically by date and time (ongoing from the start time on the first day to the end time on the last day; completed, and its tasks locked, once it ends); Cancelled by hand and reversible. One badge rule on every page (`EventBadges`)
- [x] Rescheduling: when the start date or time changes, the form asks "moved or a correction?"; a moved event remembers where it was first scheduled ("Rescheduled from Fri, Oct 3") on the Schedule, event panel, Home, PDF and Excel export
- [x] Cancelled events stay visible (struck through on the Schedule and Home); their tasks are on hold (tagged in My Tasks, not counted, no new tasks)
- [x] Internal / External type on every event (client requirement): chosen in the form, its own Type column on the Schedule (External stands out), in the event panel, PDF, Excel export and Schedule search
- [x] Analytics rebuilt around calendar periods (week, month, year, all time): every event's status (upcoming, ongoing, completed, cancelled, rescheduled), internal vs external, busiest venues; the readiness section kept, and its urgent list now opens the event
- [x] Importer reads columns by their headers (an optional TYPE column works) and turns the sheet's reschedule pairs ("Resched to June 9" + the June 9 row) into one rescheduled event; unclear cases go to the review list
- [x] Code checker (ESLint) clean, 13 → 0 problems: dialogs mount fresh on each opening (no flash of the previous values), pages load without an extra render, context hooks and the readiness colors moved into plain `.js` files
- [x] Building colors: 12 → 24 (Google Sheets' two lightest rows, the colors the EMO's sheet uses), each checked by a test to keep the Schedule's text and notes readable (4.5:1 contrast or better); the picker marks colors other buildings already use and starts new buildings on a free one. Small notes on colored Schedule rows are now darker so they stay readable on every color
- [x] Venues page keeps its original look (one colored card per building) with small fixes: "+ Add building" / "+ Add venue" and "Save changes" labels, readable text on dark building colors, a plain gray Delete (not faded red) for venues in use with the reason on the page, a visible error when saving fails, and an empty state
- [x] "Overlap" and "External" labels use the same pill style as the status badges (sentence case, no icon) instead of outlined capitals
- [x] Double-booking warning (`VenueClashes`): same venue and room (or the whole venue), overlapping days and times (no time = all day; cancelled and free-text places don't count). The event form warns live while the venue, date and time are chosen (plus a note by the Save button), the event panel shows the clash to everyone, and the Schedule marks upcoming clashes "Overlap" (with the names on hover, and searchable). A warning, not a block: some overlaps are on purpose. The EMO's real 2026 data has 13 clashing pairs, 9 of them upcoming
- [x] 70 new tests (101 total)

### Next up

**Waiting on group mates**
- [x] Bacena: remove unused files and fix a seeder comment
- [x] Sy: Mac launcher (`start.command`, double-click to start), Mac setup steps, and the `.env.example` database fix
- [x] Dropped the unused `event_staff` table, the `Event::staff()` relation, and the seeder lines that filled it. Restoring an older backup now also updates it to the current database structure

**Accounts, before the EMO uses it for real**
- [ ] Create the real members' accounts (names and roles from the client), with at least two administrators (the office head and a backup), since only an admin can reset passwords
- [ ] Log in as the real admin and deactivate the 10 demo accounts (all use `password123`, including `admin@emo.test`)
- [ ] Optional: a terminal command to reset a password, in case every admin is locked out

**Client demo**
- [ ] Mark 3–4 real upcoming events "EMO prepares" and add their real tasks, with owners and due dates
- [ ] Ask the client: are the 13 same-venue overlaps in the 2026 sheet real conflicts? Should "CON" and "College of Nursing" (and similar) be one department? Are odd imported times typos (e.g. "Recognition/Dry Run" at 1:30 AM)?

**Features (check with the adviser first)**
- [x] Venue double-booking warning: warn (not block) when an event overlaps another booking in the same venue and room, and mark clashes on the Schedule
- [x] Professor: more than 4 features are allowed if they fit the system and the client's needs
- [ ] Still open: the user-testing survey format (ISO 25010 or SUS?); email notifications (the client asked; needs internet access and a sending account); whether comments/tagging and an audit trail belong in Capstone 2 or future work

**Pilot and user testing**
- [ ] Install on an EMO office PC, restore the real data, and point `BACKUP_PATH` at a USB drive
- [ ] Let the EMO use it for 1–2 weeks, then run the survey (test scenarios and questionnaire once the format is confirmed)

---

## Notes & Decisions Log
- 2026-05-16: Project initialized. Briefing locked. Color palette: NEU green + muted gold (gold as accent only).
- 2026-05-16: Groupmate suggested Audit Trail (activity log). Deferred — briefing's "exactly 4 features" rule makes adding a 5th risky. Worth revisiting post-defense as a future enhancement or asking adviser if it can count as admin tooling.
- 2026-05-16: Post-completion polish: NEU logo integrated (top bar, login, PDF), date/time display added to all dashboards, copyright footer, StaffManagementPage built (admin user CRUD), ChangePasswordDialog + user menu dropdown.
- 2026-05-16: School year settings UI skipped — not in briefing, not visible to panel, settings rows sit unused but harmless.
- 2026-05-16: Fixed my-tasks endpoint not loading assignee relation (showed "Unassigned" incorrectly on Staff My Tasks page).
- 2026-05-16: Added historical record protection — completed events lock their tasks for staff/officer; admin-only override with explicit confirmation on status changes. Enforced backend (403) + frontend (disabled UI). Strengthens data integrity story for defense.
- 2026-09-28: Capstone 2 kickoff review found access-control gaps, a classifier edge case (fully done events within 2 days showed Critical), and UTC timezone drift. Fixed in Phase 9 with tests. Previously uploaded files in `storage/app/public/documents` are no longer read; reset with `php artisan migrate:fresh --seed` or re-upload.
- 2026-10-02: Client clarified the office is the Events Management Office (EMO), not a department. Renamed everything: app to EMO Tracker, office name, demo accounts to `@emo.test`, database to `emo_tracker`, repo to `EMO-Tracker`. `start.bat` now uses its own folder instead of a hard-coded path.
- 2026-10-02: Client showed their schedule sheet (Google Sheets, one tab per year, colored by building). Built the Schedule page around it. Team decisions: event details are admin-only (officers prepare); a per-event "EMO prepares this event" switch decides readiness tracking, since most of the ~50 monthly bookings need no preparation. Venues are a managed list the EMO can grow, not free text (the sheet had 80 spellings for ~25 places). Real sheet data stays off the repo.
- 2026-10-02: Decided not to support admin-defined columns for now: the client's sheet used the same 7 columns for all 330 rows. Revisit only if user testing shows a need. Old years are kept (moved into a dropdown), never cleared; a year-end Excel export is the planned way to "close" a year.
- 2026-10-02: Imported the client's real 2026 schedule (329 events) into the local database for the client demo, replacing the fake demo events (demo accounts kept). Snapshots: `demo-data-before-import` and `real-data-fresh-import` (restore with `php artisan backup:restore <name>`). Real data stays out of the repo.
- 2026-10-02: Cleaned the imported schedule text (221 values: capitalization, typos like "Assestment" → "Assessment", room numbers, remarks; "HINDI NA PO TULOY ITO" → cancelled). Department synonyms (e.g. "CON" vs "College of Nursing") left as-is pending the client. New reset point: `php artisan backup:restore real-data-clean`.
- 2026-10-02: The EMO has about 5–10 members and all of them handle events, so the staff-only assignment rules were dropped: anyone can own a task, everyone sees every event, and an event's People are its task owners. Kept one owner per task (clear accountability); multi-person tasks wait for user testing. The old `event_staff` table is no longer read; drop it once the demo seeder stops filling it.
- 2026-10-02: Refined the readiness rules after an event 14 days away with one task in progress showed Critical (0% done is under 40%). Old rules: Critical if under 40% done, 2 days or less away, or most tasks unassigned; At Risk if under 70% done, 6 days or less away, or any task unassigned, however far away the event was. New rules: Critical if a task is overdue, or 2 days or less away with work left, or 7 days or less away and under 40% done or most open tasks without an owner; At Risk if 14 days or less away and under 70% done, or an open task without an owner; done tasks no longer need an owner. The "do not revisit" decisions (priority order, 0 tasks = At Risk, all done = On Track, past = Completed) are unchanged. Docs update handed to Jean (`4-Jean-docs.md`); the paper needs the same wording.
- 2026-10-06: Dropped the `event_staff` table (an event's People are its task owners). Snapshot taken first: `php artisan backup:restore before-drop-event-staff`. `backup:restore` now runs migrations after restoring, so older snapshots come back in the current structure.
- 2026-10-06: Client feedback: remove budget; know whether an event is cancelled, ongoing or rescheduled; classify every event Internal or External, visible on the Schedule; analytics on event statuses. Professor allowed more than 4 features. Decisions: only upcoming/completed/cancelled are stored and "ongoing" is computed, so everything listing events that aren't over keeps including ongoing ones; completion (and the task lock) moved from midnight to the event's end time; "Rescheduled" is a mark on an event that moved (it keeps its tasks, documents and readiness) rather than a status or a second row like the EMO's sheet. Existing events default to Internal until the EMO marks the external ones. Snapshot before the migration: `before-status-and-type`. The current real data still has the sheet's old-date reschedule rows; the final import will merge them.
