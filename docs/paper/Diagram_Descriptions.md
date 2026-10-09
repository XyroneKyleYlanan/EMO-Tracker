# Diagram Descriptions

Descriptions of the eight system diagrams and the four user journey maps for **Chapter 3 (Methodology and System Design)** of the EMO Tracker Capstone 2 paper. The images are in the [`diagrams/`](diagrams/) and [`journey-maps/`](journey-maps/) folders. The entries below are in the suggested order for the chapter.

- **Figure numbers** assume these are the first figures in Chapter 3. Renumber them if other figures come first, such as a conceptual framework.
- **Accuracy:** every description was checked against the system's code as of October 9, 2026. It uses the system's own terms: Administrator, Officer, Staff; On Track, At Risk, Critical; Upcoming, Ongoing, Completed, Cancelled.
- **Sources:** the drawing code for six of the diagrams is in [CAPSTONE_PAPER_REFERENCE.md](CAPSTONE_PAPER_REFERENCE.md) (Part E). The use case and context diagrams are SVG files in [`diagrams/src/`](diagrams/src/). The journey maps come from [`journey-maps/src/journey-maps.html`](journey-maps/src/journey-maps.html); the same content is in [`tables/user-journey-map.html`](tables/user-journey-map.html) as tables that can be edited in Word.

---

### Figure 3.1. System Architecture of EMO Tracker

**File:** [diagrams/architecture.png](diagrams/architecture.png)

EMO Tracker follows a three-tier client–server architecture installed on a single laptop on the Events Management Office's local network.

- **Presentation tier:** users open the system in a web browser, either on the server laptop itself (`localhost:5173`) or from phones and other laptops on the same Wi-Fi network (the laptop's IP address, port 5173). The frontend server (Vite) sends the React single-page application to the browser. It also forwards every request whose path begins with `/api` to the application tier.
- **Application tier:** a Laravel REST API that listens only on the laptop itself (`127.0.0.1:8000`), so no other device can reach it directly. It enforces authentication, role-based access and every business rule, including readiness classification, venue overlap detection and event status changes.
- **Data tier:** a MySQL 8.4 database, a private storage area for uploaded documents, and a backup location. Each day the system writes a backup of both the database and the documents there; the location can be a folder on the laptop or an external USB drive.

The system runs entirely on the local network and uses no external services or cloud hosting.

### Figure 3.2. Context Diagram (Level 0 Data Flow Diagram)

**File:** [diagrams/context-diagram.png](diagrams/context-diagram.png) (source: [diagrams/src/context-diagram.svg](diagrams/src/context-diagram.svg))

The context diagram presents EMO Tracker as a single process, Process 0, and shows the data exchanged with the five external entities that interact with it. It uses Yourdon–DeMarco notation: the circle is the process, the rectangles are external entities, and each labeled arrow is a data flow.

- **Administrator:** supplies login details; event, venue, building and account details; and tasks and documents. Receives the schedule with readiness labels, analytics, PDF reports and the Excel export, and the backup status.
- **Officer:** supplies login details, tasks with their assignments, and documents. Receives the schedule and readiness labels, analytics, documents, and PDF reports and the Excel export.
- **Staff:** supply login details and status updates for their own tasks. Receive the schedule and event details, their assigned tasks, and documents and PDF reports.
- **The EMO's schedule spreadsheet:** a one-time source of schedule rows, imported from the command line when the system is installed.
- **Backup storage:** receives a daily backup of the database and the uploaded documents.

### Figure 3.3. Use Case Diagram

**File:** [diagrams/use-cases.png](diagrams/use-cases.png) (source: [diagrams/src/use-cases.svg](diagrams/src/use-cases.svg))

The use case diagram, drawn in UML notation, shows three actors and the functions each can perform within the system boundary.

- **Generalization:** the actors form a hierarchy, shown by solid lines with hollow triangles. An Officer inherits every use case of Staff, and an Administrator inherits every use case of the Officer.
- **Staff** (and therefore every user) can log in and log out; change their own password; view the Home page, Events and Schedule; view event details; download documents and PDF reports; view their own tasks; and update the status of their own tasks.
- **The Officer** also manages tasks (adds, edits, deletes and assigns them), uploads documents, views Analytics and exports the Schedule to Excel.
- **The Administrator** also adds and edits events; cancels, restores or deletes events; manages venues and buildings; manages user accounts; deletes documents; and views the backup status.
- **«include»:** adding or editing an event always includes the venue overlap check.
- **«extend»:** when an event's start date or time changes, editing it can be extended by recording a reschedule.

The backend checks these permissions on every request, so the restrictions also hold for requests made outside the user interface.

### Figure 3.4. Entity Relationship Diagram

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

### Figure 3.5. Rule-Based Readiness Classification Flowchart

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

### Figure 3.6. Venue Double-Booking Check Flowchart

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

### Figure 3.7. Event Status Lifecycle (State Diagram)

**File:** [diagrams/status-lifecycle.png](diagrams/status-lifecycle.png)

The state diagram shows the statuses an event passes through and what causes each change.

- **Stored and computed statuses:** the system stores three statuses: Upcoming, Completed and Cancelled. Ongoing is not stored; it is computed while an Upcoming event is taking place. Upcoming and Ongoing are therefore drawn together as one composite state, "Not over yet".
- **Over time:** an event added with a future date enters this state as Upcoming. It becomes Ongoing when the start time on its first day arrives, and Completed once the end time of its last day passes. An event added or imported with a past date starts as Completed.
- **Date changes:** changing an event's dates recalculates its status. An Administrator can return a Completed event to the composite state by moving it to a future date, or complete an event by moving its date into the past.
- **Cancelling:** an Administrator can cancel an event from any other status. A cancelled event stays on the Schedule; restoring it returns it to the composite state if it is still ahead, or to Completed if it is already over.
- **Deleting:** an Administrator can delete an event in any state.

Completion is applied automatically at the start of every request to the system, so a finished event's tasks are locked even if no one opens the event. A reschedule is recorded as a mark on the event (its original date and time), not as a separate status.

### Figure 3.8. Login and Request Sequence Diagram

**File:** [diagrams/login-sequence.png](diagrams/login-sequence.png)

The sequence diagram traces a login and one authenticated request through five participants: the user, the browser running the React application, the Vite frontend server, the Laravel API and the MySQL database.

1. **Login request:** the browser sends the email and password to the frontend server, which forwards the request to Laravel with the address of the user's device.
2. **Checks:** Laravel applies the login limit of ten attempts per minute for each email address and device. It then retrieves the user record from the database and verifies both the password hash and that the account is active.
3. **Three possible outcomes:**
   - **Correct credentials, active account:** Laravel stores a new access token that expires in 30 days and returns it with the user's name and role. The browser keeps the token and opens the Home page for that role.
   - **Wrong email or password, or a deactivated account:** Laravel returns an error (HTTP 422) whose reason the login form displays.
   - **More than ten attempts in a minute:** Laravel returns HTTP 429, and the form shows a "too many attempts" message.
4. **Later requests:** every later request carries the token. The diagram uses the Administrator's Home page as the example. Laravel marks finished events as completed (it does this on every request), looks up the token, and checks that the user's role may use the route. It then reads the events and tasks and returns JSON through the frontend server to the browser, which displays the Home page.

---

## User journey maps

Each journey map follows one kind of user across the stages of their work. Every map has the same layout:
- **Header:** who the user is, their goal, and the scenario.
- **One column per stage,** with rows for the user's actions, the touchpoint they use, a representative thought, their feeling, the pain point, and an opportunity.
- **A feelings curve** running across the stages, from very negative (bottom) to very positive (top).

The first map shows the EMO's current process (As-Is). The other three show each role's journey with EMO Tracker (To-Be). Their thoughts and feelings are the expected experience, to be confirmed with EMO users during the pilot.

### Figure 3.9. User Journey Map of the Current Process (As-Is)

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

### Figure 3.10. User Journey Map of the Administrator (To-Be)

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

### Figure 3.11. User Journey Map of the Officer (To-Be)

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

### Figure 3.12. User Journey Map of the Staff (To-Be)

**File:** [journey-maps/4-staff.png](journey-maps/4-staff.png)

This map shows a Staff member's expected journey while working on an assigned task, often from a phone. It covers seven stages: logging in, viewing the Home page, reviewing their tasks, checking events, updating progress, getting files, and finishing the task.

- **Feelings curve:** starts at neutral, because a forgotten password has to wait for the Administrator. It stays positive afterwards, and peaks when progress is updated and the task is finished.
- **Remaining pain points:**
  - there is no place inside the system to ask about a task
  - other users see an update only after their page reloads
  - Staff cannot upload files, so they pass them to an Officer
- **Opportunities:** comments on tasks, automatic refresh, file attachments by task owners, and self-service password reset.
