# Description Notes

Facts for writing the figure and table descriptions **in your own words**. Each item matches the paper's checklist and numbering. Every fact was checked against the system (October 10, 2026), so you only need to turn the notes into sentences.

The paper must pass Turnitin at 90–94% human, so write the paragraphs yourself from these notes. Don't paste from `Paper_Descriptions.html`, `Diagram_Descriptions.md` or `Table_Descriptions.md`: those are AI-written.

---

## How to write a description

1. **Start with what the figure or table is:** "Figure 3.X shows/presents ...".
2. **Write one sentence for each one or two notes,** in your own words and in the order given.
3. **End with why it matters** if the notes give a reason (the last note usually does).
4. **Length:**
   - **Chapter 3:** 3–5 sentences.
   - **Appendices:** 1–2 sentences, or just the caption if your adviser says that's enough.
5. **Plain, simple sentences are fine.** You don't need fancy wording, and your own style is what passes.

### Example (a made-up library system, not ours)

**Notes:**
- Figure 3.X – Book Borrowing Flowchart
- Shows: the steps when a student borrows a book
- Student's ID scanned → system checks for unpaid fines
- Has fines → borrowing blocked, message shown
- No fines → book scanned, due date set 7 days later
- Why: stops students with unpaid fines from borrowing more

**Description written from the notes:**

> Figure 3.X shows the steps a student goes through when borrowing a book. First, the student's ID is scanned and the system checks if they have unpaid fines. If they do, the system blocks the request and shows a message. If not, the librarian scans the book and the system sets the due date to seven days later. This makes sure students with unpaid fines cannot borrow more books.

Each note became about one sentence, in the writer's own words. Do the same with the notes below.

---

## Capstone Paper (Chapter 3): 3–5 sentences each

### Figure 3.1 - Prototyping Model Diagram
- SDLC used: Prototyping Model. Build a working version early, evaluate it with the EMO, refine, repeat
- Phases: requirements gathering and analysis → quick design → build prototype → evaluation → refine (if not yet meeting needs) or implementation and maintenance (if it does)
- Capstone 1: title and requirements Feb–Mar 2026; database and UI/UX design Mar–Apr; first prototype Apr–May; evaluated at the pre-oral defense (May 2026)
- Capstone 2: EMO consultations (Oct 2 and Oct 6, 2026) and their 2026 schedule sheet led to new rounds: Schedule page, venues, spreadsheet import, backups, event statuses, double-booking warning; pre-defense audit Oct 7
- Next: installation and pilot at the EMO (Oct 2026), then the Capstone 2 defense (Nov 2026)
- Why this model: the EMO's needs became clearer as they saw working versions

### Figure 3.2 - Entity Relationship Diagram
- Crow's foot notation; the 7 tables plus the login token table
- Center: EVENTS. Name, type (internal/external), department, venue and room, dates and times, original date/time if rescheduled, control number, remarks, "EMO prepares it", status (upcoming/completed/cancelled)
- An event: optional venue (a venue can belong to a building, whose color is used on the Schedule); 0 or more tasks; 0 or more documents
- A user: creates events, may own tasks (one owner per task at most), uploads documents, signs in with login tokens
- SETTINGS: stand-alone, reserved for settings
- Deletion rules: deleting an event deletes its tasks and documents; a venue used by events can't be deleted (merge it instead); accounts are deactivated, never deleted, so records keep their author
- Timestamps left out of the diagram; every column is in the data dictionary

### Figure 3.3 - Context Diagram
- Level 0 data flow diagram: the whole system as one process (Process 0); Yourdon-DeMarco notation (circle = process, rectangles = external entities, arrows = data flows)
- 5 external entities: Administrator, Officer, Staff, the EMO's schedule spreadsheet, backup storage
- Administrator gives: login details; event, venue, building and account details; tasks and documents. Gets: schedule with readiness, analytics, PDF reports and Excel export, backup status
- Officer gives: login details, tasks and assignments, documents. Gets: schedule with readiness, analytics, documents, PDF reports and Excel export
- Staff give: login details, status updates for their own tasks. Get: schedule and event details, assigned tasks, documents and PDF reports
- Spreadsheet: schedule rows, imported once at installation. Backup storage: daily backup of the database and documents

### Figure 3.4 - Use Case Diagram
- UML notation: 3 actors (stick figures), use cases (ovals) inside the "EMO Tracker" system boundary
- Generalization (hollow triangles): an Officer can do everything Staff can; the Administrator can do everything an Officer can
- Staff (so every user): log in and log out, change own password, view Home/Events/Schedule, view event details, download documents and PDF reports, view my tasks, update own task status
- Officer adds: manage tasks (add, edit, delete, assign), upload documents, view Analytics, export the Schedule to Excel
- Administrator adds: add and edit events; cancel, restore or delete events; manage venues and buildings; manage accounts; delete documents; see backup status
- «include»: adding or editing an event always runs the venue overlap check. «extend»: recording a reschedule, only when an event's date or time changes
- The server checks these permissions on every request

### Table 1 - Risk Assessment and Analysis
- 16 risks (R1–R16); for each: category, likelihood, impact, risk level (likelihood × impact, before mitigation), mitigation
- Result: 3 high, 9 medium, 4 low
- The 3 high risks all come from using one laptop and one network: R1 laptop failure or downtime, R2 data loss, R3 network unavailable
- R1: keep the laptop plugged in; documented steps to move the system to another laptop and restore the latest backup
- R2: automatic daily backups (USB drive), backup before every update, restore command, warning on the Administrator's Home if backups fail
- R3: own router or hotspot; works on a local network without internet; the server laptop itself can still use the system
- Medium examples: unauthorized access, double-booking, resistance to leaving the spreadsheet, spreadsheet migration errors. Low examples: forgotten passwords, accidental deletion

### Figure 3.5 - Network Topology
- Star topology on the office's local network: every device connects to one Wi-Fi router or access point (university Wi-Fi or an office router)
- Server laptop (the EMO's Windows laptop): Laragon (PHP 8.3, MySQL 8.4), Laravel API (127.0.0.1:8000), React app (port 5173), private storage for documents
- USB drive attached to the server: daily backups
- Users' laptops, computers, tablets and phones open it in a browser at the server's address (e.g., http://192.168.1.5:5173); nothing is installed on them
- The role labels on the devices are examples: any role can use any device
- No internet or cloud needed, so the data stays inside the office

### Table 2 - Compatibility Checking Table
- Lists the software and hardware the system needs: version, type (software/hardware), role in the system
- Backend: Laravel 13, PHP 8.3 or newer, Laravel Sanctum (login tokens), dompdf (PDF reports), PhpSpreadsheet (Excel import and export), MySQL 8.4
- Frontend: React 19, React Router, Vite, Tailwind CSS, axios, FullCalendar (calendar), Recharts (chart). These are libraries inside the system, not outside services
- Fonts: San Francisco on Apple devices; Inter on others (bundled, works offline)
- Tools: Composer, Node.js 20, npm, Laragon (Windows 10/11), Homebrew (Mac), a web browser (tested in Chrome)
- Hardware: server laptop, Wi-Fi router or hotspot, USB drive for backups, users' devices
- Point: all free, installed locally, runs on the office's existing laptop and network

---

## Appendix D: 1–2 sentences each

### Database Schema / SQL Scripts
- The SQL that creates the database (MySQL 8.4): the 7 application tables and the login token table, in the order they depend on each other
- Each statement: columns and data types, keys, indexes, foreign keys with what happens on delete
- Tested: an empty database built from it matched the real one
- (Optional) Since Capstone 1: event_staff removed, buildings and venues added, new event columns, budget removed

### API Endpoint Documentation
- 42 endpoints in 9 groups; for each: path, method, who can use it, parameters, success response, error codes
- Shared rules: paths start with /api; JSON; login token needed except for login; 401 not logged in, 403 role not allowed, 404 not found, 422 invalid input
- Checked against the system's route list

### Data Dictionary
- All 64 columns of the 7 application tables: field name, data type, constraints, meaning
- Goes with the ERD (Figure 3.2)

### Figure D.1 to D.3 - System Flowchart (Admin, Officer, Staff)
- One flowchart per role, one session from login to logout; symbols: ovals (start/end), rectangles (process), parallelograms (input), diamonds (decision)
- All start the same: enter email and password → wrong or deactivated? show the reason, try again → the role's Home
- D.1 Administrator: events (overlap check: a warning, but can still save), venues, accounts, tasks and documents, analytics and reports
- D.2 Officer: prepare an event (completed → tasks locked; otherwise add tasks → readiness recalculated), upload documents (PDF, Word, Excel, JPG, PNG up to 10 MB), monitor, reports
- D.3 Staff: update own task status (only own tasks, only if the event isn't completed), check events, download files

### Figure D.4 - Data Flow Diagram Level 1
- Breaks Process 0 of the context diagram (Figure 3.3) into 7 processes: 1.0 login and accounts, 2.0 events and overlaps, 3.0 venues and buildings, 4.0 tasks, 5.0 readiness and analytics, 6.0 documents and reports, 7.0 import and backup
- 5 data stores: D1 Users, D2 Events, D3 Venues and buildings, D4 Tasks, D5 Documents
- One row per process so no lines cross; repeated entities and stores marked with *
- Balanced with the context diagram

### Figure D.5 - Class Diagram
- Main backend classes; the domain classes match the tables: User, Event, Task, Document, Venue, Building
- Event is the central class (readiness, ongoing, status)
- Composition: an Event has its Tasks and Documents (deleted with it). Aggregation: a Building groups Venues
- 5 service classes: EventClassifier (readiness), VenueClashes (overlaps), ScheduleImport, ScheduleExport, Backup
- Controllers left out for readability

### Figure D.6 to D.8 - Sequence Diagram
- Show the order of messages between the user, browser (React), Laravel API, services and MySQL
- D.6 Login: login limit (10 tries a minute), password and active-account check, 30-day token; failures: 422 (wrong details or deactivated), 429 (too many tries); later requests carry the token
- D.7 Adding an event: the overlap check runs while typing (waits 0.4 s); clashes → warning, but saving is still allowed; status set from the date when saved
- D.8 Updating a task: checks ownership and whether the event is completed (403 if not allowed); saved → readiness recalculated

---

## Appendix E: 1–2 sentences each

### Figure E.1 to E.3 - Site Map (Admin, Officer, Staff)
- An indented tree per role: login → menu pages → what each page contains; two columns to fit a page
- E.1 Administrator: 7 entries (Home, My Tasks, Events, Schedule with Venues, Analytics, Accounts, user menu)
- E.2 Officer: 6 entries; event details view only; no Accounts, Venues or event form
- E.3 Staff: 5 entries; view only, except the status of their own tasks

### Figure E.4 to E.7 - User Journey Map (Current Process, Admin, Officer, Staff)
- Each map: stages, with actions, touchpoint, thought, feeling, pain point, opportunity, and a feelings curve
- E.4 Current process (As-Is): overlaps found only by noticing them (13 pairs in 2026), about 80 spellings for about 25 venues, changes hidden in text, no task tracking, no readiness check, reports by hand; the curve stays low
- E.5–E.7 With EMO Tracker (To-Be): expected feelings, to be confirmed in the pilot; main dips: the Administrator resets forgotten passwords, Officers can't change event dates, Staff wait for the Administrator on a forgotten password

### Figure E.8 - UI Wireframes (All Roles)
- Low-fidelity, grayscale layouts; gray bars stand for text; real button names
- Administrator (desktop): Home, Schedule, new event form with the overlap warning, Accounts
- Officer (desktop): calendar, event panel, new task form, Analytics
- Staff (phone): login, Home, My tasks, view-only event panel

---

## Appendix G: 1–2 sentences each

### Figure G.1 - Events Management Office Chart
- The EMO's structure: Director at the top → Ministrong Tagasubaybay below; the Secretary beside them; 5 Tech Support under the Secretary

### Figure G.2 - Gantt Chart
- The whole project, Feb–Nov 2026: Capstone 1 rows kept, Capstone 2 added in the same phases, plus implementation and evaluation
- Key dates: pre-oral defense May 14; installation, pilot and survey at the EMO Oct 13–31; final defense in the first week of November
- Colors: gray = done, light blue = in progress, purple = planned; diamonds = the two defenses

---

## Modules 1 to 10: notes for rewriting the "Explanation:" under each code block

The explanations you already pasted were AI-written, so rewrite each one from these notes (2–3 sentences per code block).

### Module 1 - Login and Authentication
- **login():** checks an email and password were given; finds the user; checks the password against the stored hash (never stored as plain text); same message whether the email or the password is wrong; deactivated accounts refused; success → login token valid 30 days, plus the user's name and role
- **boot():** tokens of deactivated accounts stop working everywhere; login limited to 10 tries a minute per email and device, then a "too many attempts" message
- **changePassword():** the current password must be right; the new one needs at least 8 characters, typed twice; saved as a hash; other devices signed out
- **AuthContext login():** saves the token and user in the browser so a reload keeps you signed in; every request carries the token; an expired token sends you back to login

### Module 2 - Event Planning and Scheduling
- **store():** checks the details; events are internal unless marked external; status set from the date (a past date → completed); records who created it
- **update():** if the date or time moves and it's a reschedule, the original date and time are kept ("Rescheduled from ..."); moving it back removes the mark; status recalculated; a cancelled event stays cancelled until restored
- **Event model:** only upcoming, completed and cancelled are stored; ongoing is worked out from the date and time; on every request, finished events are marked completed, which locks their tasks

### Module 3 - Task Assignment and Tracking
- **store():** no tasks on cancelled events; on completed events only the Administrator; a task has a name, description, due date, status, priority and at most one owner (active members only); the first task marks the event as one the EMO prepares
- **updateStatus():** Staff change only their own tasks; Officers and the Administrator any task; a completed event's tasks only by the Administrator; all checked on the server

### Module 4 - Rule-Based Event Readiness Classification
- **assess():** fixed rules checked in order; the first match gives the label and the reason. Not classified: cancelled, completed or schedule-only. No tasks → At Risk; all done → On Track. Critical: a task overdue, 2 days or less, 7 days or less with under 40% done, or 7 days or less with more than half of open tasks unassigned. At Risk: 14 days or less with under 70% done, or any open task unassigned. Otherwise On Track. No machine learning or outside service
- **AnalyticsController index():** statistics for a period (week, month, year, all time); schedule statistics for all events; preparation statistics for EMO-prepared events (readiness distribution, most urgent)

### Module 5 - Reports and Document Management
- **store() / download():** only PDF, Word, Excel, JPG and PNG up to 10 MB, with a clear reason when refused; saved in private storage in a folder per event; name, size, type and uploader recorded; download only for signed-in users; upload by Officers and the Administrator, delete by the Administrator only
- **ReportController event():** builds the PDF on the server (dompdf): event details, tasks and owners, documents, venue, task summary and completion %; looks the same on every device

### Module 6 - User Account Management
- **store():** only the Administrator creates accounts (no public sign-up); unique email, password of at least 8 characters (saved as a hash), a role
- **update():** edits name, email, role, status or password; the Administrator can't deactivate themselves or remove their own admin role
- **destroy():** deactivates instead of deleting, so records keep their author; a deactivated account, or one given a new password, is signed out everywhere
- **signOutEverywhere():** deletes the account's login tokens (it can keep the current device signed in)

### Module 7 - Role-Based Access Control
- **EnsureUserHasRole:** 401 if not logged in; 403 if the account is deactivated; 403 if the role isn't allowed for that route
- **routes/api.php:** login is public but limited; everything else needs the token; routes grouped by who can use them: all users, Officers and the Administrator, the Administrator only
- **ProtectedRoute.jsx:** the browser-side guard (sends you to login, or to your own Home); the real protection is the server check

### Module 8 - Schedule and Venue Management
- **ScheduleController index() / export():** one year of bookings laid out like the EMO's spreadsheet, with building colors, status, readiness and overlaps; the year tabs; export gives the same year as an Excel file
- **VenueController merge():** moves all events from a duplicate venue to another one, then deletes the duplicate (all in one step); keeps one name per place, which the colors, the overlap check and the statistics depend on

### Module 9 - Venue Double-Booking Warning
- **between() / sameRoom() / hours():** a clash needs the same listed venue (typed places aren't checked), neither cancelled, the same room or the whole venue, overlapping days and hours; no start time = all day; no end time = until midnight; past midnight = all day; times that only touch don't overlap
- **clashes():** called by the event form while it's being filled in, before saving; leaves out the event being edited; it's a warning, so saving is still allowed

### Module 10 - Data Protection and Migration
- **DailyBackup handle():** runs after the response, on the first request each day, so nobody waits; once a day; if it fails, retried an hour later
- **Backup create() / write() / prune():** a failure is recorded so the Administrator's Home shows a warning; saves the database and all documents in one .zip (a folder, or a USB drive with BACKUP_PATH); keeps the newest 14; named snapshots are never deleted automatically
