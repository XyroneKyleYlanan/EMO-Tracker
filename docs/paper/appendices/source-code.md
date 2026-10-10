# Source Code (Key Modules)

For Appendix D (Technical Documentation) of the Capstone 2 paper, in the same layout as Capstone 1. A copy-ready version for Google Docs or Word is in [source-code.html](source-code.html) (open it in a browser, copy, paste).

The modules are those of the current system: the Capstone 1 modules with their current code, plus Modules 8 to 10 for the parts added in Capstone 2. Code is copied from the repository (October 10, 2026) with its file and line numbers. The complete source code is at https://github.com/XyroneKyleYlanan/EMO-Tracker

| # | Module | Primary File |
|---|---|---|
| 1 | Login and Authentication | `Backend: AuthController.php, AppServiceProvider.php`<br>`Frontend: LoginPage.jsx, AuthContext.jsx, api.js` |
| 2 | Event Planning and Scheduling | `Backend: EventController.php, Event.php (model)`<br>`Frontend: EventsPage.jsx, EventCalendarView.jsx, EventListView.jsx, EventFormDialog.jsx` |
| 3 | Task Assignment and Tracking | `Backend: TaskController.php, AssignableMember.php (validation rule)`<br>`Frontend: TaskRow.jsx, TaskFormDialog.jsx, StaffTasksPage.jsx` |
| 4 | Rule-Based Event Readiness Classification | `Backend: EventClassifier.php (service), AnalyticsController.php`<br>`Frontend: AnalyticsPage.jsx, ReadinessDonut.jsx, ReadinessBadge.jsx` |
| 5 | Reports and Document Management | `Backend: ReportController.php, DocumentController.php, event-report.blade.php`<br>`Frontend: EventDetailDrawer.jsx` |
| 6 | User Account Management | `Backend: UserController.php, User.php (model)`<br>`Frontend: StaffManagementPage.jsx, UserFormDialog.jsx, ChangePasswordDialog.jsx` |
| 7 | Role-Based Access Control | `Backend: EnsureUserHasRole.php (middleware), routes/api.php`<br>`Frontend: ProtectedRoute.jsx, App.jsx` |
| 8 | Schedule and Venue Management | `Backend: ScheduleController.php, ScheduleExport.php (service), VenueController.php, BuildingController.php`<br>`Frontend: SchedulePage.jsx, VenuesPage.jsx` |
| 9 | Venue Double-Booking Warning | `Backend: VenueClashes.php (service), EventController.php`<br>`Frontend: ClashWarning.jsx, EventFormDialog.jsx` |
| 10 | Data Protection and Migration | `Backend: Backup.php (service), DailyBackup.php (middleware), ScheduleImport.php (service), routes/console.php`<br>`Frontend: ManagerDashboard.jsx (backup status)` |

---

## Module 1 — Login and Authentication

**AuthController.php** (`backend/app/Http/Controllers/Api/AuthController.php`, lines 14–41)

```php
public function login(Request $request): JsonResponse
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    if (! $user->is_active) {
        throw ValidationException::withMessages([
            'email' => ['This account has been deactivated.'],
        ]);
    }

    $token = $user->createToken('emo-tracker', ['*'], now()->addDays(30))->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->only(['id', 'name', 'email', 'role', 'is_active']),
    ]);
}
```

**Explanation:** The login() method validates that an email and password were supplied, then looks up the user by email. It verifies the password against the stored bcrypt hash using Laravel's Hash::check(); the plain password is never compared directly or stored. The same message is returned whether the email or the password is wrong, so the response does not reveal which accounts exist. Accounts flagged is_active = false are rejected. On success, a Laravel Sanctum bearer token valid for 30 days is issued and returned together with the user's basic profile.

**AppServiceProvider.php** (`backend/app/Providers/AppServiceProvider.php`, lines 22–43)

```php
/**
 * Bootstrap any application services.
 */
public function boot(): void
{
    // Tokens belonging to deactivated accounts are rejected on every route.
    Sanctum::authenticateAccessTokensUsing(
        fn ($accessToken, bool $isValid) => $isValid && $accessToken->tokenable?->is_active
    );

    // Ten tries a minute for each account on each device, so one person's
    // typos (or someone guessing one password) never lock everyone else out.
    RateLimiter::for('login', function (Request $request) {
        $email = $request->input('email');

        return Limit::perMinute(10)
            ->by(Str::lower(is_string($email) ? $email : '').'|'.$request->ip())
            ->response(fn (Request $request, array $headers) => response()->json([
                'message' => 'Too many login attempts. Wait a minute, then try again.',
            ], 429, $headers));
    });
}
```

**Explanation:** Two protections added in Capstone 2 are registered when the application starts. First, a token is accepted only while its account is active, so deactivating an account cuts off its sessions at once. Second, the login route is limited to 10 attempts per minute for each combination of email address and device; further attempts receive HTTP 429 with the message "Too many login attempts. Wait a minute, then try again." Keying the limit by both email and device means one person's typing mistakes never lock out the rest of the office.

**AuthController.php** (`backend/app/Http/Controllers/Api/AuthController.php`, lines 57–78)

```php
public function changePassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'current_password' => ['required', 'string'],
        'new_password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = $request->user();

    if (! Hash::check($data['current_password'], $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => ['The current password is incorrect.'],
        ]);
    }

    $user->update(['password' => Hash::make($data['new_password'])]);

    // Anyone still signed in elsewhere with the old password is signed out.
    $user->signOutEverywhere(except: $user->currentAccessToken());

    return response()->json(['message' => 'Password changed. Your other devices have been signed out.']);
}
```

**Explanation:** Any signed-in user can change their own password. The current password must be correct, and the new one must have at least 8 characters and be typed twice (the confirmed rule). The new password is stored as a bcrypt hash, and every other session of the account is signed out, so anyone still using the old password elsewhere loses access.

**AuthContext.jsx** (`frontend/src/contexts/AuthContext.jsx`, lines 31–37)

```jsx
async function login(email, password) {
  const res = await api.post('/login', { email, password })
  localStorage.setItem('emo_token', res.data.token)
  localStorage.setItem('emo_user', JSON.stringify(res.data.user))
  setUser(res.data.user)
  return res.data.user
}
```

**Explanation:** On the frontend, login() sends the credentials to the API and keeps the returned token and user profile in the browser's local storage, so a page reload keeps the user signed in. The shared API client (api.js) attaches the token to every request as an Authorization: Bearer header, and if the server ever answers 401 (an expired or revoked token), it clears the stored session and returns the user to the login page.

---

## Module 2 — Event Planning and Scheduling

**EventController.php** (`backend/app/Http/Controllers/Api/EventController.php`, lines 74–87)

```php
public function store(Request $request): JsonResponse
{
    $data = $this->validateDetails($request);

    $event = Event::create([
        ...$data,
        'event_type' => $data['event_type'] ?? 'internal',
        'needs_preparation' => $data['needs_preparation'] ?? false,
        'status' => Event::statusForDate($data['event_date'], $data['end_date'] ?? null, $data['end_time'] ?? null),
        'created_by' => $request->user()->id,
    ]);

    return response()->json(['event' => $this->withSummary($event)], 201);
}
```

**Explanation:** store() adds an event after validating its details (name, type, department, venue or place, dates and times, control number and remarks). Events are internal unless marked external, and are schedule-only unless the EMO prepares them. The status is not chosen by the user: it is worked out from the event's date and time, so an event entered with a past date is stored as completed. The creating Administrator is recorded in created_by.

**EventController.php** (`backend/app/Http/Controllers/Api/EventController.php`, lines 89–116)

```php
public function update(Request $request, Event $event): JsonResponse
{
    $data = $this->validateDetails($request, $event);

    $from = [$event->event_date->toDateString(), self::time($event->event_time)];
    $event->fill(collect($data)->except(['cancelled', 'rescheduled'])->all());
    $to = [$event->event_date->toDateString(), self::time($event->event_time)];

    // A reschedule (rather than a correction) keeps where the event was
    // first scheduled. Moving it back there means it's no longer rescheduled.
    if ($to !== $from && $request->boolean('rescheduled') && ! $event->original_date) {
        $event->original_date = $from[0];
        $event->original_time = $from[1] ? "{$from[1]}:00" : null;
    }
    if ($event->original_date && $to === [$event->original_date->toDateString(), self::time($event->original_time)]) {
        $event->original_date = null;
        $event->original_time = null;
    }

    // Status follows the date and time, so moving a past event into the
    // future reopens it and moving one into the past closes it. A cancelled
    // event stays cancelled until it's explicitly restored.
    $cancelled = $data['cancelled'] ?? $event->status === 'cancelled';
    $event->status = $cancelled ? 'cancelled' : Event::statusForDate($event->event_date, $event->end_date, $event->end_time);
    $event->save();

    return response()->json(['event' => $this->withSummary($event)]);
}
```

**Explanation:** update() edits an event and handles two Capstone 2 rules. When the start date or time changes and the Administrator answers that the event was moved (a reschedule rather than a correction), the original date and time are kept, so the system can show "Rescheduled from ..."; moving the event back to its original slot removes the mark. The status is then recalculated from the new date, so a completed event moved to the future reopens, while a cancelled event stays cancelled until it is explicitly restored.

**Event.php** (`backend/app/Models/Event.php`, lines 126–132, lines 134–137, lines 139–145, lines 153–170)

```php
// The end time on the last day, or the end of that day.
public static function endsAt($date, $endDate = null, $endTime = null): Carbon
{
    $day = Carbon::parse($endDate ?? $date);

    return $endTime ? $day->startOfDay()->setTimeFromTimeString($endTime) : $day->endOfDay();
}

public static function statusForDate($date, $endDate = null, $endTime = null): string
{
    return self::endsAt($date, $endDate, $endTime)->isFuture() ? 'upcoming' : 'completed';
}

public function getOngoingAttribute(): bool
{
    return $this->status === 'upcoming'
        && $this->event_date !== null
        && ! self::startsAt($this->event_date, $this->event_time)->isFuture()
        && self::endsAt($this->event_date, $this->end_date, $this->end_time)->isFuture();
}

public static function completePastEvents(): void
{
    $today = today()->toDateString();
    $lastDay = fn ($q) => $q
        ->where(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', $today))
        ->orWhereDate('end_date', $today);

    static::where('status', 'upcoming')
        ->where(fn ($q) => $q
            ->where(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', '<', $today))
            ->orWhereDate('end_date', '<', $today)
            // Ended earlier today.
            ->orWhere(fn ($endedToday) => $endedToday
                ->whereNotNull('end_time')
                ->whereTime('end_time', '<=', now()->format('H:i:s'))
                ->where($lastDay)))
        ->update(['status' => 'completed']);
}
```

**Explanation:** These methods implement the event status lifecycle. Only upcoming, completed and cancelled are stored. endsAt() finds when an event ends (its end time on the last day, or the end of that day), and statusForDate() uses it to decide between upcoming and completed. Ongoing is not stored: getOngoingAttribute() computes it for an upcoming event that has started but not ended. completePastEvents() runs at the start of every API request and marks every finished event completed in one database update, so a past event's tasks are locked even if nobody opens it.

---

## Module 3 — Task Assignment and Tracking

**TaskController.php** (`backend/app/Http/Controllers/Api/TaskController.php`, lines 50–90)

```php
public function store(Request $request, Event $event): JsonResponse
{
    if ($event->status === 'cancelled') {
        return response()->json([
            'message' => 'This event is cancelled. Restore it before adding tasks.',
        ], 422);
    }

    if ($event->status === 'completed' && $request->user()->role !== 'admin') {
        return response()->json([
            'message' => 'This event is completed. Only an administrator can add tasks to it.',
        ], 403);
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'due_date' => ['required', 'date'],
        'status' => ['nullable', Rule::in(['pending', 'in_progress', 'done'])],
        'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
        'assigned_to' => ['nullable', 'integer', new AssignableMember],
    ]);

    $task = $event->tasks()->create([
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'due_date' => $data['due_date'],
        'status' => $data['status'] ?? 'pending',
        'priority' => $data['priority'] ?? 'medium',
        'assigned_to' => $data['assigned_to'] ?? null,
    ]);

    // Planning tasks for an event means the EMO is preparing it.
    if (! $event->needs_preparation) {
        $event->update(['needs_preparation' => true]);
    }

    $task->load('assignee:id,name,email,role');

    return response()->json(['task' => $task], 201);
}
```

**Explanation:** store() adds a task to an event. Tasks cannot be added to a cancelled event, and only the Administrator can add them to a completed one. Each task has a name, an optional description, a due date, a status, a priority and at most one owner; the AssignableMember rule accepts only active members as owners (any role, since every office member can take tasks). Adding the first task to an event marks it as one the EMO prepares, which turns on its readiness tracking.

**TaskController.php** (`backend/app/Http/Controllers/Api/TaskController.php`, lines 115–139)

```php
public function updateStatus(Request $request, Task $task): JsonResponse
{
    $user = $request->user();
    $isOwner = $task->assigned_to === $user->id;
    $canManage = in_array($user->role, ['admin', 'officer'], true);

    if (! $isOwner && ! $canManage) {
        return response()->json(['message' => 'You can only update your own tasks.'], 403);
    }

    if ($task->event->status === 'completed' && $user->role !== 'admin') {
        return response()->json([
            'message' => 'This event is completed. Only an administrator can modify its tasks.',
        ], 403);
    }

    $data = $request->validate([
        'status' => ['required', Rule::in(['pending', 'in_progress', 'done'])],
    ]);

    $task->update($data);
    $task->load('assignee:id,name,email,role');

    return response()->json(['task' => $task]);
}
```

**Explanation:** updateStatus() changes a task's status (pending, in progress or done). Staff may change only the tasks they own, while Officers and the Administrator may change any task. When an event is completed its tasks become a record, so only the Administrator can still change them. These checks run on the server, so they hold even for requests that do not come from the user interface.

---

## Module 4 — Rule-Based Event Readiness Classification

**EventClassifier.php** (`backend/app/Services/EventClassifier.php`, lines 28–90)

```php
/**
 * @return array{0: string, 1: ?string}
 */
private static function assess(Event $event): array
{
    if ($event->status === 'cancelled') {
        return ['cancelled', null];
    }

    if ($event->status === 'completed') {
        return ['completed', null];
    }

    // Events the EMO only schedules (not prepares) aren't classified.
    if (! $event->needs_preparation) {
        return ['scheduled', null];
    }

    $tasks = $event->relationLoaded('tasks') ? $event->tasks : $event->tasks()->get();

    if ($tasks->isEmpty()) {
        return ['yellow', 'No tasks yet'];
    }

    $open = $tasks->where('status', '!=', 'done');

    // Nothing left to do: the event is ready no matter how close it is.
    if ($open->isEmpty()) {
        return ['green', 'All tasks done'];
    }

    $today = Carbon::today();
    $days = (int) $today->diffInDays(Carbon::parse($event->event_date), false);
    $donePct = ($tasks->count() - $open->count()) / $tasks->count() * 100;
    $done = (int) floor($donePct).'% done';
    $toGo = "{$days} days to go";
    $overdue = $open->filter(fn ($task) => $task->due_date?->lt($today))->count();
    $ownerless = $open->whereNull('assigned_to')->count();

    // Critical
    if ($overdue > 0) {
        return ['red', $overdue === 1 ? '1 task is overdue' : "{$overdue} tasks are overdue"];
    }
    if ($days <= 2) {
        return ['red', self::count($open->count(), 'task').' still open, '.self::when($days)];
    }
    if ($days <= 7 && $donePct < 40) {
        return ['red', "Only {$done}, {$toGo}"];
    }
    if ($days <= 7 && $ownerless * 2 > $open->count()) {
        return ['red', "{$ownerless} of {$open->count()} open tasks have no owner, {$toGo}"];
    }

    // At Risk
    if ($days <= 14 && $donePct < 70) {
        return ['yellow', "{$done}, {$toGo}"];
    }
    if ($ownerless > 0) {
        return ['yellow', self::count($ownerless, 'open task').($ownerless === 1 ? ' has' : ' have').' no owner'];
    }

    return ['green', "{$done}, nothing overdue"];
}
```

**Explanation:** assess() is the rule-based classifier. It checks a fixed list of rules in order, and the first rule that matches decides both the label and the reason shown to users. Cancelled, completed and schedule-only events are not classified. An event with no tasks is At Risk, and one whose tasks are all done is On Track. Otherwise it is Critical if a task is overdue, if it is two days away or less, or if it is seven days away or less with under 40% of its tasks done or with more than half of its open tasks unassigned. It is At Risk if it is fourteen days away or less with under 70% done, or if any open task has no owner; in every other case it is On Track. The classifier uses no machine learning or outside service, and its thresholds can be adjusted in this one class.

**AnalyticsController.php** (`backend/app/Http/Controllers/Api/AnalyticsController.php`, lines 14–38)

```php
public function index(Request $request): JsonResponse
{
    $period = $request->input('period', 'all');

    // Calendar periods, like a report: this week (Sunday to Saturday, as on
    // the calendar), this month, this year. Events count by their start date.
    $range = match ($period) {
        'week' => [today()->startOfWeek(CarbonInterface::SUNDAY), today()->endOfWeek(CarbonInterface::SATURDAY)],
        'month' => [today()->startOfMonth(), today()->endOfMonth()],
        'year' => [today()->startOfYear(), today()->endOfYear()],
        default => null,
    };
    $inPeriod = fn ($query) => $range
        ? $query->whereDate('event_date', '>=', $range[0])->whereDate('event_date', '<=', $range[1])
        : $query;

    return response()->json([
        'period' => $period,
        'schedule' => $this->schedule($inPeriod(Event::with('venue:id,name'))->get()),
        ...$this->preparation($inPeriod(Event::with([Event::READINESS_TASKS, 'venue:id,name']))
            ->where('needs_preparation', true)
            ->where('status', '!=', 'cancelled')
            ->get()),
    ]);
}
```

**Explanation:** index() returns the statistics for the Analytics page for a chosen period: this week (Sunday to Saturday), this month, this year or all time, counting events by their start date. It returns schedule statistics for all events (statuses, reschedules, internal and external events, busiest venues) and preparation statistics for the events the EMO prepares, which include the readiness distribution and the most urgent events.

---

## Module 5 — Reports and Document Management

**DocumentController.php** (`backend/app/Http/Controllers/Api/DocumentController.php`, lines 26–60, lines 62–67)

```php
public function store(Request $request, Event $event): JsonResponse
{
    // PHP turns away a file over its own limit before the app sees it.
    $upload = $request->file('file');
    $overLimit = $upload instanceof UploadedFile
        && in_array($upload->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);

    $request->validate([
        'file' => [
            'required',
            'file',
            'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'max:'.Document::MAX_UPLOAD_KB,
        ],
    ], [
        'file.uploaded' => $overLimit ? Document::tooLargeMessage() : "The file didn't upload completely. Please try again.",
        'file.max' => Document::tooLargeMessage(),
        'file.mimes' => "This kind of file isn't accepted. Use a PDF, Word, Excel, JPG or PNG file.",
    ]);

    $file = $request->file('file');
    $path = $file->store($event->documentsFolder(), 'local');

    $doc = $event->documents()->create([
        'uploaded_by' => $request->user()->id,
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $path,
        'file_size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
    ]);

    $doc->load('uploader:id,name');

    return response()->json(['document' => $doc], 201);
}

public function download(Document $document): StreamedResponse
{
    abort_unless(Storage::disk('local')->exists($document->file_path), 404);

    return Storage::disk('local')->download($document->file_path, $document->file_name);
}
```

**Explanation:** store() accepts an uploaded file only if it is a PDF, Word, Excel, JPG or PNG file of up to 10 MB, and explains the reason when a file is refused (including files PHP turns away for size before the application sees them). Accepted files are saved in private storage, in a folder for their event, never in a public folder, and their name, size, type and uploader are recorded. download() serves a file only through this authenticated route. Uploading is limited to Officers and the Administrator, and deleting to the Administrator, so documents stay part of the event's record.

**ReportController.php** (`backend/app/Http/Controllers/Api/ReportController.php`, lines 13–43)

```php
public function event(Event $event): Response
{
    $event->load([
        'tasks.assignee:id,name,email,role',
        'creator:id,name',
        'documents.uploader:id,name',
        'venue',
    ]);

    $taskSummary = [
        'total' => $event->tasks->count(),
        'done' => $event->tasks->where('status', 'done')->count(),
        'in_progress' => $event->tasks->where('status', 'in_progress')->count(),
        'pending' => $event->tasks->where('status', 'pending')->count(),
    ];
    $taskSummary['percent'] = $taskSummary['total'] > 0
        ? round(($taskSummary['done'] / $taskSummary['total']) * 100)
        : 0;

    $pdf = Pdf::loadView('pdf.event-report', [
        'event' => $event,
        'taskSummary' => $taskSummary,
        // The people on an event are everyone with a task on it.
        'people' => $event->tasks->pluck('assignee')->filter()->unique('id')->sortBy('name')->values(),
        'generatedAt' => now(),
    ])->setPaper('a4');

    $filename = 'event-report-'.Str::slug($event->name).'.pdf';

    return $pdf->download($filename);
}
```

**Explanation:** event() generates an event's PDF report on the server with dompdf. It loads the event with its tasks and their owners, creator, documents and venue, computes the task summary (total, done, in progress, pending and the completion percentage), and renders the event-report template, which also shows the readiness label and its reason. Because the PDF is built on the server, it looks the same whichever device downloads it.

---

## Module 6 — User Account Management

**UserController.php** (`backend/app/Http/Controllers/Api/UserController.php`, lines 25–46, lines 55–90, lines 92–102)

```php
public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
        'role' => ['required', Rule::in(['admin', 'officer', 'staff'])],
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'role' => $data['role'],
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    return response()->json([
        'user' => $user->only(['id', 'name', 'email', 'role', 'is_active']),
    ], 201);
}

public function update(Request $request, User $user): JsonResponse
{
    $data = $request->validate([
        'name' => ['sometimes', 'string', 'max:255'],
        'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        'role' => ['sometimes', Rule::in(['admin', 'officer', 'staff'])],
        'is_active' => ['sometimes', 'boolean'],
        'password' => ['sometimes', 'string', 'min:8'],
    ]);

    // Keeps at least one active admin: the acting admin can't lock themselves out.
    if ($user->id === $request->user()->id) {
        if (array_key_exists('is_active', $data) && ! $data['is_active']) {
            return response()->json(['message' => 'You cannot deactivate yourself.'], 422);
        }
        if (array_key_exists('role', $data) && $data['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove your own administrator role.'], 422);
        }
    }

    if (isset($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    }

    $user->update($data);

    // A deactivated account, or one given a new password, is signed out
    // everywhere (an admin resetting their own stays signed in here).
    if (! $user->is_active || isset($data['password'])) {
        $user->signOutEverywhere(except: $user->is($request->user()) ? $request->user()->currentAccessToken() : null);
    }

    return response()->json([
        'user' => $user->fresh()->only(['id', 'name', 'email', 'role', 'is_active']),
    ]);
}

public function destroy(User $user, Request $request): JsonResponse
{
    if ($user->id === $request->user()->id) {
        return response()->json(['message' => 'You cannot deactivate yourself.'], 422);
    }

    $user->update(['is_active' => false]);
    $user->tokens()->delete();

    return response()->json(['message' => 'User deactivated.']);
}
```

**Explanation:** Only the Administrator manages accounts; there is no public registration. store() creates an account with a unique email, a password of at least 8 characters (stored as a bcrypt hash) and a role. update() edits the name, email, role or status, or sets a new password, but stops the Administrator from deactivating themselves or removing their own administrator role, so the office always keeps an administrator. destroy() does not delete the account: it deactivates it, so the events, tasks and documents the person created keep their author. A deactivated account, or one given a new password, is signed out on every device.

**User.php** (`backend/app/Models/User.php`, lines 55–63)

```php
/**
 * Signs the account out on every device, optionally keeping the one in use.
 */
public function signOutEverywhere(mixed $except = null): void
{
    $this->tokens()
        ->when($except instanceof PersonalAccessToken, fn ($tokens) => $tokens->whereKeyNot($except->getKey()))
        ->delete();
}
```

**Explanation:** signOutEverywhere() deletes the account's login tokens, optionally keeping the one in use, so the current device stays signed in when a user changes their own password. It is used when users change their own password, and when the Administrator sets a new password or turns an account off in the edit form. (The Deactivate button signs an account out the same way, by deleting all of its tokens.)

---

## Module 7 — Role-Based Access Control

**EnsureUserHasRole.php** (`backend/app/Http/Middleware/EnsureUserHasRole.php`, lines 11–28)

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = $request->user();

    if (! $user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    if (! $user->is_active) {
        return response()->json(['message' => 'Account is deactivated.'], 403);
    }

    if (! in_array($user->role, $roles, true)) {
        return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
    }

    return $next($request);
}
```

**Explanation:** This middleware guards every role-restricted route. It rejects requests without a valid login (401) and from deactivated accounts (403), then allows the request only if the user's role is one of the roles the route lists; otherwise it answers 403 with "You do not have permission to access this resource."

**api.php** (`backend/routes/api.php`, lines 24–83)

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('role:admin')->get('/dashboard/admin', [DashboardController::class, 'admin']);
    Route::middleware('role:admin,officer')->get('/dashboard/officer', [DashboardController::class, 'officer']);
    Route::middleware('role:staff')->get('/dashboard/staff', [DashboardController::class, 'staff']);

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/{event}/tasks', [TaskController::class, 'index']);
    Route::get('/events/{event}/documents', [DocumentController::class, 'index']);
    Route::get('/events/{event}/report', [ReportController::class, 'event']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);

    Route::get('/schedule', [ScheduleController::class, 'index']);
    Route::get('/venues', [VenueController::class, 'index']);

    Route::get('/my-tasks', [TaskController::class, 'myTasks']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::middleware('role:admin,officer')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::get('/schedule/export', [ScheduleController::class, 'export']);
        Route::post('/events/{event}/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('/events/{event}/documents', [DocumentController::class, 'store']);
        Route::get('/users', [UserController::class, 'index']);
    });

    // The admin owns the schedule: event details (date, time, venue, ...) are admin-only.
    // Officers handle preparation: tasks, documents and reports. Documents are
    // records, so once uploaded only the admin can delete them.
    Route::middleware('role:admin')->group(function () {
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::patch('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::get('/departments', [EventController::class, 'departments']);
        Route::get('/event-clashes', [EventController::class, 'clashes']);
        Route::post('/venues', [VenueController::class, 'store']);
        Route::put('/venues/{venue}', [VenueController::class, 'update']);
        Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);
        Route::post('/venues/{venue}/merge', [VenueController::class, 'merge']);
        Route::post('/buildings', [BuildingController::class, 'store']);
        Route::put('/buildings/{building}', [BuildingController::class, 'update']);
        Route::delete('/buildings/{building}', [BuildingController::class, 'destroy']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
```

**Explanation:** The routes file applies the rules above to every endpoint. Login is public but rate-limited. Everything else requires a valid token (auth:sanctum). Within that, routes are grouped by role: any signed-in user can view events, the Schedule, tasks and documents and update their own task status; Officers and the Administrator can manage tasks, upload documents, view Analytics and export the Schedule; and only the Administrator can change events, venues, buildings and accounts, or delete documents.

**ProtectedRoute.jsx** (`frontend/src/components/ProtectedRoute.jsx`, lines 1–24)

```jsx
import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/auth'

export default function ProtectedRoute({ children, allowedRoles }) {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-gray-500 text-sm">Loading...</div>
      </div>
    )
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (allowedRoles && !allowedRoles.includes(user.role)) {
    return <Navigate to={`/${user.role}`} replace />
  }

  return children
}
```

**Explanation:** On the frontend, ProtectedRoute wraps each role's pages. It waits while a saved session is being checked, sends a signed-out visitor to the login page, and sends a signed-in user who opens another role's address back to their own Home page. This keeps the interface tidy, but the real protection is the server-side check above, which cannot be bypassed from the browser.

---

## Module 8 — Schedule and Venue Management

**ScheduleController.php** (`backend/app/Http/Controllers/Api/ScheduleController.php`, lines 18–55, lines 57–65)

```php
public function index(Request $request): JsonResponse
{
    $years = Event::pluck('event_date')->map->year->push(today()->year)->unique()->sort()->values();
    $year = (int) $request->input('year', today()->year);

    $events = Event::with(['venue.building', Event::READINESS_TASKS])
        ->whereYear('event_date', $year)
        ->orderBy('event_date')
        ->orderBy('event_time')
        ->get();

    // Double bookings matter for events still to come; past ones can't be fixed.
    $clashes = VenueClashes::within($events);

    $rows = $events->map(fn (Event $e) => [
        'id' => $e->id,
        'name' => $e->name,
        'event_type' => $e->event_type,
        'department' => $e->department,
        'event_date' => $e->event_date->toDateString(),
        'end_date' => $e->end_date?->toDateString(),
        'event_time' => $e->event_time,
        'end_time' => $e->end_time,
        'original_date' => $e->original_date?->toDateString(),
        'original_time' => $e->original_time,
        'location' => $e->location,
        'building' => $e->venue?->building?->only(['id', 'name', 'color']),
        'control_number' => $e->control_number,
        'remarks' => $e->remarks,
        'status' => $e->status,
        'ongoing' => $e->ongoing,
        'clashes' => $e->status === 'upcoming' ? collect($clashes[$e->id] ?? [])->pluck('name')->values() : [],
        'readiness' => $e->readiness,
        'needs_preparation' => $e->needs_preparation,
    ]);

    return response()->json(['year' => $year, 'current_year' => today()->year, 'years' => $years, 'events' => $rows]);
}

public function export(Request $request): Response
{
    $year = (int) $request->input('year', today()->year);

    return response(ScheduleExport::build($year), 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => "attachment; filename=\"emo-schedule-{$year}.xlsx\"",
    ]);
}
```

**Explanation:** index() returns one year of the Schedule, laid out like the EMO's own spreadsheet: every event and venue booking with its date, time, type, department, location, control number and remarks, the color of its venue's building, its status, its readiness and any overlapping bookings. It also lists the years that have events, for the year tabs. export() returns the same year as an Excel file built by the ScheduleExport service, in the spreadsheet's familiar format.

**VenueController.php** (`backend/app/Http/Controllers/Api/VenueController.php`, lines 74–94)

```php
/**
 * Fix duplicates such as "UHALL" and "University Hall": move every event to
 * the other venue, then remove this one.
 */
public function merge(Request $request, Venue $venue): JsonResponse
{
    $data = $request->validate([
        'into_id' => ['required', 'integer', 'exists:venues,id', Rule::notIn([$venue->id])],
    ], [
        'into_id.not_in' => 'Choose a different venue to merge into.',
    ]);

    $moved = DB::transaction(function () use ($venue, $data) {
        $moved = Event::where('venue_id', $venue->id)->update(['venue_id' => $data['into_id']]);
        $venue->delete();

        return $moved;
    });

    return response()->json(['message' => "Merged. {$moved} event(s) moved.", 'moved' => $moved]);
}
```

**Explanation:** merge() combines a duplicate venue (for example, "UHALL" and "University Hall") into another one: inside a database transaction, it moves all of the duplicate's events to the other venue and then deletes the duplicate. Keeping one name per place is what lets the Schedule colors, the double-booking check and the venue statistics work.

---

## Module 9 — Venue Double-Booking Warning

**VenueClashes.php** (`backend/app/Services/VenueClashes.php`, lines 20–36, lines 110–116, lines 118–129)

```php
public static function between(Event $a, Event $b): bool
{
    // Venue ids from the form arrive as text, so compare them as numbers.
    if ($a->is($b) || ! $a->venue_id || (int) $a->venue_id !== (int) $b->venue_id) {
        return false;
    }
    if ($a->status === 'cancelled' || $b->status === 'cancelled' || ! self::sameRoom($a->venue_details, $b->venue_details)) {
        return false;
    }
    if ($a->event_date->gt($b->end_date ?? $b->event_date) || $b->event_date->gt($a->end_date ?? $a->event_date)) {
        return false;
    }
    [$aFrom, $aTo] = self::hours($a);
    [$bFrom, $bTo] = self::hours($b);

    return $aFrom < $bTo && $bFrom < $aTo;
}

// An empty room means the whole venue; otherwise the same room text (ignoring case and spaces).
private static function sameRoom(?string $a, ?string $b): bool
{
    $normal = fn (?string $room) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $room)));

    return $normal($a) === '' || $normal($b) === '' || $normal($a) === $normal($b);
}

// Daily hours in minutes. No time, or hours that run past midnight, count as
// all day; no end time runs to the end of the day.
private static function hours(Event $event): array
{
    if (! $event->event_time) {
        return self::ALL_DAY;
    }
    $from = self::minutes($event->event_time);
    $to = $event->end_time ? self::minutes($event->end_time) : 24 * 60;

    return $to > $from ? [$from, $to] : self::ALL_DAY;
}
```

**Explanation:** between() decides whether two bookings clash. They must use the same venue from the list (places typed as free text are not compared), neither may be cancelled, they must be in the same room or one must take the whole venue (sameRoom(), which ignores capital letters and extra spaces), and their days and hours must overlap. hours() treats a booking with no start time, or one that runs past midnight, as all day, and one with no end time as lasting until midnight; bookings that only touch, such as 8 to 10 and 10 to 12, do not overlap.

**EventController.php** (`backend/app/Http/Controllers/Api/EventController.php`, lines 50–72)

```php
/**
 * Bookings that would clash with an event before it's saved, so the form
 * can warn while the date, time and venue are being chosen.
 */
public function clashes(Request $request): JsonResponse
{
    $data = $request->validate([
        'venue_id' => ['nullable', 'integer'],
        'venue_details' => ['nullable', 'string', 'max:255'],
        'event_date' => ['required', 'date'],
        'end_date' => ['nullable', 'date'],
        'event_time' => ['nullable', 'date_format:H:i'],
        'end_time' => ['nullable', 'date_format:H:i'],
        'ignore' => ['nullable', 'integer'],
    ]);

    $event = new Event(collect($data)->except('ignore')->all());
    $event->status = 'upcoming';

    return response()->json([
        'clashes' => VenueClashes::for($event, $data['ignore'] ?? null)->map(fn (Event $other) => VenueClashes::summary($other)),
    ]);
}
```

**Explanation:** clashes() is called by the event form while the Administrator fills it in, before anything is saved. It builds an unsaved event from the venue, dates and times entered so far and returns the existing bookings it clashes with, leaving out the event being edited. The form shows these as a warning but still allows saving, because some overlaps are intended, such as a rehearsal right before its own event.

---

## Module 10 — Data Protection and Migration

**DailyBackup.php** (`backend/app/Http/Middleware/DailyBackup.php`, lines 22–39)

```php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    $key = 'backup:daily:'.today()->toDateString();
    if (config('backup.daily') && Cache::add($key, true, now()->addDays(2))) {
        defer(function () use ($key) {
            try {
                Backup::create();
            } catch (Throwable $e) {
                report($e);
                Cache::put($key, true, now()->addHour());
            }
        });
    }

    return $response;
}
```

**Explanation:** handle() makes the daily backup without delaying anyone: after a response is sent, the first request of each day starts the backup. A cache key ensures it runs only once a day; if the backup fails, the error is logged and another attempt is allowed an hour later.

**Backup.php** (`backend/app/Services/Backup.php`, lines 31–46, lines 63–91, lines 168–174)

```php
/**
 * A named snapshot (e.g. "fresh-import") is kept until someone deletes it;
 * plain daily backups are cleaned up after the newest 14.
 */
public static function create(?string $name = null): string
{
    try {
        $path = self::write($name);
    } catch (Throwable $e) {
        Cache::put(self::FAILED, ['at' => now()->toIso8601String(), 'message' => $e->getMessage()], now()->addDays(30));
        throw $e;
    }
    Cache::forget(self::FAILED);

    return $path;
}

private static function write(?string $name): string
{
    $dir = config('backup.path');
    // @: report which folder failed, not PHP's bare "mkdir(): Not a directory".
    if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
        throw new RuntimeException("Can't create the backup folder: {$dir}");
    }

    $label = $name ? preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($name)) : null;
    $path = $dir.DIRECTORY_SEPARATOR.($label
        ? 'emo-snapshot-'.self::database().'-'.$label.'.zip'
        : self::prefix().now()->format('Y-m-d_His').'.zip');
    $zip = new ZipArchive;
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Can't write the backup file: {$path}");
    }

    $zip->addFromString('database.json', json_encode(self::dumpDatabase(), JSON_UNESCAPED_UNICODE));

    $disk = Storage::disk('local');
    foreach ($disk->allFiles(self::DOCUMENTS) as $file) {
        $zip->addFile($disk->path($file), $file);
    }

    $zip->close();
    self::prune();

    return $path;
}

// Each database keeps its own newest backups, so one can't crowd out another.
private static function prune(): void
{
    foreach (array_slice(self::mine(), max(1, config('backup.keep'))) as $old) {
        @unlink($old);
    }
}
```

**Explanation:** create() records a failure so the Administrator's Home page can show a warning, and clears it after a success. write() saves the whole database and every uploaded document into one .zip file in the backup folder (a USB drive when BACKUP_PATH points to one). prune() then keeps only the newest 14 daily backups; named snapshots, saved before database updates or by hand, are never deleted automatically. The ScheduleImport service (not shown because of its length) was used once at installation to bring the EMO's existing spreadsheet into the database, with a list of rows for a person to review.
