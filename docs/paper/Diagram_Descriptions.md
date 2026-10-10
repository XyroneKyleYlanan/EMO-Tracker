# Diagram Descriptions

Descriptions of the diagrams for the EMO Tracker Capstone 2 paper, organized like the Capstone 1 paper. Part 1 has the figures for **Chapter 3 (Methodology and System Design)**, and Part 2 has the figures for the **appendices**. The images are in the [`diagrams/`](diagrams/) (with the sequence diagrams in [`diagrams/sequence-diagrams/`](diagrams/sequence-diagrams/)), [`flowcharts/`](flowcharts/), [`site-maps/`](site-maps/), [`journey-maps/`](journey-maps/) and [`wireframes/`](wireframes/) folders.

- **Figure numbers:** Chapter 3 figures are numbered 3.1 to 3.9; renumber them if other figures come first, such as a conceptual framework. Appendix figures are numbered by their appendix letter, as in Capstone 1 (for example, Figure G.2 for the Gantt chart), so they have no numbers here.
- **Accuracy:** every description was checked against the system's code as of October 10, 2026. It uses the system's own terms: Administrator, Officer, Staff; On Track, At Risk, Critical; Upcoming, Ongoing, Completed, Cancelled.
- **Sources:** each entry links to the file the image was drawn from. Six diagrams are drawn from Mermaid code in [CAPSTONE_PAPER_REFERENCE.md](CAPSTONE_PAPER_REFERENCE.md) (Part E); the rest from the files in each folder's `src/` folder. The journey maps also exist as tables that can be edited in Word ([`tables/user-journey-map.html`](tables/user-journey-map.html)).
- **Descriptions of the tables** (data dictionary, compatibility, risk assessment, API endpoints) are in [Table_Descriptions.md](Table_Descriptions.md).

---

# Part 1. Chapter 3 figures

### Figure 3.1. Prototyping Model

**File:** [diagrams/prototyping-model.png](diagrams/prototyping-model.png) (source: [diagrams/src/prototyping-model.mmd](diagrams/src/prototyping-model.mmd))

EMO Tracker was developed with the Prototyping Model, an iterative software development life cycle in which a working version of the system is built early, evaluated with the client, and refined until it meets the users' needs. The diagram shows the six phases and, beside each, when the project went through it.

1. **Requirements gathering and analysis:** in Capstone 1, the title proposal and requirements (February to March 2026). In Capstone 2, consultations with the EMO and an analysis of its 2026 schedule spreadsheet (October 2026).
2. **Quick design:** the database and UI/UX design (March to April 2026), updated in Capstone 2 for the Schedule, venues and event statuses.
3. **Build the prototype:** the Capstone 1 prototype (April to May 2026).
4. **Evaluation:** each version was checked against the EMO's needs. So far, at the pre-oral defense (May 2026), at the two consultations with the EMO (October 2 and 6, 2026) and in a pre-defense audit (October 7, 2026).
5. **Refine the prototype:** when the answer is no, the feedback starts another round of design and building. The Capstone 2 rounds (September 28 to October 9, 2026) added:
   - the Schedule, the venue list, the spreadsheet import and the backups
   - event statuses, event types and the double-booking warning
   - the audit fixes and UI polish
6. **Implementation and maintenance:** once the system meets the EMO's needs, it is presented at the Capstone 2 defense (November 2026), installed on the EMO's laptop, piloted and evaluated.

The model suited the project because the EMO's needs became clear only as its members saw working versions; for example, the Schedule page and the venue list came from the October consultations.

### Figure 3.2. System Architecture of EMO Tracker

**File:** [diagrams/architecture.png](diagrams/architecture.png)

EMO Tracker follows a three-tier client–server architecture installed on a single laptop on the Events Management Office's local network.

- **Presentation tier:** users open the system in a web browser, either on the server laptop itself (`localhost:5173`) or from phones and other laptops on the same Wi-Fi network (the laptop's IP address, port 5173). The frontend server (Vite) sends the React single-page application to the browser. It also forwards every request whose path begins with `/api` to the application tier.
- **Application tier:** a Laravel REST API that listens only on the laptop itself (`127.0.0.1:8000`), so no other device can reach it directly. It enforces authentication, role-based access and every business rule, including readiness classification, venue overlap detection and event status changes.
- **Data tier:** a MySQL 8.4 database, a private storage area for uploaded documents, and a backup location. Each day the system writes a backup of both the database and the documents there; the location can be a folder on the laptop or an external USB drive.

The system runs entirely on the local network and uses no external services or cloud hosting.

### Figure 3.3. Network Topology

**File:** [diagrams/network-topology.png](diagrams/network-topology.png) (source: [diagrams/src/network-topology.svg](diagrams/src/network-topology.svg))

EMO Tracker runs on the EMO office's local network in a star topology: every device connects to one central Wi-Fi access point or router.

- **Server laptop:** the EMO's Windows laptop runs the whole system through Laragon:
  - the Laravel API, which listens only on the laptop itself (`127.0.0.1:8000`)
  - the MySQL 8.4 database
  - the uploaded documents, in private storage
  - the React application, served to the other devices on port 5173
- **USB drive (recommended):** connected to the server laptop, it receives the automatic daily backups. By default, backups are saved in a folder on the laptop; the `BACKUP_PATH` setting points them to the USB drive instead, so a broken laptop does not take the backups with it.
- **Access point:** the university Wi-Fi or a router of the office's own. The topology is the same either way.
- **Clients:** office laptops and phones open the system in any web browser at the server's address (`http://SERVER-IP:5173`). Nothing is installed on them.

No internet connection is needed. The system can only be reached from devices on the same network, which keeps the EMO's data inside the office.

### Figure 3.4. Context Diagram (Level 0 Data Flow Diagram)

**File:** [diagrams/context-diagram.png](diagrams/context-diagram.png) (source: [diagrams/src/context-diagram.svg](diagrams/src/context-diagram.svg))

The context diagram presents EMO Tracker as a single process, Process 0, and shows the data exchanged with the five external entities that interact with it. It uses Yourdon–DeMarco notation: the circle is the process, the rectangles are external entities, and each labeled arrow is a data flow.

- **Administrator:** supplies login details; event, venue, building and account details; and tasks and documents. Receives the schedule with readiness labels, analytics, PDF reports and the Excel export, and the backup status.
- **Officer:** supplies login details, tasks with their assignments, and documents. Receives the schedule and readiness labels, analytics, documents, and PDF reports and the Excel export.
- **Staff:** supply login details and status updates for their own tasks. Receive the schedule and event details, their assigned tasks, and documents and PDF reports.
- **The EMO's schedule spreadsheet:** a one-time source of schedule rows, imported from the command line when the system is installed.
- **Backup storage:** receives a daily backup of the database and the uploaded documents.

### Figure 3.5. Use Case Diagram

**File:** [diagrams/use-cases.png](diagrams/use-cases.png) (source: [diagrams/src/use-cases.svg](diagrams/src/use-cases.svg))

The use case diagram, drawn in UML notation, shows three actors and the functions each can perform within the system boundary.

- **Generalization:** the actors form a hierarchy, shown by solid lines with hollow triangles. An Officer inherits every use case of Staff, and an Administrator inherits every use case of the Officer.
- **Staff** (and therefore every user) can log in and log out; change their own password; view the Home page, Events and Schedule; view event details; download documents and PDF reports; view their own tasks; and update the status of their own tasks.
- **The Officer** also manages tasks (adds, edits, deletes and assigns them), uploads documents, views Analytics and exports the Schedule to Excel.
- **The Administrator** also adds and edits events; cancels, restores or deletes events; manages venues and buildings; manages user accounts; deletes documents; and views the backup status.
- **«include»:** adding or editing an event always includes the venue overlap check.
- **«extend»:** when an event's start date or time changes, editing it can be extended by recording a reschedule.

The backend checks these permissions on every request, so the restrictions also hold for requests made outside the user interface.

### Figure 3.6. Entity Relationship Diagram

**File:** [diagrams/erd.png](diagrams/erd.png)

The entity relationship diagram, in crow's foot notation, shows the structure of the EMO Tracker database.

- **EVENTS** is the core entity. For each event it stores:
  - the name, type (internal or external) and department
  - the venue and room
  - the dates and times, and the original date and time if the event was rescheduled
  - the control number and remarks
  - whether the EMO prepares the event
  - the stored status: upcoming, completed or cancelled
- **Venues and buildings:** each event may be hosted by one venue from the managed list, and each venue may belong to one building, whose color is used on the Schedule. Both links are optional, so an event can name a typed place instead, and a venue can stand outside any building.
- **Tasks and documents:** an event has zero or more TASKS and zero or more DOCUMENTS, and each of these belongs to exactly one event.
- **USERS** relate to four entities. A user creates zero or more events, owns zero or more tasks (a task has at most one owner and may have none), uploads zero or more documents, and signs in with zero or more PERSONAL_ACCESS_TOKENS (the login tokens that the authentication process stores).
- **SETTINGS** is a stand-alone key–value table reserved for system settings, not yet used by the application.

Deletion rules preserve the record:
- Deleting an event also deletes its tasks and documents.
- A venue used by events cannot be deleted, only merged into another venue.
- Deleting a building leaves its venues in place.
- User accounts are deactivated rather than deleted, so every record keeps its author.

Every table also has `created_at` and `updated_at` timestamps, left out of the diagram for readability; the data dictionary lists every column.

### Figure 3.7. Rule-Based Readiness Classification Flowchart

**File:** [diagrams/readiness-flowchart.png](diagrams/readiness-flowchart.png)

The flowchart shows the deterministic, rule-based classifier that labels each event the EMO prepares as On Track, At Risk or Critical. The rules are checked in a fixed order, and the first rule that applies decides the label.

1. **Not classified:** events that are cancelled or completed, or that the EMO only schedules without preparing. They show their status instead (Cancelled, Completed or Scheduled).
2. **No tasks:** the event is At Risk.
3. **All tasks done:** the event is On Track, regardless of its date.
4. **Critical**, if any of these holds:
   - an open task is overdue
   - the event is two days away or less
   - the event is seven days away or less and under 40% of its tasks are done
   - the event is seven days away or less and more than half of its open tasks have no owner
5. **At Risk**, if no Critical condition holds and either:
   - the event is fourteen days away or less and under 70% of its tasks are done, or
   - any open task has no owner
6. **On Track** in all other cases.

With the label, the system shows the reason from the rule that applied, for example "Only 20% done, 5 days to go". The classifier uses no machine learning, training data or external service. Its thresholds are constants in a single class (`EventClassifier`), so they can be adjusted after user testing.

### Figure 3.8. Venue Double-Booking Check Flowchart

**File:** [diagrams/overlap-check.png](diagrams/overlap-check.png)

The flowchart shows how the system decides whether two bookings of the same venue overlap.

- **Which events are checked:** only events whose venue was chosen from the managed venue list. Places typed as free text are not checked, because they cannot be compared reliably.
- **The comparison:** the event is compared with each other booking at the same venue. A pair does not overlap if either event is cancelled, if the two name different rooms of the venue, or if their dates or hours do not overlap.
- **Interpretation rules:**
  - an event with no room named uses the whole venue, so it overlaps a booking in any room
  - an event with no start time lasts all day, and one with no end time lasts until midnight
  - hours that run past midnight count as all day
  - times that only touch, such as 8–10 AM and 10 AM–12 PM, do not overlap
- **The result:** when every condition holds, the system shows an overlap warning on the event form, in the event panel and on the Schedule.

The warning does not prevent saving, because some overlaps are intentional, such as a rehearsal held right before its own event.

### Figure 3.9. Event Status Lifecycle (State Diagram)

**File:** [diagrams/status-lifecycle.png](diagrams/status-lifecycle.png)

The state diagram shows the statuses an event passes through and what causes each change.

- **Stored and computed statuses:** the system stores three statuses: Upcoming, Completed and Cancelled. Ongoing is not stored; it is computed while an Upcoming event is taking place. Upcoming and Ongoing are therefore drawn together as one composite state, "Not over yet".
- **Over time:** an event added with a future date enters this state as Upcoming. It becomes Ongoing when the start time on its first day arrives, and Completed once the end time of its last day passes. An event added or imported with a past date starts as Completed.
- **Date changes:** changing an event's dates recalculates its status. An Administrator can return a Completed event to the composite state by moving it to a future date, or complete an event by moving its date into the past.
- **Cancelling:** an Administrator can cancel an event from any other status. A cancelled event stays on the Schedule; restoring it returns it to the composite state if it is still ahead, or to Completed if it is already over.
- **Deleting:** an Administrator can delete an event in any state.

Completion is applied automatically at the start of every request to the system, so a finished event's tasks are locked even if no one opens the event. A reschedule is recorded as a mark on the event (its original date and time), not as a separate status.

---

# Part 2. Appendix figures

In the order of the Capstone 1 appendices.

## System flowcharts

The three system flowcharts show, step by step, how each role uses EMO Tracker in one session: logging in, choosing actions, and the decisions the system makes along the way. They use standard flowchart symbols:
- rounded terminators for start and end
- rectangles for processes
- parallelograms for input
- diamonds for decisions

### System Flowchart: Administrator

**File:** [flowcharts/admin.png](flowcharts/admin.png) (source: [flowcharts/src/admin.mmd](flowcharts/src/admin.mmd))

1. **Log in:** the Administrator opens EMO Tracker and enters an email and password. If the details are wrong or the account is deactivated, the system shows the reason and asks again.
2. **Home:** the system shows the Administrator's Home, with the summary, the events that need attention and the next 7 days.
3. **Choose an action** among five branches:
   - **Events:** entering or changing an event's details, with a check for overlapping bookings. If there is one, a warning is shown, but the event can still be saved; its status follows its date.
   - **Venues:** adding, editing or merging venues and buildings.
   - **Accounts:** adding, editing or deactivating accounts.
   - **Tasks and documents:** managing tasks, and uploading or deleting documents.
   - **Analytics and reports:** viewing Analytics, downloading PDF reports and exporting the Schedule to Excel.
4. **Repeat or finish:** after each action, the Administrator can choose another one or log out.

### System Flowchart: Officer

**File:** [flowcharts/officer.png](flowcharts/officer.png) (source: [flowcharts/src/officer.mmd](flowcharts/src/officer.mmd))

After the same login steps, the Officer sees the Officer's Home and chooses among four actions:
- **Prepare an event:** opening the event panel. If the event is completed, its tasks are locked and can only be viewed. Otherwise the Officer adds or edits tasks with an owner, a due date and a priority, and the system recalculates the event's readiness label.
- **Upload a document:** the system accepts only PDF, Word, Excel, JPG and PNG files up to 10 MB. An accepted file is stored privately with the event; otherwise the system explains why the file was refused.
- **Monitor:** viewing the readiness labels, their reasons and Analytics.
- **Report:** downloading the PDF report or exporting the Schedule to Excel.

The Officer can repeat actions or log out.

### System Flowchart: Staff

**File:** [flowcharts/staff.png](flowcharts/staff.png) (source: [flowcharts/src/staff.mmd](flowcharts/src/staff.mmd))

A Staff member logs in on a laptop or phone and sees the Staff Home with their open tasks and upcoming events. They then choose among three actions:
- **Update a task:** opening My tasks and choosing the new status (Pending, In progress or Done). The system saves the change only if the task is the user's own and its event is not completed; it then updates the event's readiness. Otherwise it shows that the change is not allowed.
- **Check events:** opening My events or the Schedule and viewing an event's details.
- **Get files:** downloading documents or the PDF report.

## Data flow diagram (Level 1)

### Data Flow Diagram (Level 1)

**File:** [diagrams/dfd-level-1.png](diagrams/dfd-level-1.png) (source: [diagrams/src/dfd-level-1.svg](diagrams/src/dfd-level-1.svg))

The Level 1 data flow diagram breaks the single process of the context diagram (Figure 3.4) into seven processes. It shows the data that flows between them, the external entities and five data stores. It uses Yourdon–DeMarco notation:
- circles are processes
- rectangles are external entities
- pairs of parallel lines are data stores

Each process has its own row so that no flows cross. Entities and data stores used by more than one process are therefore drawn more than once, marked with an asterisk, as DFD notation allows. Lookups of people's names (for example, a task owner's name on a report) are left out for readability.

**Processes:**
- **1.0 Authenticate and manage accounts:** receives login details from all three roles and account details from the Administrator, and reads and writes the user records (D1 Users).
- **2.0 Manage events and check overlaps:** receives event details from the Administrator and keeps the event records (D2). It reads the venue list (D3) to check for overlaps, and sends the schedule and event details to every role, with overlap warnings to the Administrator. Deleting an event also deletes its tasks (D4) and its documents (D5).
- **3.0 Manage venues and buildings:** keeps the venue records (D3) from the Administrator's details.
- **4.0 Manage tasks:** receives tasks and assignments from the Administrator and Officers, and status updates from Staff. It keeps the task records (D4), reads the users who can own tasks (D1), and sends Staff their assigned tasks.
- **5.0 Classify readiness and analyze:** reads event dates and statuses (D2) and task progress (D4), and sends readiness labels and analytics to the Administrator and Officers.
- **6.0 Manage documents and reports:** stores uploaded documents (D5), and produces documents, PDF reports and the Excel export. It draws on the event (D2), task (D4), and venue and building (D3) data; the building colors shade the Excel export.
- **7.0 Import and back up data:**
  - imports the EMO's spreadsheet once, creating events (D2) and any new venues (D3)
  - copies all data stores into the daily backup
  - reports the backup status to the Administrator

The diagram is balanced with the context diagram: every flow to or from an external entity in the context diagram appears here, split among the processes that handle it.

## Class diagram

### Class Diagram

**File:** [diagrams/class-diagram.png](diagrams/class-diagram.png) (source: [diagrams/src/class-diagram.mmd](diagrams/src/class-diagram.mmd))

The UML class diagram shows the main classes of the backend, with their attributes, their methods and the relationships between them.

- **Domain classes** (the Laravel models, one per database table):
  - **User:** has role checks (`isAdmin`, `isOfficer`, `isStaff`) and `signOutEverywhere`.
  - **Event:** the central class. It has the methods that work out its readiness, its reason, whether it is ongoing, its lifecycle status and its location. Its static methods set a status from a date (`statusForDate`) and mark finished events completed (`completePastEvents`).
  - **Task**, **Document**, **Venue** and **Building:** hold the data shown in the ERD.
- **Associations:**
  - a User creates many Events, may own many Tasks and uploads many Documents
  - a Venue hosts many Events
- **Composition** (filled diamonds): an Event *has* its Tasks and Documents. They cannot exist without it and are deleted with it. A filled diamond already means each part belongs to exactly one event, so no number is written on the Event side.
- **Aggregation** (hollow diamond): a Building *groups* Venues. The venues remain if the building is deleted.
- **Service classes** (marked «service»): hold the business rules as static methods, and depend on Event, and in one case on Venue (dashed arrows).
  - **EventClassifier:** classifies readiness.
  - **VenueClashes:** compares bookings for overlaps.
  - **ScheduleImport:** creates events from the spreadsheet, and any venues it doesn't find in the list.
  - **ScheduleExport:** reads events to build the Excel file.
  - **Backup:** copies the whole database and the uploaded documents into one .zip file.
- **Left out for readability:** the controllers, Laravel's framework classes and the unused Setting model.

## Sequence diagrams

### Sequence Diagram: Login and a Typical Request

**File:** [diagrams/sequence-diagrams/1-login.png](diagrams/sequence-diagrams/1-login.png) (source: Mermaid code in E.8.4 of [CAPSTONE_PAPER_REFERENCE.md](CAPSTONE_PAPER_REFERENCE.md))

The sequence diagram traces a login and one authenticated request through five participants: the user, the browser running the React application, the Vite frontend server, the Laravel API and the MySQL database.

1. **Login request:** the browser sends the email and password to the frontend server, which forwards the request to Laravel with the address of the user's device.
2. **Checks:** Laravel applies the login limit of ten attempts per minute for each email address and device. It then retrieves the user record from the database and verifies both the password hash and that the account is active.
3. **Three possible outcomes:**
   - **Correct credentials, active account:** Laravel stores a new access token that expires in 30 days and returns it with the user's name and role. The browser keeps the token and opens the Home page for that role.
   - **Wrong email or password, or a deactivated account:** Laravel returns an error (HTTP 422) whose reason the login form displays.
   - **More than ten attempts in a minute:** Laravel returns HTTP 429, and the form shows a "too many attempts" message.
4. **Later requests:** every later request carries the token. The diagram uses the Administrator's Home page as the example. Laravel marks finished events as completed (it does this on every request), looks up the token, and checks that the user's role may use the route. It then reads the events and tasks and returns JSON through the frontend server to the browser, which displays the Home page.

### Sequence Diagram: Adding an Event With the Overlap Check

**File:** [diagrams/sequence-diagrams/2-add-event.png](diagrams/sequence-diagrams/2-add-event.png) (source: [diagrams/sequence-diagrams/src/2-add-event.mmd](diagrams/sequence-diagrams/src/2-add-event.mmd))

This sequence diagram shows how the overlap check works while an Administrator adds an event.

1. **Checking as the form is filled:** while the Administrator enters the venue, date and time, the browser waits 0.4 seconds after each change, then asks the Laravel API for clashing bookings.
2. **Finding clashes:** the VenueClashes service retrieves the other bookings at the same venue on overlapping days that are not cancelled. It keeps those in the same room (or with no room) and with overlapping hours, and returns them as a list.
3. **Showing the result:** if there is a clash, the form shows a warning naming each booking and noting that the event can still be saved; otherwise no warning appears.
4. **Saving:** when the Administrator clicks "Add event", the API checks the role and validates the details. It sets the status from the date (upcoming, or completed if the event is already over), saves the event and returns it. The browser confirms *Event "[name]" added to the schedule.*, and the event appears on the Schedule and the calendar.

### Sequence Diagram: Updating a Task's Status

**File:** [diagrams/sequence-diagrams/3-task-status-update.png](diagrams/sequence-diagrams/3-task-status-update.png) (source: [diagrams/sequence-diagrams/src/3-task-status-update.mmd](diagrams/sequence-diagrams/src/3-task-status-update.mmd))

This sequence diagram shows what happens when a Staff member marks a task as done.

1. **The request:** the browser sends the new status to the API with the login token. On every request, the API first marks finished events completed; it then looks up the token, the task and its event.
2. **Three possible outcomes:**
   - **Not the task's owner** (and not an Officer or the Administrator): the API refuses with "You can only update your own tasks."
   - **The event is completed** (and the user is not the Administrator): the API refuses, because the tasks of a completed event are locked.
   - **Allowed:** the API saves the status, and the browser confirms "Task marked as Done." The browser then reloads the event or task list; the API reads the event and its tasks and asks the EventClassifier for the new readiness label and reason (for example, On Track, "All tasks done"). The updated progress and label appear on the screen.

## Site maps

The three site maps show how the screens of EMO Tracker are organized for each role, from the login page down to the dialogs and files each screen leads to.
- **Rectangles:** pages.
- **Rounded shapes:** panels and dialogs that open on top of a page.
- **Wavy-bottomed shapes:** generated files.
- **Repeated screens:** a screen reachable from more than one page, such as the event panel, appears under each page and is marked "same as under ..." rather than drawn with crossing lines.

### Site Map: Administrator

**File:** [site-maps/admin.png](site-maps/admin.png) (source: [site-maps/src/admin.mmd](site-maps/src/admin.mmd))

After logging in, the Administrator's Home leads to six areas:
- **My tasks.**
- **Events** (calendar and list), with the add event form, and an event panel that leads to:
  - the edit event form (including cancel and reschedule) and deleting the event
  - the task form
  - documents (upload, download, delete)
  - the PDF report
- **Schedule**, which also has the add event form, the Excel export, and the Venues page, with its venue and building forms and venue merging. The add event form can also create a new venue on the spot.
- **Analytics.**
- **Accounts**, with the account form (add, edit, deactivate).
- **The user menu**, with change password and log out.

### Site Map: Officer

**File:** [site-maps/officer.png](site-maps/officer.png) (source: [site-maps/src/officer.mmd](site-maps/src/officer.mmd))

The Officer's Home leads to five areas:
- **My tasks.**
- **Events**, whose event panel shows event details as view only and leads to:
  - the task form (add, edit, delete, assign)
  - documents (upload, download)
  - the PDF report
- **The Schedule** (view only), with the Excel export.
- **Analytics.**
- **The user menu.**

There are no Accounts or Venues pages, and no event form, because those belong to the Administrator.

### Site Map: Staff

**File:** [site-maps/staff.png](site-maps/staff.png) (source: [site-maps/src/staff.mmd](site-maps/src/staff.mmd))

The Staff Home leads to four areas:
- **My tasks.**
- **My events**, whose event panel is view only except for the status of the user's own tasks, and leads to document downloads and the PDF report.
- **The Schedule** (view only).
- **The user menu.**

This is the smallest site map, because Staff only follow and update their own work.

## User journey maps

Each journey map follows one kind of user across the stages of their work. Every map has the same layout:
- **Header:** who the user is, their goal, and the scenario.
- **One column per stage,** with rows for the user's actions, the touchpoint they use, a representative thought, their feeling, the pain point, and an opportunity.
- **A feelings curve** running across the stages, from very negative (bottom) to very positive (top).

The first map shows the EMO's current process (As-Is). The other three show each role's journey with EMO Tracker (To-Be). Their thoughts and feelings are the expected experience, to be confirmed with EMO users during the pilot.

### User Journey Map of the Current Process (As-Is)

**File:** [journey-maps/1-as-is-current-process.png](journey-maps/1-as-is-current-process.png)

The As-Is map describes how EMO members keep the schedule and prepare events before EMO Tracker. It is based on the analysis of the office's 2026 schedule sheet and the problems identified in Capstone 1. It follows seven stages, from receiving a request to reporting after an event.

- **Pain points (all documented):**
  - overlapping bookings are found only by noticing them (13 pairs in the 2026 sheet)
  - venues typed freely, giving about 80 spellings for about 25 places
  - reschedules and cancellations hidden in text
  - no single place that tracks tasks and who does them
  - no objective way to tell which events need attention
  - reports made by hand
- **Feelings curve:** stays at or below neutral throughout, and reaches its lowest point at tracking readiness.
- **Last row:** instead of opportunities, it names the EMO Tracker feature that addresses each pain point.
- **Limits:** items marked with an asterisk, such as how requests arrive and how work is assigned today, are to be confirmed with the EMO. The feelings are inferred from the documented problems rather than observed.

This map supports the need for the system: each of its pain points corresponds to a feature in the To-Be journeys that follow.

### User Journey Map of the Administrator (To-Be)

**File:** [journey-maps/2-administrator.png](journey-maps/2-administrator.png)

This map shows the Administrator's expected journey with EMO Tracker across eight stages: logging in, checking the Home page, recording events, spotting overlaps, handling changes, managing venues, managing accounts, and reviewing and reporting.

- **Feelings curve:** stays above neutral for most of the day, and peaks when reports and statistics are ready without being compiled by hand.
- **Dips to neutral** at two stages:
  - when an overlap warning appears, because settling the clash still requires contacting the other party
  - when managing accounts, because the Administrator resets every forgotten password
- **Opportunities** for future work that would address the remaining pain points:
  - refreshing the Home page automatically
  - notifying task owners when an event changes
  - showing the other booking's department in the overlap warning
  - self-service password reset (which needs email)
  - an audit trail of who changed what

### User Journey Map of the Officer (To-Be)

**File:** [journey-maps/3-officer.png](journey-maps/3-officer.png)

This map shows the Officer's expected journey while preparing an event, across eight stages: logging in, checking the Home page, checking the Schedule, preparing the event's tasks, uploading documents, monitoring readiness, reporting, and closing the event.

- **Feelings curve:** rises as tasks are assigned to owners and documents are stored with the event, and peaks when the report is generated in one step.
- **Dips to neutral** at two stages:
  - checking the Schedule, because changes to an event's date, time or venue go through the Administrator
  - monitoring readiness, because a Critical label still needs people to act on it
- **Opportunities:**
  - refreshing screens automatically
  - requesting schedule changes inside the system
  - notifying owners of new tasks
  - alerting the team when an event turns Critical

### User Journey Map of the Staff (To-Be)

**File:** [journey-maps/4-staff.png](journey-maps/4-staff.png)

This map shows a Staff member's expected journey while working on an assigned task, often from a phone. It covers seven stages: logging in, viewing the Home page, reviewing their tasks, checking events, updating progress, getting files, and finishing the task.

- **Feelings curve:** starts at neutral, because a forgotten password has to wait for the Administrator. It stays positive afterwards, and peaks when progress is updated and the task is finished.
- **Remaining pain points:**
  - there is no place inside the system to ask about a task
  - other users see an update only after their page reloads
  - Staff cannot upload files, so they pass them to an Officer
- **Opportunities:** comments on tasks, automatic refresh, file attachments by task owners, and self-service password reset.

## UI wireframes

The wireframes are low-fidelity, grayscale layouts of the main screens of each role. Gray bars stand for text, while headings and button labels use the system's real wording. They show how each screen is arranged without the colors and data of the finished system (shown in the screenshots).
- **Desktop layout:** a header with the system name and the user's menu, a sidebar of pages, and the page content.
- **Phone layout:** the sidebar becomes a bar at the bottom of the screen.

### UI Wireframes: Administrator

**File:** [wireframes/1-administrator.png](wireframes/1-administrator.png) (source: [wireframes/src/wireframes.html](wireframes/src/wireframes.html))

Four desktop screens:
- **A1. Home:** the summary sentence, the "Needs attention" list with readiness labels, and the "Next 7 days" list.
- **A2. Schedule:** the year tabs, the search box, and the buttons to export to Excel, manage venues and add events. Rows are grouped by month and shaded by building, with labels for overlaps and readiness and struck-through cancellations.
- **A3. New event form:** the event name, type, department, date and times, venue and room fields, with the overlap warning that appears while they are filled in. (The real form also has optional description, control number and remarks fields below these.)
- **A4. Accounts:** filters by role and status, and the accounts table with edit and deactivate actions.

### UI Wireframes: Officer

**File:** [wireframes/2-officer.png](wireframes/2-officer.png) (source: [wireframes/src/wireframes.html](wireframes/src/wireframes.html))

Four desktop screens:
- **O1. Events (calendar view):** a month calendar whose events are shaded by readiness, with switches for calendar or list and EMO-prepared or all events.
- **O2. Event panel:** opened from the Schedule. It shows the readiness label and reason, the event details, the tasks with their status menus, the documents with an upload link, and the PDF report link.
- **O3. New task form:** name, description, due date, priority, status and owner.
- **O4. Analytics:** the period switch, the event counts by status plus the rescheduled count, the readiness distribution chart and the most urgent events.

### UI Wireframes: Staff

**File:** [wireframes/3-staff.png](wireframes/3-staff.png) (source: [wireframes/src/wireframes.html](wireframes/src/wireframes.html))

Four phone screens, because Staff often update their tasks from a phone:
- **S1. Login:** the sign-in form shared by every role.
- **S2. Home:** the summary, the open tasks with a status menu, and the upcoming events, with the bottom navigation bar.
- **S3. My tasks:** the task counts, the filters (Open, All, Pending, In progress, Done), and each task with its status menu.
- **S4. Event panel (view only):** the event's details, people and tasks. Only the user's own tasks have a status menu.

## Gantt chart

### Gantt Chart

**File:** [diagrams/gantt-chart.png](diagrams/gantt-chart.png) (source: [diagrams/src/gantt-chart.mmd](diagrams/src/gantt-chart.mmd))

The Gantt chart shows the Capstone 2 project timeline from September to November 2026, continuing the Capstone 1 timeline (February to June 2026). It uses the same phases as the Capstone 1 chart, plus implementation and evaluation:

| Phase | Activities |
|---|---|
| Planning and Analysis | Capstone 2 planning and system review (September 1 to 28); requirements gathering with the EMO (September 28 to October 7) |
| System Design | Database and UI/UX design updates for the Schedule, venues and statuses (October 1 to 8) |
| Development | Security hardening and automated testing (September 28 to October 2); prototype refinement for the Schedule and venues (October 2 to 6), then for statuses and overlaps (October 6 to 8); pre-defense audit and UI polish (October 7 to 12) |
| Defense and Revision | Final defense preparation (October 26 to November 5); the Capstone 2 final defense in the first week of November (shown as a milestone); revisions based on the panel's feedback (November 5 to 19) |
| Documentation and Finalization | Documentation and manuscript writing (October 7 to November 5); final checking by the panel (November 19 to 26) |
| Implementation and Evaluation | Installation at the EMO and pilot testing (November 9 to 23); user evaluation by survey (November 23 to 28) |

Bars are activities and the diamond is the defense milestone. Development overlaps with requirements and design because the system was built with the Prototyping Model: each consultation with the EMO led directly to a new round of refinement.
