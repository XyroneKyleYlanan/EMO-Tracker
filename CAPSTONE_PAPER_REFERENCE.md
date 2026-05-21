# EMD Tracker — Capstone Paper Reference Document

> **For groupmates writing the capstone manuscript.**
> This document contains all the technical facts, design decisions, and project details needed to write the paper. Paste relevant sections into an AI assistant (ChatGPT / Claude / Gemini) to help draft chapters. **Do not paste this entire document at once** — feed your AI only the sections relevant to the chapter you're working on.

---

## 1. PROJECT IDENTIFICATION

- **Project Title:** EMD Tracker: A Web Application for Event Planning and Task Management with Rule-Based Event Readiness Classification for the Events Management Department of New Era University
- **Project Type:** Web Application (Responsive — works on desktop and mobile browser)
- **Client / Beneficiary:** Events Management Department (EMD), New Era University
- **Client Address:** No. 9 Central Avenue, New Era, Quezon City, Philippines
- **Proponents:**
  - Ylanan, Xyrone Kyle E.
  - Bacena, Phil Jade P.
  - Sy, Joana Daphne T.
  - Zabala, Jean Simone L.
- **Program:** BS Information Technology (3BSIT-2)
- **Subject:** Capstone 1
- **Adviser:** Prof. Teresita C. Alcantara
- **Institution:** New Era University, College of Informatics and Computing Studies
- **Year:** 2026

---

## 2. BACKGROUND OF THE STUDY

The Events Management Department (EMD) of New Era University is responsible for organizing and overseeing university events such as Foundation Day, Freshmen Orientation, Sports Fests, Faculty Recognition Nights, and Inter-College competitions. The department currently has approximately ten (10) members.

**Existing problems with manual event management:**
1. No centralized tracking of event tasks and assignments — information is scattered across spreadsheets, paper records, and informal tools.
2. Difficulty monitoring how ready an event is before it happens — the department lacks an objective way to determine which events require urgent attention.
3. No automated reporting after events — generating post-event summary reports is manual and time-consuming.
4. Staff assignments are loosely tracked — sometimes leading to overlapping responsibilities or unassigned tasks.

**Proposed solution:**
EMD Tracker is a responsive web application that replaces these manual methods with a centralized, data-driven system. It runs on the department's local area network (LAN) via a single laptop acting as the server. The system features a Calendly-inspired event calendar with automatic readiness classification, real-time task tracking, role-based access control, and PDF report generation.

---

## 3. STATEMENT OF THE PROBLEM

**General problem:** The Events Management Department of New Era University lacks a centralized, automated system for managing event planning, task assignments, staff coordination, and post-event reporting.

**Specific problems:**
1. How can the department centralize event planning information that is currently scattered?
2. How can task assignments be tracked clearly with accountability?
3. How can the department objectively monitor event readiness before each event?
4. How can post-event reports be generated efficiently with consistent formatting?

---

## 4. OBJECTIVES

**General objective:** To develop EMD Tracker, a responsive web application that centralizes event planning, task tracking, staff assignment, readiness classification, and reporting for the Events Management Department of New Era University.

**Specific objectives:**
1. To design and implement an event planning module that allows authorized users to create, edit, and view events through both a Calendly-inspired calendar and list view.
2. To implement a task and staff assignment tracking module that supports real-time status updates with role-based permissions.
3. To develop a rule-based event readiness classification system that automatically categorizes events as On Track (green), At Risk (yellow), Critical (red), or Completed without using external AI services or machine learning.
4. To provide an analytics dashboard with stat cards, a readiness distribution donut chart, and a list of the most urgent events.
5. To enable PDF report generation and secure document management (upload/download/delete) for each event.
6. To enforce role-based access control with three roles (Administrator, Officer, Staff) using token-based authentication.
7. To ensure the system runs locally on the EMD's network without dependency on external cloud services or paid hosting.

---

## 5. SCOPE AND LIMITATIONS

### Scope

- The system is designed exclusively for **internal use** by the Events Management Department of New Era University.
- The system supports approximately ten (10) concurrent users (the department's full staff complement).
- The system runs on a local area network (LAN) using Laragon as the local server.
- The system supports three user roles: Administrator, Officer, and Staff.
- The system implements exactly four core features: (1) Event Planning and Scheduling, (2) Task and Staff Assignment Tracking, (3) Rule-Based Event Readiness Classification, and (4) Event Progress Reports and Document Management.
- The system is responsive and works on both desktop and mobile browsers.

### Limitations

- The system requires that all users be connected to the same local network (WiFi or hotspot) as the server laptop.
- The system does not function offline (the React frontend requires connection to the Laravel API).
- User accounts cannot be self-registered — they must be created by an Administrator.
- The system does not include audit trail or activity logging in its current version (proposed as future enhancement).
- The system does not integrate with external services such as email, SMS, calendar APIs, or cloud storage — all operations are local.
- The readiness classification is **rule-based and hardcoded**; it does not learn or adapt from historical data (per professor's requirement of no machine learning).
- The system does not support multi-event templates or recurring event generation.

---

## 6. SIGNIFICANCE OF THE STUDY

- **To the Events Management Department:** A centralized tool that replaces error-prone manual workflows, providing real-time visibility into event readiness and task progress.
- **To the proponents:** Demonstrates the integration of multiple technologies (React, Laravel, MySQL) into a cohesive responsive web application that satisfies real-world departmental needs.
- **To future researchers and students:** Provides a reference for building academic capstone projects that comply with strict no-external-API constraints while still delivering modern, polished user experiences.
- **To New Era University:** Establishes a precedent for student-developed internal tools that can be donated to departments after defense, contributing to institutional capability.

---

## 7. TECHNICAL STACK

### Frontend

| Component | Technology | Version | Rationale |
|---|---|---|---|
| Framework | React.js | 19 | Component-based architecture suitable for dynamic dashboards; widely taught in BSIT curriculum |
| Build tool | Vite | 8 | Fast development server with Hot Module Replacement (HMR); modern replacement for older bundlers |
| Styling | Tailwind CSS | 4 | Utility-first CSS framework; ensures responsive design without writing custom CSS files |
| Routing | React Router | 7 | Standard library for client-side routing in single-page applications |
| HTTP client | axios | latest | Cleaner API than fetch(), supports request/response interceptors |
| Calendar UI | FullCalendar (React adapter) | 6 | UI component library for the Calendly-style monthly calendar view |
| Charting | Recharts | latest | UI component library for the donut chart in the analytics dashboard |

### Backend

| Component | Technology | Version | Rationale |
|---|---|---|---|
| Framework | Laravel | 13 | PHP framework; provides ORM, routing, validation, authentication out-of-the-box |
| Language | PHP | 8.3 | Stable PHP version required by Laravel 13 |
| Authentication | Laravel Sanctum | 4 | Lightweight token-based API authentication suitable for SPAs |
| PDF Generation | barryvdh/laravel-dompdf | 3.1 | Composer package (NOT a third-party service); generates PDFs locally on the server |

### Database

| Component | Technology | Version | Rationale |
|---|---|---|---|
| Database | MySQL | 8.4 | Relational database for structured event/task/user data |
| ORM | Laravel Eloquent | (built-in) | Active Record-style ORM included with Laravel |

### Local Server / Hosting

| Component | Technology | Rationale |
|---|---|---|
| Local server | Laragon | Bundles Apache, PHP, MySQL into a single Windows installer; recommended over XAMPP for cleaner Laravel support |
| Network | LAN (Local Area Network) | Per professor's requirement; no external cloud or paid hosting |

### Important Compliance Notes

- **No external service APIs are used.** All data and logic are handled by the self-built Laravel REST API.
- **No machine learning models or AI APIs are integrated.** The classification logic is implemented as plain PHP if/elif statements (about 30 lines of code).
- **UI component libraries** (FullCalendar, Recharts, Tailwind) are NOT APIs — they are visual rendering tools with no external server calls. The professor's "no third-party APIs" rule applies to service APIs (e.g., OpenAI, Google Maps, Firebase), not to UI libraries.
- All hosting is local; no Vercel, Render, Railway, or Netlify deployments.

---

## 8. SYSTEM ARCHITECTURE

The system follows a **three-tier architecture** with clear separation of concerns:

1. **Presentation Tier (Frontend):** React Single-Page Application running in the user's browser, connected via WiFi to the server laptop.
2. **Application Tier (Backend):** Laravel REST API running on the server laptop, exposing JSON endpoints under `/api/*`.
3. **Data Tier (Database):** MySQL database running on the same server laptop via Laragon.

**Communication flow:**
- Browser sends HTTPS (or HTTP for LAN) requests to the Laravel API on port 8000.
- Laravel validates the request, applies role-based authorization, performs business logic, and queries MySQL.
- Laravel returns JSON responses, which the React frontend renders.
- Authentication uses bearer tokens issued by Sanctum and stored in the browser's `localStorage`.

**LAN access pattern:**
- One laptop runs Laragon (MySQL) + Laravel backend + Vite development server.
- All staff devices (phones, tablets, laptops) connect to the same WiFi network or hotspot.
- Staff access the application by typing the server laptop's local IP address in their browser (e.g., `http://192.168.1.5:5173`).
- The Vite development server proxies API requests to the Laravel backend, allowing single-origin access from any device.

---

## 9. DATABASE DESIGN

The system uses **six (6) tables** in a normalized relational schema:

### `users` table
Stores account information for all three roles.
- `id` (primary key)
- `name` (string)
- `email` (string, unique)
- `password` (string, hashed using bcrypt)
- `role` (enum: 'admin', 'officer', 'staff')
- `is_active` (boolean, default true)
- `email_verified_at`, `remember_token`, `timestamps`

### `events` table
Stores all events created in the system.
- `id` (primary key)
- `name` (string)
- `description` (text, nullable)
- `venue` (string)
- `event_date` (date)
- `event_time` (time)
- `budget` (decimal, nullable)
- `status` (enum: 'upcoming', 'completed', default 'upcoming')
- `created_by` (foreign key to users.id)
- `timestamps`

### `tasks` table
Stores tasks belonging to events.
- `id` (primary key)
- `event_id` (foreign key to events.id, cascade on delete)
- `name` (string)
- `description` (text, nullable)
- `due_date` (date)
- `status` (enum: 'pending', 'in_progress', 'done', default 'pending')
- `priority` (enum: 'low', 'medium', 'high', default 'medium')
- `assigned_to` (foreign key to users.id, nullable, null on delete)
- `timestamps`

### `event_staff` table (pivot)
Pivot table for event-level staff assignment (separate from task-level assignment).
- `id` (primary key)
- `event_id` (foreign key to events.id, cascade on delete)
- `user_id` (foreign key to users.id, cascade on delete)
- Unique constraint on (event_id, user_id)

### `documents` table
Stores metadata for files uploaded against events.
- `id` (primary key)
- `event_id` (foreign key to events.id, cascade on delete)
- `uploaded_by` (foreign key to users.id, cascade on delete)
- `file_name` (string, original filename)
- `file_path` (string, server storage path)
- `file_size` (unsigned big integer, in bytes)
- `mime_type` (string)
- `timestamps`

### `settings` table
Stores configurable system settings (e.g., school year boundaries).
- `id` (primary key)
- `key` (string, unique)
- `value` (text, nullable)
- `timestamps`

---

## 10. THE FOUR CORE FEATURES

### Feature 1: Event Planning and Scheduling Module

**Description:** Allows authorized users (Administrator and Officer) to create, edit, and delete events. Events are displayed in a Calendly-inspired monthly calendar grid OR an alternative list view (user toggleable).

**Capabilities:**
- Create events with name, description, venue, date, time, and budget
- View events in either calendar or list format (toggle button)
- On mobile devices (screen width < 768px), the list view is the default
- Each event displayed as a colored pill on the calendar — the color matches the event's readiness classification
- Clicking an event opens a detail drawer showing tasks, assigned staff, documents, and metadata
- Staff members can be assigned to events at the event level (independent of task-level assignment)

**Key files:** `EventController.php`, `EventsPage.jsx`, `EventCalendarView.jsx`, `EventListView.jsx`

### Feature 2: Task and Staff Assignment Tracking

**Description:** Provides full lifecycle management of tasks within an event, including assignment to specific staff members and real-time status tracking.

**Capabilities:**
- Add tasks under each event with name, description, due date, priority, and assigned staff
- Track task completion status across three states: Pending, In Progress, Done
- Administrators and Officers can create, edit, and delete tasks
- Staff members can update the status of tasks assigned to them (not others)
- A dedicated "My Tasks" page for Staff lists all tasks assigned to them across all events
- Tasks have priority levels (Low, Medium, High) displayed with color coding
- **Historical record protection:** once an event transitions to Completed status, its tasks become locked. Staff and Officers can no longer modify them. Only an Administrator may correct historical task records, and any such change requires explicit on-screen confirmation. This safeguards the integrity of post-event reports.

**Key files:** `TaskController.php`, `TaskRow.jsx`, `TaskFormDialog.jsx`, `StaffTasksPage.jsx`

### Feature 3: Rule-Based Event Readiness Classification (AI-Assisted)

**Description:** The system automatically classifies each event into one of four readiness levels using hardcoded rule-based logic. **This is the "AI" feature required by the curriculum — implemented without machine learning, external APIs, or data training.**

**Classification levels:**
- **GREEN — On Track:** tasks completed ≥ 70% AND days remaining ≥ 7 AND at least 1 staff assigned per task
- **YELLOW — At Risk:** tasks completed between 40% and 69%, OR days remaining between 3-6 days, OR some tasks have no staff assigned
- **RED — Critical:** tasks completed < 40%, OR days remaining ≤ 2 days, OR majority of tasks have no assigned staff
- **COMPLETED:** event date has passed (assigned automatically when an upcoming event's date is in the past)

**Classification priority order (always evaluated in this sequence):**
1. Check RED conditions first → if any RED condition is true, classify as RED
2. Check YELLOW conditions second → if any YELLOW condition is true, classify as YELLOW
3. Only assign GREEN if neither RED nor YELLOW conditions are triggered
4. **Edge case:** if an event has zero tasks, classify as YELLOW (not enough data to be GREEN, not critical enough to be RED)

**Analytics Dashboard (accessible to Administrator and Officer):**
- Overview stat cards: Total Events, Total Tasks, Tasks Done with percentage, Active Staff
- Readiness Distribution Donut Chart showing the breakdown of all events by readiness category
- Top 5 Most Urgent Events list (sorted by event date ascending)
- Period filter: This Week / This Month / All Time

**Implementation note:** Classification is computed on the backend in PHP (`App\Services\EventClassifier`). The frontend only renders the value returned by the API — no classification logic exists in React.

**Key files:** `EventClassifier.php` (the classifier service), `AnalyticsController.php`, `AnalyticsPage.jsx`, `ReadinessDonut.jsx`

### Feature 4: Event Progress Reports and Document Management

**Description:** Allows generation of post-event PDF summary reports and management of attached documents per event.

**PDF Report Capabilities:**
- Any authenticated user can generate a PDF report for any event
- Reports include: event details, readiness badge, task summary statistics, assigned staff list, full task table with status badges, attached documents list, and a generation timestamp
- Reports are styled with EMD branding (NEU logo, brand colors, formatted typography)
- Generated locally on the server using `barryvdh/laravel-dompdf` (a Composer package, NOT an external API)

**Document Management Capabilities:**
- Officers and Administrators can upload documents to any event
- Supported file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG
- Maximum file size: 10 MB per file
- Validation occurs on both frontend (file picker) and backend (Laravel validation rules)
- Files are stored in Laravel's `storage/app/public` directory and accessed via `storage:link` symlink
- Any authenticated user can download documents
- Only Officers and Administrators can delete documents

**Key files:** `ReportController.php`, `DocumentController.php`, `event-report.blade.php` (PDF template)

---

## 11. USER ROLES AND PERMISSIONS

### Role 1: Administrator
- Full system access
- Can manage user accounts (create, edit, deactivate, reactivate)
- Can create, edit, and delete events
- Can assign tasks and event-level staff
- Can view all analytics and generate reports
- Can upload and delete documents
- Can change their own password

### Role 2: Officer
- Can create and manage events (but not delete them)
- Can assign tasks to staff members
- Can monitor event readiness classification
- Can view analytics and the readiness distribution chart
- Can upload and delete documents
- Can change their own password
- **Cannot** manage user accounts (Administrator-only)
- **Cannot** delete events (Administrator-only)

### Role 3: Staff
- Can view their own assigned tasks and events
- Can update the status of tasks assigned to them (Pending → In Progress → Done)
- Has read-only access to event details
- Can download documents
- Can download PDF reports
- Can change their own password
- **Cannot** create events or assign other staff
- **Cannot** access the user management or analytics pages

---

## 12. SECURITY AND ACCESS CONTROL

### Authentication
- Token-based authentication using Laravel Sanctum
- Passwords are hashed using bcrypt (cost factor 12, set via the `BCRYPT_ROUNDS` environment variable)
- Tokens are stored in the browser's `localStorage`
- Tokens have a 30-day expiration

### Authorization
- Role-based access control (RBAC) is enforced at two levels:
  1. **Backend middleware** (`role:admin,officer,staff`) checks the user's role before executing controller methods
  2. **Frontend route guards** (`ProtectedRoute`) prevent unauthorized users from accessing role-restricted pages
- This double-gating ensures that a malicious user cannot bypass the UI to access forbidden endpoints

### Input Validation
- All input is validated on both frontend (form-level) and backend (Laravel validation rules)
- SQL injection is prevented by using parameterized queries via Eloquent ORM
- XSS attacks are mitigated by React's default text escaping and by validating uploaded file types
- File uploads are restricted by type (MIME type whitelist) and size (10 MB cap)

### Note on localStorage
- The storage of authentication tokens in `localStorage` is acceptable for this project because the application runs on a trusted school LAN with approximately ten known staff members. XSS attack risks are not a realistic concern in this controlled environment.

### Historical Record Protection (Data Integrity)
- When an event's scheduled date passes, the system automatically transitions it to "Completed" status.
- Once an event is Completed, all of its tasks become locked: Staff and Officer roles can no longer change task status, edit, delete, or add tasks to that event.
- Only an Administrator may modify a completed event's task records. This is intended as a "break glass" mechanism for correcting genuine mistakes — not for routine editing.
- Any status change an Administrator makes to a completed event's task requires explicit on-screen confirmation, preventing accidental or silent alteration of historical data.
- This protection ensures that once an event has concluded, the record of who did what — used by post-event reports — cannot be casually tampered with. It is enforced on the backend (HTTP 403 responses) so it cannot be bypassed through the user interface.

---

## 13. METHODOLOGY

The system was developed using an **iterative, phase-based approach** beginning with environment setup (Phase 0), followed by eight (8) development phases (Phases 1–8):

1. **Phase 0 — Environment Setup:** Installation of Laragon, PHP 8.3, MySQL 8.4, Node.js, Composer, Vite, Laravel, React, Tailwind, Sanctum, and axios. Verification of full-stack connectivity via a `/api/ping` endpoint.
2. **Phase 1 — Database Schema and Migrations:** Design and implementation of six tables (users, events, tasks, event_staff, documents, settings) with appropriate foreign keys, indexes, and cascade rules. Creation of Eloquent models with relationships.
3. **Phase 2 — Authentication and Role-Based Access:** Implementation of Sanctum-based authentication, role middleware, protected routes, and the login page.
4. **Phase 3 — Dashboards and Layout Shell:** Construction of the responsive application shell (sidebar + top bar + bottom navigation) and role-specific dashboard home pages.
5. **Phase 4 — Event Planning Module (Feature 1):** Implementation of event CRUD endpoints, the Calendly-style calendar view using FullCalendar, the list view, and the event detail drawer.
6. **Phase 5 — Task and Staff Assignment (Feature 2):** Implementation of task CRUD endpoints, the staff "My Tasks" page, and inline task status updates with live readiness recalculation.
7. **Phase 6 — Rule-Based Classification and Analytics (Feature 3):** Refactoring of the classifier into a dedicated service class and implementation of the analytics page with the readiness distribution donut chart.
8. **Phase 7 — Reports and Document Management (Feature 4):** Integration of `barryvdh/laravel-dompdf`, the PDF report template, and the document upload/download/delete UI.
9. **Phase 8 — Polish, Responsiveness, and LAN Demo Preparation:** Implementation of toast notifications, LAN configuration, demo data seeding, documentation, and final responsive QA.

Each phase was validated before moving to the next, ensuring stable foundations.

---

## 14. TESTING APPROACH

### Unit-level Testing
- Backend API endpoints were tested via `Invoke-RestMethod` (PowerShell) and Laravel's built-in route inspection.
- Authentication flow, role gating, and validation rules were verified for each of the three roles.

### Integration Testing
- Full-stack flows (login → fetch events → mark task done → observe readiness change) were tested manually in the browser.
- Edge cases were tested: zero-task events, past events, events with all tasks unassigned.

### User Interface Testing
- Responsive behavior was tested on desktop (1366×768 and higher), tablet, and mobile (Chrome DevTools mobile emulation and a physical device).
- Browser compatibility was verified on Chrome and Edge.

### Acceptance Testing (planned for Capstone 2)
- User Acceptance Testing (UAT) sessions are planned at the EMD office with actual department staff.
- Feedback will be collected and used to refine the application before final donation/handover.

---

## 15. DEPLOYMENT MODEL

The system follows a **single-server LAN deployment model**:

- One designated laptop runs Laragon (Apache + MySQL), the Laravel backend (via `php artisan serve` on port 8000), and the Vite development server (on port 5173).
- The server laptop connects to the EMD's local network (school WiFi or mobile hotspot).
- All staff devices (phones, tablets, laptops) connect to the same network.
- Staff access the application by typing the server laptop's local IP address in their browser.
- No internet connection is required after initial setup — the system functions entirely offline on the local network.
- A startup script (`start.bat`) automates the launch of both servers with a single click.

For long-term deployment after Capstone 2, the team plans to either donate a dedicated server laptop to the EMD or install the system on an existing departmental computer.

---

## 16. KEY TECHNICAL TERMS AND CONCEPTS

For the paper's glossary or terminology section:

- **REST API:** Representational State Transfer Application Programming Interface — a standardized way for software systems to communicate over HTTP using JSON.
- **CRUD:** Create, Read, Update, Delete — the four basic operations for persistent data.
- **JWT / Bearer Token:** A string that proves the holder's identity when included in HTTP request headers.
- **ORM (Object-Relational Mapping):** A programming technique that lets developers interact with a relational database using objects instead of raw SQL queries.
- **SPA (Single-Page Application):** A web application that loads a single HTML page and dynamically updates content as the user interacts with it — typical of React apps.
- **Middleware:** Code that runs between an incoming request and its controller, used for cross-cutting concerns like authentication and role checking.
- **Eloquent:** Laravel's ORM, which represents database tables as PHP classes and rows as PHP objects.
- **Sanctum:** Laravel's first-party authentication system for SPAs and mobile apps using tokens.
- **Migration:** A versioned PHP file that describes how to create or modify a database table.
- **Seeder:** A script that populates the database with initial data for testing or demonstration.
- **Hot Module Replacement (HMR):** A development feature where code changes appear in the browser without a full page reload.
- **Tailwind CSS:** A utility-first CSS framework where styling is applied via class names (e.g., `bg-emerald-100`) rather than custom CSS files.

---

## 17. ANTICIPATED PANEL QUESTIONS (AND HONEST ANSWERS)

These are likely questions and the talking points to prepare for them.

**Q: How does the "AI" feature work? Where is it?**
A: It is a rule-based classifier implemented in `App\Services\EventClassifier.php`, approximately 30 lines of PHP. It evaluates three metrics (completion percentage, days remaining, task assignment status) against priority-ordered rules to assign one of four readiness categories. No machine learning, no external APIs, no training data — strictly hardcoded if/elif logic, as required by the curriculum.

**Q: Why React and Laravel? Why not just one framework?**
A: Separation of concerns. React provides a responsive, component-based UI that runs in the browser. Laravel handles business logic, database access, validation, and security. The frontend never touches the database directly — all data flows through the API, which provides a single point for enforcing security and validation.

**Q: What happens if the WiFi disconnects?**
A: The system requires connection to the server laptop's network. If a user's device disconnects, they cannot access the app until reconnected. The data on the server is preserved. We considered offline-first design but it was outside the scope of Capstone 1 and not required by the briefing.

**Q: Is the system scalable to thousands of users?**
A: The system was designed for the EMD's actual scale of approximately ten staff. The architecture would scale to perhaps a few hundred concurrent users on the same laptop, but beyond that it would require deployment to a server. Scalability beyond the EMD's needs was not a project goal.

**Q: What about security? Isn't localStorage vulnerable to XSS?**
A: Yes, in theory localStorage is vulnerable to XSS attacks. We chose this approach because the application runs on a trusted internal LAN with approximately ten known users. XSS risks are minimal in this controlled environment. For production internet deployment, we would use httpOnly cookies instead.

**Q: What stops a staff member from faking task completion or tampering with records after an event?**
A: Two safeguards. First, staff can only update the status of tasks assigned to them — not other people's tasks. Second, once an event's date passes and it becomes "Completed," all its task records are locked: staff and officers can no longer modify them. Only an Administrator can correct historical records, and the system requires explicit confirmation for any such change. This protection is enforced on the backend, so it cannot be bypassed through the interface. A full Audit Trail (logging every change with timestamp and user) is planned as a future enhancement for complete accountability.

**Q: What if the EMD wants more features after the project is done?**
A: The codebase is organized for maintainability and is documented. Future enhancements mentioned during the project include an Audit Trail / Activity Log, email notifications, and a configurable school year filter. The team will continue to support and improve the system through Capstone 2.

---

## 18. PROJECT METRICS

For the paper's results/data section:

- **Lines of code (approximate):** Backend ~2,500 lines PHP; Frontend ~3,500 lines JSX/JS; Templates and configuration ~500 lines.
- **Number of database tables:** 6
- **Number of API endpoints:** approximately 30 (36 route registrations in `routes/api.php`, since update routes accept both PUT and PATCH)
- **Number of frontend components:** 17 reusable components + 10 page-level files (8 functional pages + 2 placeholder pages)
- **Number of build phases:** 8
- **Number of features (per briefing):** Exactly 4
- **Number of user roles:** 3
- **Demo seed data:** 10 users, 5 events, 18 tasks, 1 document, 3 settings entries

---

## 19. HOW TO USE THIS DOCUMENT WITH AN AI ASSISTANT

When writing a section of the capstone paper, paste **only the relevant sections** of this document into your AI assistant along with a prompt like:

> "I am writing Chapter 1 (Background and Introduction) of my capstone paper. Here is the project information. Help me write a 2-3 paragraph Background of the Study section in formal academic tone."
>
> *(then paste sections 1, 2, 3, 4, 5, 6)*

Or:

> "I am writing Chapter 3 (Methodology and System Design). Help me write a System Architecture section. Here is the technical information."
>
> *(then paste sections 7, 8, 9, 13)*

Or for the Algorithm section:

> "Help me write the algorithm explanation section for the readiness classifier."
>
> *(then paste section 10's Feature 3 description)*

**Important:** Always tell the AI to use formal academic English, third-person voice, and to cite sources where appropriate. Review the AI's output critically — it can sometimes invent facts. Verify all technical claims against this document.

---

*End of reference document. For questions about specific implementation details not covered here, consult the `OVERVIEW.md` (architectural reference) or the codebase directly.*
