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

### Source Code: Key Modules

**File:** [appendices/source-code.md](appendices/source-code.md)

This listing presents the key code of EMO Tracker's seven modules, copied from the repository with each file's name and line numbers. Each module starts with a short explanation and the list of files it is made of.

| Module | Key code shown |
|---|---|
| 1. Event Planning and Scheduling | How an event's start, end and status are worked out, how finished events are completed automatically, and how events are added and edited |
| 2. Task Assignment and Tracking | Adding a task, and the status update with its ownership check and completed-event lock |
| 3. Rule-Based Readiness Classification | The whole `EventClassifier` class: the rules checked in order and the reason shown with each label |
| 4. Reports and Document Management | The document upload with its file checks, the private download, and the PDF report |
| 5. Schedule Management | The Schedule for a year, the Excel export, and merging duplicate venues |
| 6. Venue Double-Booking Warning | The whole `VenueClashes` class: same venue and room, overlapping days and hours |
| 7. Data Protection and Migration | The daily backup that runs after the response, the .zip backup of the database and documents, and the cleanup that keeps the newest 14 |

It ends with the supporting code that every module relies on: the login request with its attempt limit, and the role check on every protected request.

### Modules 1 to 7

The seven modules correspond to the system's seven features:

| Module | What it does for the EMO | Who uses it |
|---|---|---|
| 1. Event Planning and Scheduling | Keeps every event and venue booking, with its type (internal or external), its status (Upcoming, Ongoing, Completed or Cancelled) and a "Rescheduled from" note when it moves; shown as a calendar and a list | Everyone views; the Administrator adds and edits |
| 2. Task Assignment and Tracking | Breaks each prepared event into tasks with one owner, a due date and a priority; every user has a My tasks page | Administrator and Officers manage; owners update their status |
| 3. Rule-Based Readiness Classification | Labels each prepared event On Track, At Risk or Critical with a reason, and summarizes the schedule and preparation on the Analytics page | Everyone sees labels; Administrator and Officers use Analytics |
| 4. Reports and Document Management | Stores event documents privately and generates a PDF report for each event | Administrator and Officers upload; everyone downloads; only the Administrator deletes |
| 5. Schedule Management | Shows each year's bookings laid out like the EMO's spreadsheet, colored by building, with a managed list of venues and buildings and an Excel export | Everyone views; the Administrator manages venues; Administrator and Officers export |
| 6. Venue Double-Booking Warning | Warns when an event overlaps another booking at the same venue and time, in the form, the event panel and the Schedule | The Administrator sees it while booking; everyone sees the labels |
| 7. Data Protection and Migration | Backs up the database and documents daily, restores them when needed, and imported the EMO's existing spreadsheet once | Automatic; the Administrator sees the backup status |

Use this table to introduce the source code listing, or rewrite each row as a short paragraph with the matching screenshots.

### Database Schema (SQL Script)

**File:** [appendices/database-schema.sql](appendices/database-schema.sql)

The SQL script contains the structure of all 16 tables of the EMO Tracker database (MySQL 8.4), without any data.
- **Application tables:** `users`, `buildings`, `venues`, `events`, `tasks`, `documents` and `settings`, in the order they depend on each other.
- **Laravel's own tables:** for login tokens, sessions and housekeeping.
- **What it defines:** every column with its data type and default, the primary keys, the indexes, and the foreign keys with their delete rules. For example:
  - deleting an event also deletes its tasks and documents
  - deleting a venue leaves its events without a venue

The script was exported from the database that the system's migrations build. It was tested by loading it into an empty database and comparing the result with the original: every column and foreign key matched. In practice the system creates its database with `php artisan migrate`; this script documents the same structure in SQL.

### API Endpoint Documentation

**File:** [tables/api-endpoints.html](tables/api-endpoints.html)

The API endpoint table documents all 42 endpoints of the EMO Tracker REST API (45 route registrations, since four endpoints accept both PUT and PATCH). For each endpoint it gives the HTTP method, the path, who can use it and what it does.
- **Groups:** login and account, Home pages, events and Schedule, venues and buildings, tasks, documents and reports, analytics, and accounts.
- **Shared rules:**
  - requests and responses use JSON
  - every endpoint except login and the server check needs the login token
  - a missing or expired token returns 401, a role that is not allowed returns 403, and invalid input returns 422 with the reason

The table was generated from the system's own route list and checked against it, so no endpoint is missing or invented. It shows how the rules of the use case diagram are enforced: changes to the schedule, venues and accounts are limited to the Administrator, while task and document management is open to Officers too.

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
