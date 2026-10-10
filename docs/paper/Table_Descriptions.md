# Table Descriptions

Descriptions of the tables and listings for the EMO Tracker Capstone 2 paper, organized like the Capstone 1 paper. Part 1 has the tables for **Chapter 3**, and Part 2 has the tables and listings for the **appendices**.

- **Where the tables are:** in the [`tables/`](tables/) folder, as web pages that paste into Word or Google Docs as editable tables (open the file in a browser, select the table, copy, paste). The database schema and the source code are in the [`appendices/`](appendices/) folder.
- **Accuracy:** each description was checked against its table and against the system as of October 10, 2026.
- **Numbering:** Chapter 3 tables are numbered 3.1 and 3.2; renumber them if other tables come first. Appendix tables are numbered by their appendix letter, as in Capstone 1.
- **Figure descriptions** are in [Diagram_Descriptions.md](Diagram_Descriptions.md), and screenshot descriptions in [Screenshot_Descriptions.md](Screenshot_Descriptions.md).

---

# Part 1. Chapter 3 tables

### Table 3.1. Software and Hardware Compatibility

**File:** [tables/compatibility-table.html](tables/compatibility-table.html)

The compatibility table lists the 26 technologies EMO Tracker needs, with the version the project used, whether each is software or hardware, and its role in the system.

- **Backend software:** Laravel 13.34 on PHP 8.3 or newer, Laravel Sanctum 4.3 for token-based login, dompdf 3.1 for PDF reports, PhpSpreadsheet 5.10 for the spreadsheet import and the Excel export, and MySQL 8.4 as the database.
- **Frontend software:** React 19.2, React Router 7.18, Vite 8.3, Tailwind CSS 4.3, axios 1.20, FullCalendar 6.1 and Recharts 3.8. FullCalendar and Recharts are user-interface libraries that run inside the system, not outside services.
- **Fonts:** Apple devices use their own San Francisco font, and other devices use Inter 5.3, its closest free match, bundled with the system so that it works without internet.
- **Tools and platforms:** Composer, Node.js 20 and npm; Laragon on the EMO's Windows 10 or 11 laptop; Homebrew on the Mac used for development and the defense; and a web browser (tested in Google Chrome).
- **Hardware:**
  - a server laptop that must stay on while the office uses the system
  - a wireless router or hotspot for the local network (no internet access needed)
  - a USB flash drive or external drive for the daily backups
  - client devices (laptops, desktops, tablets and phones)

The table shows that the system is built entirely from free, locally installed software, and runs on the EMO's existing laptop and network.

### Table 3.2. Risk Assessment and Analysis

**File:** [tables/risk-assessment.html](tables/risk-assessment.html)

The risk assessment identifies 16 risks (R1 to R16) to the system's operation and to the project.
- **What each risk has:** a category (technical, data, security, operational or project), its likelihood and impact, a risk level, and a mitigation strategy.
- **How the level is set:** from a standard likelihood × impact matrix, rated before mitigation.

**Results:** 3 risks are rated High, 9 Medium and 4 Low.

- **The three high risks all come from running on one laptop and one network:**
  - **R1, server laptop failure or downtime:** keep the laptop plugged in during office hours, so its battery acts as a built-in UPS; start the system with a double-click; and follow the documented setup steps to move it to another laptop, restoring the latest backup.
  - **R2, data loss:** automatic daily backups of the database and all documents. The newest 14 are kept, plus named snapshots, and they can be stored on a USB drive. The Administrator's Home shows the last backup or a warning if backups fail; a backup is saved before every database update; and a restore command brings data back.
  - **R3, network unavailability:** a dedicated router or hotspot; only a local network is needed, not the internet; the server laptop itself can still use the system; and when a device cannot connect, the system says so and offers to try again, with no data lost.
- **Medium risks:** these include:
  - unauthorized access and an Administrator account compromise (R5, R6)
  - double-booking of venues (R7), addressed by the overlap warning
  - user resistance to leaving the spreadsheet (R8)
  - incorrect data entry (R10)
  - errors when migrating the spreadsheet (R11)
  - a code update running on an old database (R13)
  - scope changes from feedback (R16)
- **Low risks:**
  - forgotten passwords (R9)
  - accidental deletion (R12)
  - two users editing the same record at the same time (R14)
  - exposure of real data through the public code repository (R15)

The assessment shows that every risk has a mitigation built into the system or its procedures, and that the remaining high risks are the ones every locally hosted system shares.

---

# Part 2. Appendix tables and listings

In the order of the Capstone 1 appendices.

### Source Code (Key Modules)

**Files:** [appendices/source-code.html](appendices/source-code.html) (copy-ready for Google Docs or Word) and [appendices/source-code.md](appendices/source-code.md) (the same content, readable on GitHub)

This listing follows the layout of Appendix D in the Capstone 1 paper.
- **Summary table:** each module's number, name and primary files.
- **Each code block:** a title bar with the module and file, the code (copied from the repository with its file name and line numbers), and an explanation.

| # | Module | Key code shown |
|---|---|---|
| 1 | Login and Authentication | The login with its password check and 30-day token; the login attempt limit and the rejection of deactivated accounts' tokens; changing one's own password; the frontend login and token handling |
| 2 | Event Planning and Scheduling | Adding and editing events, including the reschedule mark; the status lifecycle (upcoming, ongoing, completed) and the automatic completion of finished events |
| 3 | Task Assignment and Tracking | Adding tasks with one active owner; the status update with its ownership check and completed-event lock |
| 4 | Rule-Based Event Readiness Classification | The classifier's rules, checked in order; the Analytics statistics by period |
| 5 | Reports and Document Management | The document upload with its file checks and private storage; the protected download; the PDF report |
| 6 | User Account Management | Creating, editing and deactivating accounts, with the safeguard that keeps an administrator; signing an account out everywhere |
| 7 | Role-Based Access Control | The role-checking middleware; the route groups for each role; the frontend route guard |
| 8 | Schedule and Venue Management | One year of the Schedule and its Excel export; merging duplicate venues |
| 9 | Venue Double-Booking Warning | The rules that decide whether two bookings clash; the check the event form calls while it is filled in |
| 10 | Data Protection and Migration | The daily backup after the response; the .zip backup of the database and documents; keeping the newest 14 |

The modules are those of the current system. Modules 1 to 7 keep the order of the Capstone 1 appendix, all with their current code. For example, the login token is now named `emo-tracker`, the login is rate-limited, and deactivated accounts lose their sessions at once. Module 3 is renamed "Task Assignment and Tracking", because any office member, not only Staff, can now own a task. Modules 8 to 10 cover the parts added in Capstone 2.

### Database Schema / SQL Scripts

**Files:** [appendices/database-schema.html](appendices/database-schema.html) (copy-ready, in the Capstone 1 layout) and [appendices/database-schema.sql](appendices/database-schema.sql) (the full script)

This appendix gives the SQL that creates the EMO Tracker database (MySQL 8.4), in the Capstone 1 layout: a title bar for each table, then its CREATE TABLE statement.
- **Tables shown:** the seven application tables (`users`, `buildings`, `venues`, `events`, `tasks`, `documents`, `settings`) and `personal_access_tokens`, which holds the login tokens. They appear in the order they depend on each other.
- **The full script** also creates Laravel's housekeeping tables (sessions, cache, jobs and the migrations list), 16 tables in all.
- **What each statement defines:** every column with its data type and default, the primary key, the indexes, and the foreign keys with their delete rules. For example, deleting an event also deletes its tasks and documents, and deleting a venue leaves its events without a venue.
- **Changes since Capstone 1:** the `event_staff` table was removed (an event's people are now its task owners), the event budget was removed, and the `buildings` and `venues` tables were added.

The script was exported from the database that the system's migrations build. It was tested by building an empty database from it and comparing the result with the original: every column (including its collation), index and foreign key matched. In practice the system creates its database with `php artisan migrate`; this script documents the same structure in SQL.

### API Endpoint Documentation

**File:** [tables/api-endpoints.html](tables/api-endpoints.html)

The API endpoint documentation describes all 42 endpoints of the EMO Tracker REST API (45 route registrations, since four endpoints accept both PUT and PATCH), in the Capstone 1 layout.
- **Columns:** the endpoint, its HTTP method, whether it needs a login and for which roles, its parameters (with type and whether each is required), the success response, and the error codes with their meaning.
- **Groups:** authentication, Home pages, events, the Schedule, venues and buildings, tasks, documents and reports, analytics, and accounts.
- **Shared rules:**
  - every path starts with `/api`
  - requests and responses use JSON, except file downloads
  - every endpoint except login and the server check needs the login token
  - 401 means a missing, expired or revoked token; 403 means the user's role may not use the endpoint; 404 means the item no longer exists; and 422 responses give the reason for each invalid field

The endpoints were checked against the system's own route list, so none is missing or invented, and each parameter, response and error was read from the code. The table also shows how the rules of the use case diagram are enforced: changes to the schedule, venues and accounts are limited to the Administrator, while task and document management is open to Officers too.

### Data Dictionary

**File:** [tables/data-dictionary.html](tables/data-dictionary.html)

The data dictionary describes every column of the seven application tables: 64 columns in all. Each entry gives the field name, data type, constraints and meaning.

| Table | Columns | What it stores |
|---|---|---|
| users | 10 | User accounts, roles and login data |
| events | 20 | Every event and venue booking on the schedule, whether or not the EMO prepares it |
| tasks | 10 | The tasks that prepare an event, with owner, due date, priority and status |
| buildings | 5 | The building groups that color the Schedule |
| venues | 5 | The places that can be booked, each optionally in a building |
| documents | 9 | Files attached to events, stored privately |
| settings | 5 | Key and value pairs reserved for system settings, not yet used |

The constraints column shows which fields are required, unique or optional, and the foreign keys with what happens when the referenced record is deleted. It complements the ERD (Figure 3.6), which shows the same tables and their relationships as a diagram.

### User Journey Map (Tables)

**File:** [tables/user-journey-map.html](tables/user-journey-map.html)

These tables hold the same content as the four visual user journey maps, in a form that can be edited in Word:
- **The maps:** the EMO's current process (As-Is), and the Administrator, Officer and Staff journeys with EMO Tracker (To-Be).
- **Columns:** each stage, with its actions, touchpoint, thoughts, feeling (with a score from 1, very negative, to 5, very positive), pain point and opportunity.
- **Last column of the As-Is table:** how EMO Tracker addresses each pain point.

Use the visual maps as figures and these tables when an editable version is needed. Their descriptions are under "User journey maps" in [Diagram_Descriptions.md](Diagram_Descriptions.md).
