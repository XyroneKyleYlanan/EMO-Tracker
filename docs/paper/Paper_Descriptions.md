# Descriptions for the Paper

Each item matches the paper's figure and table numbers. A copy-ready version with a Copy button per item is [Paper_Descriptions.html](Paper_Descriptions.html) (open it in a browser). The source code explanations (Modules 1 to 10) are in [appendices/source-code.html](appendices/source-code.html). Checked against the system on October 10, 2026.

---

## Capstone Paper (Chapter 3)

### Figure 3.1 - Prototyping Model Diagram

Figure 3.1 presents the Prototyping Model, the software development life cycle used to develop EMO Tracker. In this model, a working version of the system is built early, evaluated with the client, and refined in repeated cycles until it meets the users' needs. The process begins with requirements gathering and analysis, followed by a quick design and the construction of a prototype. The prototype is then evaluated against the needs of the Events Management Office (EMO). If it does not yet meet them, the feedback leads to a refinement of the prototype and another round of design and building; once it does, the system proceeds to implementation and maintenance.

In Capstone 1, the team proposed the title and gathered the requirements from February to March 2026, designed the database and user interface from March to April, and built the first prototype from April to May, which was evaluated at the pre-oral defense in May 2026. In Capstone 2, the prototype went through further rounds of evaluation and refinement. Consultations with the EMO on October 2 and 6, 2026, together with an analysis of the office's 2026 schedule spreadsheet, led to the Schedule page, the venue list, the spreadsheet import, automatic backups, event statuses, event types and the double-booking warning, and a pre-defense audit on October 7, 2026 led to further fixes and interface improvements. The system is then installed on the EMO's laptop, piloted and evaluated by the office in October 2026, before the Capstone 2 defense in November 2026. The model suited the project because the EMO's needs became clear as its members saw working versions of the system.

### Figure 3.2 - Entity Relationship Diagram

Figure 3.2 shows the entity relationship diagram (ERD) of the EMO Tracker database in crow's foot notation. The EVENTS entity is at the center of the design. It stores each event's name, type (internal or external), department, venue and room, dates and times, the original date and time when the event was rescheduled, control number, remarks, whether the EMO prepares the event, and its stored status (upcoming, completed or cancelled).

Each event may be held at one venue from the managed list, and each venue may belong to one building, whose color is used on the Schedule. Both links are optional, so an event can name a typed place instead, and a venue can stand outside any building. An event has zero or more TASKS and zero or more DOCUMENTS, each of which belongs to exactly one event. USERS relate to four entities: a user creates events, may own tasks (a task has at most one owner), uploads documents, and signs in with login tokens stored in PERSONAL_ACCESS_TOKENS. SETTINGS is a stand-alone table reserved for system settings.

The deletion rules protect the office's records. Deleting an event also deletes its tasks and documents; a venue used by events cannot be deleted, only merged into another venue; deleting a building leaves its venues in place; and user accounts are deactivated rather than deleted, so every record keeps its author. For readability, the diagram omits the created_at and updated_at timestamps that every table has; all columns are listed in the data dictionary.

### Figure 3.3 - Context Diagram

Figure 3.3 presents the context diagram, or Level 0 data flow diagram, of EMO Tracker. It shows the whole system as a single process, Process 0, and the data it exchanges with five external entities. It uses Yourdon-DeMarco notation: the circle is the process, the rectangles are the external entities, and each labeled arrow is a data flow.

The Administrator provides login details; event, venue, building and account details; and tasks and documents. In return, the Administrator receives the schedule with readiness labels, analytics, PDF reports and the Excel export, and the backup status. The Officer provides login details, tasks with their assignments, and documents, and receives the schedule and readiness labels, analytics, documents, PDF reports and the Excel export. Staff members provide login details and status updates for their own tasks, and receive the schedule and event details, their assigned tasks, and documents and PDF reports. The EMO's schedule spreadsheet is a one-time source of schedule rows, imported when the system is installed, and the backup storage receives a daily backup of the database and the uploaded documents.

### Figure 3.4 - Use Case Diagram

Figure 3.4 shows the use case diagram of EMO Tracker in UML notation, with three actors and the functions each can perform within the system boundary. The actors form a hierarchy, shown by generalization arrows with hollow triangles: an Officer can do everything a Staff member can, and the Administrator can do everything an Officer can.

Every user, starting with Staff, can log in and log out, change their own password, view the Home page, Events and Schedule, view event details, download documents and PDF reports, view their own tasks, and update the status of their own tasks. The Officer can also manage tasks (add, edit, delete and assign them), upload documents, view Analytics, and export the Schedule to Excel. The Administrator can also add and edit events; cancel, restore or delete events; manage venues and buildings; manage user accounts; delete documents; and view the backup status.

Adding or editing an event always includes the venue overlap check («include»), and when an event's start date or time changes, editing it can be extended by recording a reschedule («extend»). The backend enforces these permissions on every request, so they also hold for requests made outside the user interface.

### Table 1 - Risk Assessment and Analysis

Table 1 presents the risk assessment and analysis of EMO Tracker. It identifies sixteen risks (R1 to R16) to the system's operation and to the project. Each risk is given a category (technical, data, security, operational or project), its likelihood and impact, a risk level derived from a likelihood-by-impact matrix before mitigation, and a mitigation strategy. Of the sixteen risks, three are rated high, nine medium and four low.

The three high risks come from running the system on a single laptop and network. Failure or downtime of the server laptop (R1) is mitigated by keeping it plugged in during office hours, starting the system with a double-click, and documented steps for moving it to another laptop and restoring the latest backup. Data loss (R2) is mitigated by automatic daily backups of the database and all documents, which can be stored on a USB drive; the Administrator's Home page shows the last backup or a warning if backups fail, a backup is saved before every database update, and a restore command brings the data back. Network unavailability (R3) is mitigated by using a dedicated router or hotspot, needing only a local network rather than the internet, and letting the server laptop itself keep using the system.

The medium risks are the single point of failure of running every part on one laptop, unauthorized access, a compromised Administrator account, double-booking of venues, resistance to leaving the familiar spreadsheet, incorrect data entry, errors in migrating the spreadsheet, a code update running on an outdated database, and scope changes from feedback. The low risks are forgotten passwords, accidental deletion, simultaneous edits of the same record, and exposure of real data through the public code repository. Every risk has a mitigation built into the system or its procedures.

### Figure 3.5 - Network Topology

Figure 3.5 shows the network topology of EMO Tracker: a star topology on the EMO office's local area network (LAN), in which every device connects to one central Wi-Fi access point or router. The server laptop, the EMO's Windows laptop, runs the whole system. Laragon provides PHP 8.3 and the MySQL 8.4 database, the Laravel API listens only on the laptop itself (127.0.0.1:8000), the React application is served to the other devices on port 5173, and uploaded documents are kept in private storage. A USB drive connected to the server laptop receives the automatic daily backups, so a failure of the laptop does not take the backups with it.

The users' laptops, computers, tablets and phones connect to the access point, which may be the university Wi-Fi or a router of the office's own, and open the system in a web browser at the server laptop's address (for example, http://192.168.1.5:5173); nothing is installed on these devices. The four devices in the figure are labeled by role as examples, since any role can use any device. No internet or cloud connection is required, which keeps the EMO's data within the office.

### Table 2 - Compatibility Checking Table

Table 2 lists the software and hardware that EMO Tracker requires, with the version used by the project, whether each is software or hardware, and its role in the system. On the backend, the system uses Laravel 13 on PHP 8.3 or newer, Laravel Sanctum for token-based login, dompdf for PDF reports, PhpSpreadsheet for the spreadsheet import and the Excel export, and MySQL 8.4 as the database. On the frontend, it uses React 19 with React Router, Vite, Tailwind CSS and axios, together with FullCalendar for the event calendar and Recharts for the Analytics chart; these are user-interface libraries that run inside the system rather than outside services. Apple devices display their own San Francisco font, and other devices use Inter, its closest free match, which is bundled with the system so that it works without internet access.

The tools and platforms are Composer, Node.js 20 and npm; Laragon on the EMO's Windows 10 or 11 laptop; Homebrew on the Mac used for development and the defense; and a modern web browser, tested in Google Chrome. The hardware consists of a server laptop that stays on while the office uses the system, a wireless router or hotspot for the local network, a USB flash drive or external drive for the daily backups, and the client devices of the EMO's members. The table shows that the system is built entirely from free, locally installed software and runs on the office's existing laptop and network.

---

## Appendix D

### Database Schema / SQL Scripts

This appendix presents the SQL statements that create the EMO Tracker database in MySQL 8.4. A CREATE TABLE statement is given for each of the seven application tables (users, buildings, venues, events, tasks, documents and settings) and for personal_access_tokens, which stores the login tokens, in the order in which the tables depend on one another. Each statement defines the table's columns with their data types and defaults, the primary key, the indexes, and the foreign keys with their delete rules; for example, deleting an event also deletes its tasks and documents, while deleting a venue leaves its events without a venue.

The statements were exported from the database built by the system's migrations and verified by creating an empty database from them and comparing the result with the original, which matched in every column, index and foreign key. Compared with Capstone 1, the event_staff table was removed, since an event's people are now its task owners; the buildings and venues tables were added; and the events table gained columns for the event type, department, venue and room, end date and time, original date and time, control number, remarks and whether the EMO prepares the event, while the budget column was removed.

### API Endpoint Documentation

This appendix documents the forty-two endpoints of the EMO Tracker REST API, grouped into authentication, Home pages, events, the Schedule, venues and buildings, tasks, documents and reports, analytics, and accounts. For each endpoint, the table gives the path, the HTTP method, whether a login is required and for which roles, the parameters with their types and whether they are required, the success response, and the possible error codes.

All paths begin with /api, and requests and responses use JSON except for file downloads. Every endpoint except login and the server check requires the login token. A missing, expired or revoked token returns HTTP 401, a role that may not use the endpoint returns 403, an item that no longer exists returns 404, and invalid input returns 422 with the reason for each field. The table was checked against the system's own route list, so no endpoint is missing or invented, and it shows how the server enforces the permissions in the use case diagram (Figure 3.4).

### Data Dictionary

The data dictionary describes all 64 columns of the seven application tables of the EMO Tracker database: users (10 columns), events (20), tasks (10), buildings (5), venues (5), documents (9) and settings (5). For each column, it gives the field name, data type, constraints and meaning. The constraints show which fields are required, unique or optional, and identify the foreign keys together with what happens when the referenced record is deleted. The data dictionary complements the entity relationship diagram (Figure 3.2), which shows the same tables and their relationships graphically.

### Figure D.1 to D.3 - System Flowchart (Admin, Officer, Staff)

Figures D.1 to D.3 present the system flowcharts of EMO Tracker for the Administrator, the Officer and Staff. Each traces one session of use, from logging in to logging out, with the decisions the system makes along the way, using standard flowchart symbols: rounded terminators for start and end, rectangles for processes, parallelograms for input, and diamonds for decisions. All three begin the same way. The user opens EMO Tracker and enters an email and password; if the details are wrong or the account is deactivated, the system shows the reason and asks again, and otherwise it shows the Home page for the user's role. After each action, the user may choose another action or log out.

*Figure D.1*

In Figure D.1, the Administrator chooses among five kinds of action. When entering or changing an event's details, the system checks for overlapping bookings at the same venue and shows a warning if it finds one, but still allows the event to be saved, with its status set from its date. The Administrator can also add, edit or merge venues and buildings; add, edit or deactivate accounts; manage tasks and upload or delete documents; and view Analytics, download PDF reports and export the Schedule to Excel.

*Figure D.2*

In Figure D.2, the Officer chooses among four actions. To prepare an event, the Officer opens its panel: if the event is completed, its tasks are locked and can only be viewed; otherwise, the Officer adds or edits tasks with an owner, due date and priority, and the system recalculates the event's readiness label. When a document is uploaded, the system accepts only PDF, Word, Excel, JPG and PNG files of up to 10 MB, stores accepted files privately with the event, and explains why any other file was refused. The Officer can also monitor the readiness labels, their reasons and Analytics, and download PDF reports or export the Schedule to Excel.

*Figure D.3*

In Figure D.3, a Staff member, often using a phone, chooses among three actions. To update a task, the Staff member opens My tasks and chooses the new status (Pending, In progress or Done); the system saves the change only if the task is the user's own and its event is not completed, and then updates the event's readiness; otherwise, it shows that the change is not allowed. The Staff member can also check events through My events or the Schedule, and download documents or PDF reports.

### Figure D.4 - Data Flow Diagram Level 1

Figure D.4 presents the Level 1 data flow diagram of EMO Tracker, which breaks the single process of the context diagram (Figure 3.3) into seven processes. It shows the data flowing between these processes, the external entities, and five data stores: D1 Users, D2 Events, D3 Venues and buildings, D4 Tasks and D5 Documents. It uses Yourdon-DeMarco notation, in which circles are processes, rectangles are external entities, and pairs of parallel lines are data stores. Each process is drawn in its own row so that no flows cross; entities and data stores used by more than one process are therefore drawn more than once and marked with an asterisk.

Process 1.0 authenticates users and manages accounts using the user records. Process 2.0 manages events and checks overlaps: it keeps the event records, reads the venue list to detect double-bookings, sends the schedule and event details to every role, and, when an event is deleted, deletes its tasks and documents. Process 3.0 maintains the venues and buildings. Process 4.0 manages tasks from the input of the Administrator and Officers and the status updates of Staff, reading the users who can own tasks. Process 5.0 classifies readiness and produces analytics from the event and task data. Process 6.0 stores documents and produces PDF reports and the Excel export from the event, task, and venue and building data. Process 7.0 imports the EMO's spreadsheet once, creating events and any new venues, copies all data stores into the daily backup, and reports the backup status to the Administrator. The diagram is balanced with the context diagram: every flow to or from an external entity in Figure 3.3 appears here.

### Figure D.5 - Class Diagram

Figure D.5 shows the UML class diagram of the main classes of the EMO Tracker backend. The domain classes correspond to the database tables. User has methods that check the user's role and sign the account out of every device. Event is the central class, with methods that work out its readiness and the reason for it, whether it is ongoing, its status and its location, and static methods that set a status from a date and mark finished events completed. Task, Document, Venue and Building hold the data shown in the entity relationship diagram. A User creates Events, may own Tasks and uploads Documents, and a Venue hosts Events. An Event is composed of its Tasks and Documents (filled diamonds), which cannot exist without it and are deleted with it, while a Building aggregates Venues (hollow diamond), which remain if the building is deleted.

The service classes, marked «service», hold the business rules as static methods. EventClassifier classifies readiness, VenueClashes compares bookings for overlaps, ScheduleImport creates events and any missing venues from the spreadsheet, ScheduleExport reads events to build the Excel file, and Backup copies the whole database and the uploaded documents into one .zip file. The controllers and the framework's classes are left out for readability.

### Figure D.6 to D.8 - Sequence Diagram

Figures D.6 to D.8 present sequence diagrams for three key scenarios of EMO Tracker, showing the messages exchanged, in order, between the user, the browser running the React application, the Laravel API, the system's services and the MySQL database.

*Figure D.6*

Figure D.6 traces a login and one later request. The browser sends the email and password to the API through the frontend server, which passes along the address of the user's device. The API applies the login limit of ten attempts per minute for each email address and device, retrieves the user record, and verifies the password hash and that the account is active. With correct credentials, it stores a new access token valid for 30 days and returns it with the user's name and role, and the browser opens the Home page for that role. A wrong email or password, or a deactivated account, returns an error (HTTP 422) that is shown on the login form, and too many attempts return HTTP 429. Every later request carries the token: using the Administrator's Home page as the example, the API marks finished events as completed, looks up the token, checks that the user's role may use the route, reads the events and tasks, and returns them as JSON.

*Figure D.7*

Figure D.7 shows how the overlap check works while the Administrator adds an event. As the venue, date and time are entered, the browser waits 0.4 seconds after each change and then asks the API for clashing bookings. The VenueClashes service retrieves the other bookings at the same venue on overlapping days that are not cancelled, keeps those in the same room (or with no room named) and with overlapping hours, and returns them. If there are clashes, the form shows a warning naming each booking while still allowing the event to be saved. When the Administrator clicks Add event, the API checks the role, validates the details, sets the status from the date (upcoming, or completed if the event is already over), saves the event and returns it, and the browser confirms that the event was added to the Schedule and the calendar.

*Figure D.8*

Figure D.8 shows what happens when a Staff member marks a task as done. The browser sends the new status to the API with the login token; the API first marks finished events completed, then looks up the token, the task and its event. If the user is not the task's owner (and is not an Officer or the Administrator), or the event is completed (and the user is not the Administrator), the API refuses the change with HTTP 403 and the browser shows the reason. Otherwise, the API saves the status and the browser confirms it. The browser then reloads the event or task list; the API reads the event and its tasks and asks the EventClassifier for the new readiness label and reason, so the updated progress and label appear on the screen.

---

## Appendix E

### Figure E.1 to E.3 - Site Map (Admin, Officer, Staff)

Figures E.1 to E.3 present the site maps of EMO Tracker for the Administrator, the Officer and Staff. Each is an indented tree that starts from the system and the login page and branches into the pages in the role's menu, with each page's sections, filters, panels and dialogs listed beneath it. To fit a page, the menu pages are arranged in two columns joined under the role. The event panel opens from more than one page, so it is shown in full once and marked "same as under" elsewhere.

*Figure E.1*

The Administrator's site map (Figure E.1) has seven menu entries. Home shows the summary, the events that need attention, the next seven days, the user's open tasks and the backup status. My Tasks lists the user's tasks. Events offers the calendar and list views, the add event form and the event panel, from which the tasks, documents, the PDF report, and the edit and delete event actions are reached. The Schedule includes the Excel export and the Venues page for managing and merging venues and buildings. The remaining entries are Analytics, Accounts, and the user menu for changing the password and logging out.

*Figure E.2*

The Officer's site map (Figure E.2) has six entries: Home, My Tasks, Events, the Schedule (view only, with the Excel export), Analytics and the user menu. In the event panel, the event's own details are view only, but the Officer can add, edit and delete tasks, change their status, upload and download documents, and download the PDF report. There are no Accounts or Venues pages and no event form, because these belong to the Administrator.

*Figure E.3*

The Staff site map (Figure E.3) is the smallest, with five entries: Home, My Tasks, My Events, the Schedule (view only) and the user menu. The event panel is view only except for the status of the user's own tasks, and documents and the PDF report can only be downloaded.

### Figure E.4 to E.7 - User Journey Map (Current Process, Admin, Officer, Staff)

Figures E.4 to E.7 present the user journey maps. Each follows one kind of user across the stages of their work, showing for each stage the user's actions, the touchpoint used, a representative thought, the feeling, the pain point and an opportunity, with a feelings curve that runs from very negative to very positive. Figure E.4 shows the EMO's current process before the system (As-Is), and Figures E.5 to E.7 show the journeys of the Administrator, the Officer and Staff with EMO Tracker (To-Be). The thoughts and feelings in the To-Be maps are the expected experience, to be confirmed with the EMO's users during the pilot.

*Figure E.4*

Figure E.4 describes how EMO members keep the schedule and prepare events with a shared, hand-typed spreadsheet, based on the analysis of the office's 2026 schedule sheet and the problems identified in Capstone 1. Its pain points are that overlapping bookings are found only by noticing them (13 pairs in the 2026 sheet), venues are typed freely (about 80 spellings for about 25 places), reschedules and cancellations are hidden in text, no single place tracks tasks and their owners, there is no objective way to tell which events need attention, and reports are made by hand. The feelings curve stays at or below neutral and is lowest at tracking readiness. In place of opportunities, the last row names the EMO Tracker feature that addresses each pain point.

*Figure E.5*

Figure E.5 follows the Administrator through logging in, checking the Home page, recording events, spotting overlaps, handling changes, managing venues and accounts, and reviewing reports. The feelings curve stays mostly above neutral and peaks when reports and statistics are ready without being compiled by hand. It dips when an overlap warning still requires contacting the other party, and when the Administrator must reset every forgotten password.

*Figure E.6*

Figure E.6 follows the Officer through preparing an event: checking the Home page and the Schedule, adding tasks with owners, uploading documents, monitoring readiness, reporting, and closing the event. The curve rises as tasks are assigned and documents are kept with the event, and dips when schedule changes must go through the Administrator and when a Critical label still needs people to act on it.

*Figure E.7*

Figure E.7 follows a Staff member working on an assigned task, often from a phone: logging in, viewing the Home page, reviewing tasks, checking events, updating progress, getting files, and finishing the task. The curve starts at neutral, because a forgotten password must wait for the Administrator, then stays positive and peaks when progress is updated and the task is finished.

### Figure E.8 - UI Wireframes (All Roles)

Figure E.8 presents the low-fidelity wireframes of the main screens of EMO Tracker for each role. They are drawn in grayscale, with gray bars standing for text and the system's real wording for headings and buttons. On a desktop, each screen has a header with the system name and the user's menu, a sidebar of pages, and the page content; on a phone, the sidebar becomes a bar at the bottom of the screen.

The Administrator's wireframes show the Home page, the Schedule, the new event form with its overlap warning, and the Accounts page. The Officer's wireframes show the Events calendar, the event panel with tasks and documents, the new task form, and the Analytics page. The Staff wireframes are drawn on a phone and show the login page, the Home page, My tasks, and the view-only event panel, in which only the user's own tasks have a status menu.

---

## Appendix G

### Figure G.1 - Events Management Office Chart

Figure G.1 shows the organizational chart of the Events Management Office (EMO), the client of EMO Tracker. The Director heads the office, with the Ministrong Tagasubaybay below. The Secretary works alongside the Ministrong Tagasubaybay, and five Tech Support members work under the Secretary.

### Figure G.2 - Gantt Chart

Figure G.2 shows the Gantt chart of the whole capstone project, from the title proposal in February 2026 to the Capstone 2 defense and final checking in November 2026. It keeps the activities and dates of the Capstone 1 chart and adds the Capstone 2 activities to the same phases: planning and analysis, system design, development, implementation and evaluation, defense and revision, and documentation and finalization.

Capstone 1 covered the title proposal and requirements (February to March 2026), the database and user interface design (March to April), the initial system development (April to May), the pre-oral defense on May 14, and the revisions and final checking (May to June). After the break from June to August, Capstone 2 covered planning and system review (September); requirements gathering with the EMO, design updates, security hardening and prototype refinement (late September to early October); the pre-defense audit (October 7 to 12); installation, pilot testing and a user survey at the EMO (October 13 to 31); the final defense in the first week of November; and the revisions and final checking that follow. Gray bars are finished activities, light blue bars are in progress, purple bars are planned, and the diamonds mark the two defenses.
