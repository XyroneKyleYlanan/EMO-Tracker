# Facts for Appendix E.9: User Manuals

**Reference only. Write the sentences yourselves** (the paper has to pass Turnitin). The facts below are checked against the system as of November 24, 2026. The tables (sidebar tabs, fields, buttons) aren't prose, so you can copy them as they are.

Button and field names are written **exactly as they appear on screen**, so the manual matches what the EMO sees.

---

## Part A. What's wrong in the current manual (Appendices PDF, pp. 178–186)

| Current manual says | The system now |
|---|---|
| "EMD Tracker logo" (p. 178) | **EMO Tracker** logo (the NEU seal), "Events Management Office · New Era University" |
| Home shows Total Events, Total Tasks, Tasks Done, Active Staff, "Four Quick Action shortcuts" (Admin), "Two Quick Action shortcuts" (Officer) | No stat cards and no shortcuts on the Administrator/Officer Home. Home shows a greeting, the date and time, a one-line summary, **Needs attention**, **Next 7 days** and **My open tasks** (see Step 2) |
| Sidebar: Home, Staff, Events, Analytics (Admin); Home, Events, Analytics (Officer); Home, My Tasks, My Events (Staff) | Admin: Home, My tasks, Events, Schedule, Analytics, Accounts. Officer: Home, My tasks, Events, Schedule, Analytics. Staff: Home, My tasks, My events, Schedule |
| "Staff Management Page", "New User" button, "Create User" | Page is **Accounts**, button **+ Add account**, save button **Add account** |
| Log out shows "A confirmation prompt" | No prompt: **Log out** signs out right away and returns to the login page |
| Event form has Name, Venue, Date, Time, **Budget**, **Assigned Staff** checkboxes, "Create Event" | No Budget and no Assigned Staff in the form (people come from the tasks). Fields are in Step 4, and the save button is **Add event** |
| Event Details drawer shows budget and assigned staff; "Generate Report" button | Panel shows **People** (from tasks), no budget; button is **Download report** |
| Officers can create events and delete documents | Only the **Administrator** adds, edits and deletes events and deletes documents. Officers manage tasks, upload files, download reports and export the Schedule |
| Task form: "Assigned Staff", "Create Task" | Field is **Assigned to** (any active member), button **Add task** |
| Analytics: Total Events, Total Tasks, Tasks Done, Active Staff; This Week / This Month / All Time | Period: **This week, This month, This year, All time**. Two parts: **Events** and **EMO preparation** (see Step 5) |
| Staff: "contact your Officer or Administrator to reset it" | Only the **Administrator** can set a new password for someone (in Accounts, Edit) |
| Staff Home: four stat cards Pending, In Progress, Done, My Events | Staff Home has a one-line summary, **My open tasks** (with status menus) and **My upcoming events**. The four count cards are on **My tasks** (Total, Pending, In progress, Done) |
| No Schedule, Venues, filters, Export, overlap warning or reschedule question | All of these exist now (Steps 4 to 7) |

---

## Part B. The same for every role

### Login page
- Fields: **Email** (example shown: you@neu.edu.ph), **Password**; button **Sign in**. Note under the card: "Internal use only".
- **Eye** at the right of the password box: shows or hides what was typed (helps on a phone).
- After signing in, each role goes to its own Home page.
- Messages:

| Situation | Message on screen |
|---|---|
| Wrong email or password | The provided credentials are incorrect. |
| Account deactivated by the Administrator | This account has been deactivated. |
| 10 wrong tries within a minute (same account, same device) | Too many login attempts. Wait a minute, then try again. |
| Server not running or not reachable | Can't reach the EMO Tracker server. Make sure the app is running, then try again. |

- No "forgot password" link and no self-registration: the Administrator creates accounts and can set a new password.

### Account menu (top right: name, role badge, initials circle)
- Click it to see: name and email, **Change password**, **Log out**.
- **Change password** dialog fields: **Current password**, **New password** (minimum 8 characters; your other devices will be signed out), **Confirm new password**. Each box has an eye. Buttons: **Cancel**, **Change password**. Success message: "Password changed. Your other devices have been signed out."
- **Log out**: signs out right away (no confirmation) and returns to the login page.

### On a phone
- Same pages; the menu moves to the **bottom of the screen**; the Schedule shows cards instead of a table.

### My tasks page (every role has it)
- Count cards: **Total, Pending, In progress, Done**.
- Filters: **Open, All, Pending, In progress, Done**.
- Each task: task name, its event, who it's assigned to, due date, priority (Low, Medium, High), and a **status menu** (Pending, In progress, Done).
- Click a task to open its **task details**.
- Tasks of a **completed** event are locked: "This event is completed, so this task is locked. Contact an administrator if a correction is needed." (The Administrator can still change them; the app asks to confirm first.)

### Event panel (opens when you click an event anywhere)
- Top: event name, readiness label (**On Track / At Risk / Critical**, only for events the EMO prepares) with its **reason** (for example "60% done, 14 days to go"), status labels (Ongoing, Cancelled, Rescheduled from …).
- Details: date and time, venue (with its building color), type (Internal or External), department or organizer, who created it.
- **People (n)**: everyone with a task in this event.
- **Overlapping bookings**: other events at the same venue and time, if any.
- **Tasks (n)** and **Documents (n)** sections.
- **Download report**: a PDF with the event name, Event Details, Task Summary, People, a Tasks table (Task, Assignee, Due, Priority, Status), Attached Documents, and a "Generated on …" line.
- Close with the **×** or the Esc key.

### Schedule page (every role has it)
- Shows **every NEU event and venue booking**, like the EMO's spreadsheet. Officers and Staff see "View only".
- **Year tabs** (past years in a menu).
- Filters: type (**All, Internal, External**), **Month** (All months or one month), **Venue** (venues grouped by building, plus "Other / not listed"). A chosen filter turns green.
- **Search schedule** box.
- Rows grouped by month ("November 2026 · 22 events"), each row **colored by its building**. Columns: Date, Time, Event, Type, Department, Venue, Control #, Remarks. Labels such as Ongoing, Cancelled, Overlap, and the readiness label.
- Click a row to open the **event panel**.

### Readiness labels

| Label | Color | Meaning (the system checks these in order; the first that fits decides) |
|---|---|---|
| (none) | — | Cancelled and completed events, and events the EMO doesn't prepare |
| At Risk | Amber | No tasks yet |
| On Track | Green | All tasks done |
| Critical | Red | A task is overdue; or 2 days or less left with a task still open; or 7 days or less left and under 40% done; or 7 days or less left and more than half of the open tasks have no owner |
| At Risk | Amber | 14 days or less left and under 70% done; or an open task has no owner |
| On Track | Green | Everything else ("…% done, nothing overdue") |

---

## Part C. Administrator

**Sidebar tabs**

| Tab | What it opens |
|---|---|
| Home | Summary, events that need attention, the next 7 days, your open tasks, backup status |
| My tasks | The tasks assigned to you |
| Events | All events in a calendar or a list; add events |
| Schedule | The whole schedule by year, like the EMO's spreadsheet; export; manage venues |
| Analytics | Event counts and how ready the EMO's events are, for a chosen period |
| Accounts | Everyone's accounts: add, edit, deactivate, reactivate |

**Step 2. Home**
- "Hello, EMO Administrator", the date and time, and a one-line summary of the EMO-prepared events and open tasks.
- **Needs attention**: EMO-prepared events that are Critical or At Risk, each with its reason and "x/y tasks done" (link: All events). If none: "Nothing needs attention. Every EMO-prepared event is on track."
- **Next 7 days**: everything on the schedule this week (link: Open schedule).
- **My open tasks** (link: View all).
- Bottom line: **backup status** ("Last backup: …"; a yellow warning if a backup failed).

**Step 3. Events page**
- **Calendar view** or **List view** (upcoming first, then past).
- Filter: **EMO-prepared** or **All events**.
- **+ Add event** opens the event form (fields below).

**Step 4. Adding or editing an event** (also from the Schedule's **+ Add event**)

| Field | Notes |
|---|---|
| Event | Name |
| Type | **Internal** (an NEU event) or **External** (organized by an outside group) |
| Department (optional) | Becomes **Organizer** for external events |
| Date | Check **Runs for several days** to add an **Until** date |
| Start time / End time (optional) | Type it like **1:00 PM**, **1pm** or **13:00**. A time without AM or PM is not accepted. **Clear** empties it |
| Venue | List grouped by building; **Other / not listed** to type a place; **+ Add a new venue…** to add one (name and building) |
| Room / details (optional) | For example "504–507, 2nd floor lobby" |
| The EMO prepares this event | Check it to track readiness (On Track / At Risk / Critical) |
| Description, Control #, Remarks (optional) | Remarks are short notes |
| This event is cancelled | Edit form only; the event stays on the schedule, struck through |

- **Overlap warning**: if the venue is already booked at that time: "This overlaps with another booking at this venue. You can still save it." It names the other booking.
- **Reschedule question** (editing only, when the date or start time changes): "The start date or time changed. Was the event rescheduled?" **Yes, it was moved** (shows "Rescheduled from …") or **No, I'm correcting a mistake**.
- Buttons: **Cancel**, **Add event** / **Save changes**.
- In the event panel: **Edit event** (also for completed events) and **Delete** (asks to confirm).

**Step 5. Tasks and documents (in the event panel)**
- **+ Add task** → task form: **Task name**, **Description** (optional), **Due date**, **Priority** (Low, Medium, High), **Status** (Pending, In progress, Done), **Assigned to** (optional; any active member, any role). Buttons **Cancel**, **Add task** / **Save changes**.
- Each task has a status menu, an **Edit task** icon and a delete icon (asks to confirm).
- **+ Upload file**: PDF, Word, Excel, JPG or PNG, up to 10 MB. Each file shows its size, who uploaded it and when; icons to **Download** and **Delete** (Delete asks to confirm; only the Administrator can delete).

**Step 6. Schedule page extras**
- **Export** menu: **Excel (.xlsx)** "With the building colors", or **CSV (.csv)** "Plain text, opens in any app". With a filter or search on, only the rows shown are exported ("Only the N events shown").
- **Manage venues** → Venues page: each building (in its color) with its venues. **+ Add building** (name and color), **+ Add venue** (name and building). Each building: **Edit**, **Remove**. Each venue: **Edit**, **Merge** (moves its events into another venue, for duplicate spellings), **Delete**. A venue that events use can't be deleted.
- **+ Add event**.

**Step 7. Analytics page**
- Period: **This week, This month, This year, All time** (events count by the day they start).
- **Events**: Upcoming, Ongoing, Completed, Cancelled, Rescheduled (moved); **Internal vs external**; **Most active venues**.
- **EMO preparation**: Prepared events, Total tasks, Tasks done (with %), People with tasks; **Readiness distribution** donut (On Track green, At Risk amber, Critical red, Completed gray); **Top 5 most urgent events**.

**Step 8. Accounts page**
- Header counts (for example "10 total · 1 admin · 2 officers · 7 staff"; with deactivated accounts: "3 active · … · 9 deactivated").
- Filters: **All roles, Admin, Officer, Staff** and **Active, Deactivated, All**.
- List: name ("You" on your own), email, role badge, status; **Edit**, **Deactivate** (asks: "Deactivate [name]? They will no longer be able to log in."), **Reactivate** on deactivated accounts.
- **+ Add account** → **Full name**, **Email**, **Password** (minimum 8 characters, with the eye), **Role** (Administrator, Officer, Staff) → **Add account**.
- **Edit**: same fields; **New password** is optional (leave blank to keep it; a new one signs them out everywhere); **Active** checkbox. You can't change your own role or deactivate yourself.
- Accounts are never deleted, so records keep their owners.

---

## Part D. Officer

**Sidebar tabs**

| Tab | What it opens |
|---|---|
| Home | Summary, events that need attention, the next 7 days, your open tasks |
| My tasks | The tasks assigned to you |
| Events | All events in a calendar or a list |
| Schedule | The whole schedule by year (view only); export |
| Analytics | Event counts and how ready the EMO's events are, for a chosen period |

- **Home**: same as the Administrator's, without the backup status.
- **Events**: Calendar or List, EMO-prepared / All events; **no + Add event**.
- **Event panel**: the event's own details are **view only** (no Edit event, no Delete). The Officer **can** add, edit and delete tasks, change task status, **+ Upload file**, download files and **Download report**. Can't delete files. Tasks of completed events are locked.
- **Schedule**: view only, with the **Export** menu (Excel or CSV). No Manage venues, no + Add event.
- **Analytics**: same as the Administrator's.
- No Accounts and no Venues page.

---

## Part E. Staff

**Sidebar tabs**

| Tab | What it opens |
|---|---|
| Home | Your open tasks and your upcoming events |
| My tasks | The tasks assigned to you |
| My events | The events where you have a task, in a calendar or a list |
| Schedule | The whole schedule by year (view only) |

- **Home**: "Hello, [name]", the date and time, a summary ("You have 2 open tasks (1 in progress) and 3 finished tasks. You have tasks on 1 upcoming event."), **My open tasks** with a status menu on each, **My upcoming events** with readiness label, date, time and place.
- **My tasks**: as in Part B; change your task's status from the menu.
- **My events**: Calendar or List; only events where you have a task.
- **Event panel**: view only, except the **status of your own tasks**; download files and **Download report**; can't upload or delete.
- **Schedule**: view only; no Export.
- To get a new password, ask the **Administrator**.
