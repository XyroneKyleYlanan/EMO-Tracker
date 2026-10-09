# Screenshot Descriptions

Descriptions of the 26 EMO Tracker screenshots for the **appendices** (and for Chapter 4, where screens are used as evidence) of the Capstone 2 paper. The images are in the [`screenshots/`](screenshots/) folder.

- **Order:** one folder per role. Inside each folder, the screens follow the order a user of that role goes through them; the numbers run across all folders.
- **Data:** every screenshot was taken on October 9, 2026, with the system's **demo data**. The names are fictional demo accounts, never the EMO's real data.
- **Dates:** the dates in the screenshots are relative to that day. "Today" is Friday, October 9, 2026.
- **Size:** desktop screenshots are 1440 × 900 pixels; phone screenshots are 390 × 844 at 2× resolution.
- **Numbering:** renumber the figures to match the appendix, for example "Figure G.1". Each entry gives a suggested caption, the file, and a description.

---

## 1. All roles

Screens shared by every user: signing in and managing one's own password.

### 01. Login Page

**File:** [screenshots/1-all-roles/01-login.png](screenshots/1-all-roles/01-login.png)

The login page is the entry point for all users. It shows the New Era University seal, the system name and the office it serves, followed by email and password fields and a Sign in button. The system has no public registration: every account is created by the Administrator. After signing in, each user is taken to the Home page for their role.

### 02. Login Page After a Wrong Password

**File:** [screenshots/1-all-roles/02-login-error.png](screenshots/1-all-roles/02-login-error.png)

This screen shows the login page after an incorrect password was entered. The form displays the message "The provided credentials are incorrect." The same message appears whether the email or the password is wrong, so the page does not reveal which accounts exist. The system also limits sign-in attempts to ten per minute for each email address and device, which protects accounts against password guessing.

### 03. Change Password Dialog

**File:** [screenshots/1-all-roles/03-change-password.png](screenshots/1-all-roles/03-change-password.png)

Every user can change their own password from the account menu at the top right of any page; the example shows a Staff member. The dialog requires:
- the current password
- a new password of at least eight characters
- a confirmation of the new password

As the dialog explains, changing the password signs the user out on their other devices, so a lost or shared session cannot continue.

---

## 2. Administrator

The Administrator manages the schedule, venues and user accounts, and can do everything an Officer can.

### 04. Administrator's Home Page

**File:** [screenshots/2-administrator/04-home.png](screenshots/2-administrator/04-home.png)

The Home page opens with a one-sentence summary of the EMO's workload: how many EMO-prepared events need attention, how many tasks are still open and done, and how many events are on the schedule in the next seven days.

- **Needs attention:** lists the events that are At Risk or Critical. Each shows its readiness label, the reason for it (for example, "4 tasks still open, event is tomorrow") and its task progress.
- **Next 7 days:** lists every scheduled event by day, marked with its building's color and its current status, such as Ongoing or Completed.
- **Backup status:** shown below the panels, outside the visible area, with a warning if the last automatic backup failed.

### 05. Events Page, Calendar View

**File:** [screenshots/2-administrator/05-events-calendar.png](screenshots/2-administrator/05-events-calendar.png)

The Events page shows the events in a monthly calendar. Each event is colored by its readiness: red for Critical and orange for At Risk. Today's date is marked with a green circle. Users can switch between Calendar and List views and between EMO-prepared events and all events. The Administrator also sees the "Add event" button.

### 06. Schedule Page

**File:** [screenshots/2-administrator/06-schedule.png](screenshots/2-administrator/06-schedule.png)

The Schedule page reproduces the layout of the spreadsheet the EMO used before the system, so the office could move to it without relearning its records.

- **Layout:** one tab per year; columns for date, time, event, type, department, venue, control number and remarks; rows grouped by month and colored by the venue's building.
- **Labels on the rows:** an event's status (Ongoing), its readiness when the EMO prepares it, venue overlaps and external organizers. A rescheduled event notes its original date, for example "Rescheduled from Sat, Sep 26".
- **Actions:** a search box finds any event. The Administrator can export the Schedule to Excel, manage venues and add events from this page.

### 07. New Event Form With a Double-Booking Warning

**File:** [screenshots/2-administrator/07-new-event-overlap-warning.png](screenshots/2-administrator/07-new-event-overlap-warning.png)

The Administrator adds an event with this form, which collects:
- the event name and type (internal or external) and the department
- the date, with an option for events that run several days
- the start and end times (optional)
- a venue from the managed list, and the room

In the example, the chosen venue and time clash with "Faculty Recognition Night" at PSB MPH. The form checks for overlaps as the details are entered, and shows a warning that names the clashing booking. A short note next to the Add event button repeats it. As the warning says, the event can still be saved, because some overlaps are intentional.

### 08. Rescheduling Question When Editing an Event

**File:** [screenshots/2-administrator/08-reschedule-question.png](screenshots/2-administrator/08-reschedule-question.png)

When the Administrator changes the start date or time of an event, the edit form asks whether the event was rescheduled.
- **"Yes, it was moved":** the event keeps a note of its original slot. In the example it would show "Rescheduled from Sun, Nov 8".
- **"No, I'm correcting a mistake":** the date is simply fixed, with no note.

This distinction keeps the schedule's history accurate, and the Analytics page counts rescheduled events separately.

### 09. Event Details Panel

**File:** [screenshots/2-administrator/09-event-details.png](screenshots/2-administrator/09-event-details.png)

Selecting any event opens its details panel.
- **Readiness:** the label and its reason at the top, here At Risk because the event is "40% done, 5 days to go".
- **Details:** the event's description, date, time, venue (with its building), type, department and creator.
- **People:** the event's people, who are the owners of its tasks.
- **Tasks:** each task shows its owner, due date, priority and status.

At the bottom, the Administrator can download the event's PDF report, delete the event or edit it.

### 10. A Cancelled Event

**File:** [screenshots/2-administrator/10-cancelled-event.png](screenshots/2-administrator/10-cancelled-event.png)

A cancelled event stays on the Schedule, struck through and labeled Cancelled, so the office keeps a record of it. Its panel explains that its tasks are on hold and that the Administrator can restore it by editing the event. Cancelled events are left out of readiness, overlap checks and venue statistics.

### 11. Venues Page

**File:** [screenshots/2-administrator/11-venues.png](screenshots/2-administrator/11-venues.png)

The Venues page, opened from the Schedule, manages the list of venues used for bookings and overlap checks. Venues are grouped by building, and each building's color is the one used for its rows on the Schedule. Each venue shows how many events use it. The Administrator can:
- add and edit buildings and venues
- merge duplicate venues
- delete unused venues (a venue that events use cannot be deleted, which protects existing records)

### 12. Accounts Page

**File:** [screenshots/2-administrator/12-accounts.png](screenshots/2-administrator/12-accounts.png)

The Accounts page lists every user with their email, role and status, and a count of accounts by role. The list can be filtered by role and by whether accounts are active or deactivated. Accounts are deactivated rather than deleted, so the records a person created keep their author. The Administrator's own account is marked "You" and cannot be deactivated.

### 13. Adding an Account

**File:** [screenshots/2-administrator/13-add-account.png](screenshots/2-administrator/13-add-account.png)

The Administrator creates accounts with this form: full name, email, a password of at least eight characters, and a role (Administrator, Officer or Staff). The role decides what the person can see and do. Because only the Administrator can create accounts, access to the system stays limited to the EMO's members.

### 14. Events List on a Phone

**File:** [screenshots/2-administrator/14-events-list-phone.png](screenshots/2-administrator/14-events-list-phone.png)

The system is responsive, so it can also be used on a phone connected to the office Wi-Fi. On a small screen:
- the Events page shows a list of cards, with upcoming events first
- each card gives the event's status or readiness, date, time, venue, people and task progress
- a bottom navigation bar replaces the sidebar

### 15. Schedule on a Phone

**File:** [screenshots/2-administrator/15-schedule-phone.png](screenshots/2-administrator/15-schedule-phone.png)

On a phone, the Schedule shows each event as a card instead of a table row. The cards keep the building colors and are grouped by month. Each card gives the event's name, date, time, venue, department and type. The view shown is the Administrator's, with the buttons to export, manage venues and add events.

---

## 3. Officer

The Officer prepares events: managing tasks, uploading documents, and monitoring readiness and analytics.

### 16. Officer's Home Page

**File:** [screenshots/3-officer/16-home.png](screenshots/3-officer/16-home.png)

The Officer's Home page has the same summary, Needs attention list and Next 7 days list as the Administrator's, without the backup status. The sidebar shows the Officer's pages: Home, My tasks, Events, Schedule and Analytics. Account management is not available to this role.

### 17. Adding a Task

**File:** [screenshots/3-officer/17-add-task.png](screenshots/3-officer/17-add-task.png)

From an event's panel, the Officer adds a task with:
- a name and an optional description
- a due date
- a priority and a status
- an owner, chosen from the active members

Each task has at most one owner, which keeps responsibility clear. Saving a task updates the event's readiness right away.

### 18. Uploading a Document to an Event

**File:** [screenshots/3-officer/18-documents.png](screenshots/3-officer/18-documents.png)

This screen shows the Documents section of an event's panel just after the Officer uploaded a file, confirmed by the message at the top.
- **What each document shows:** its file type, size, uploader and upload date, with a download button.
- **Accepted files:** PDF, Word, Excel, JPG and PNG, up to 10 MB.
- **Storage and access:** files are kept in private storage and can be downloaded only by signed-in users.
- **Deletion:** as the note says, only the Administrator can delete files, so documents remain part of the event's record.

### 19. A Completed Event With Locked Tasks

**File:** [screenshots/3-officer/19-completed-event.png](screenshots/3-officer/19-completed-event.png)

Once an event ends, the system marks it Completed and locks its tasks, which become a record of the event. The panel explains that the task records are locked and that an administrator can make corrections. Each task shows its final status with a "Locked" mark. The Schedule behind the panel is marked "View only", because only the Administrator changes event details.

### 20. Analytics Page

**File:** [screenshots/3-officer/20-analytics.png](screenshots/3-officer/20-analytics.png)

The Analytics page summarizes the schedule and the EMO's preparation for the period chosen at the top right (this week, this month, this year or all time).

- **Events:** counts the events by status (these add up to all events) and the rescheduled events. It also compares internal and external events and ranks the busiest venues.
- **EMO preparation:** counts the prepared events, their tasks and how many are done, and the people with tasks. It shows the readiness distribution as a chart and lists the most urgent events.
- **Different counts:** this page counts the whole year, including completed events, so it reports five prepared events, while the Home page counts only the four that are not yet over. For the same reason, "Top 5 most urgent events" lists four.

---

## 4. Staff

Staff members follow their assigned tasks and update their progress.

### 21. Staff Home Page on a Phone

**File:** [screenshots/4-staff/21-home-phone.png](screenshots/4-staff/21-home-phone.png)

A Staff member's Home page opens with a summary of their own work: open and finished tasks, and the upcoming events they have tasks on. It then lists their open tasks and their upcoming events. A task's status can be changed directly from this page, which suits quick updates from a phone. This page counts only tasks on events that are not yet over; the My tasks page counts all of them.

### 22. My Tasks Page

**File:** [screenshots/4-staff/22-my-tasks.png](screenshots/4-staff/22-my-tasks.png)

The My tasks page, available to every role, lists the tasks assigned to the user across all events. At the top are counts of total, pending, in-progress and done tasks. Filters show open, pending, in-progress, done or all tasks. Each task shows its event, due date and priority, and the owner can update its status from the list.

### 23. My Events Page

**File:** [screenshots/4-staff/23-my-events.png](screenshots/4-staff/23-my-events.png)

For a Staff member, the Events page, labeled "My events", shows only the events where they have tasks. Each event card shows its readiness, date, time, venue and task progress, and past events are behind the "Show past events" link. As the page notes, the Schedule still shows every event, so staff can see the whole calendar.

### 24. Event Details Panel for Staff (View Only)

**File:** [screenshots/4-staff/24-event-details-view-only.png](screenshots/4-staff/24-event-details-view-only.png)

Staff can open any event's details, but the panel is view-only for them:
- there are no buttons to edit or delete the event or to add tasks
- only the status of the Staff member's own tasks can be changed (shown as drop-down menus); other tasks show their status as fixed labels
- the PDF report can still be downloaded

The backend enforces the same limits on every request, so they cannot be bypassed through the browser.

---

## 5. Reports

Files the system generates for records and sharing.

### 25. PDF Event Report

**File:** [screenshots/5-reports/25-pdf-report.png](screenshots/5-reports/25-pdf-report.png)

Any user can download an event's report as a PDF from its details panel; both pages are shown. The report contains:
- the event's readiness label and reason
- its details (date, time, venue, department, type, status, creator and description)
- a task summary (total, done, in progress and completion rate)
- the people involved and the full task list
- the attached documents with their uploaders and dates
- a footer recording when it was generated

The server generates the report, so it looks the same on every device.

### 26. Schedule Exported to Excel

**File:** [screenshots/5-reports/26-excel-export.png](screenshots/5-reports/26-excel-export.png)

The Administrator and Officers can export the Schedule as an Excel file in the same layout as the spreadsheet the EMO used before the system: a year title, then columns for date, time, event, type, department, venue, control number and remarks. Rows are colored by building, cancelled events are struck through, and reschedules are noted in the remarks. The office can therefore keep sharing the schedule in its familiar format. The file is shown in a file preview; Arial is used in place of Excel's default font, Calibri, which the preview computer does not have.
