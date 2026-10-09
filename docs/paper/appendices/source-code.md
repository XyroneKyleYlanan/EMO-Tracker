# Source Code: Key Modules

The key code of EMO Tracker's 7 modules, for the appendix of the Capstone 2 paper. Each module lists the files it is made of, then the code that carries its main logic, copied from the repository (October 10, 2026) with its file and line numbers. The complete source code is in the repository: https://github.com/XyroneKyleYlanan/EMO-Tracker

The backend is PHP (Laravel 13) and the frontend is JavaScript (React 19). Comments in the code explain each rule in plain words.

---

## Module 1. Event Planning and Scheduling

Stores every event and booking and keeps its status true to the calendar. Only three statuses are stored (upcoming, completed, cancelled); "ongoing" is worked out from the date and time, and finished events are marked completed at the start of every request.

**Files:** `backend/app/Models/Event.php`, `backend/app/Http/Controllers/Api/EventController.php`, `backend/app/Http/Middleware/CompletePastEvents.php`, `frontend/src/pages/EventsPage.jsx`, `frontend/src/components/EventFormDialog.jsx`, `frontend/src/components/EventDetailDrawer.jsx`

**backend/app/Models/Event.php**, lines 118–124 (`startsAt()`)

```php
// The start time on the first day, or the start of that day.
public static function startsAt($date, $time = null): Carbon
{
    $day = Carbon::parse($date)->startOfDay();

    return $time ? $day->setTimeFromTimeString($time) : $day;
}
```

**backend/app/Models/Event.php**, lines 126–132 (`endsAt()`)

```php
// The end time on the last day, or the end of that day.
public static function endsAt($date, $endDate = null, $endTime = null): Carbon
{
    $day = Carbon::parse($endDate ?? $date);

    return $endTime ? $day->startOfDay()->setTimeFromTimeString($endTime) : $day->endOfDay();
}
```

**backend/app/Models/Event.php**, lines 134–137 (`statusForDate()`)

```php
public static function statusForDate($date, $endDate = null, $endTime = null): string
{
    return self::endsAt($date, $endDate, $endTime)->isFuture() ? 'upcoming' : 'completed';
}
```

**backend/app/Models/Event.php**, lines 139–145 (`getOngoingAttribute()`)

```php
public function getOngoingAttribute(): bool
{
    return $this->status === 'upcoming'
        && $this->event_date !== null
        && ! self::startsAt($this->event_date, $this->event_time)->isFuture()
        && self::endsAt($this->event_date, $this->end_date, $this->end_time)->isFuture();
}
```

**backend/app/Models/Event.php**, lines 153–170 (`completePastEvents()`)

```php
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

**backend/app/Http/Controllers/Api/EventController.php**, lines 74–87 (`store()`)

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

**backend/app/Http/Controllers/Api/EventController.php**, lines 89–116 (`update()`)

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

---

## Module 2. Task Assignment and Tracking

Lets Officers and the Administrator break an event into tasks with one owner each, and lets owners update their own task status. The tasks of a completed event are locked for everyone except the Administrator.

**Files:** `backend/app/Http/Controllers/Api/TaskController.php`, `backend/app/Models/Task.php`, `frontend/src/components/TaskFormDialog.jsx`, `frontend/src/components/TaskRow.jsx`, `frontend/src/pages/StaffTasksPage.jsx`

**backend/app/Http/Controllers/Api/TaskController.php**, lines 50–90 (`store()`)

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

**backend/app/Http/Controllers/Api/TaskController.php**, lines 115–139 (`updateStatus()`)

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

---

## Module 3. Rule-Based Readiness Classification

Labels each event the EMO prepares as On Track, At Risk or Critical, with a reason, using fixed rules checked in order (no machine learning or outside service). Shown in full.

**Files:** `backend/app/Services/EventClassifier.php`, `backend/app/Http/Controllers/Api/AnalyticsController.php`, `frontend/src/components/ReadinessBadge.jsx`, `frontend/src/pages/AnalyticsPage.jsx`

**backend/app/Services/EventClassifier.php**, lines 1–106 (whole file)

```php
<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Readiness of an event the EMO prepares: On Track (green), At Risk (yellow)
 * or Critical (red). Progress is only judged as the event gets close, so an
 * event planned weeks ahead isn't flagged just because work hasn't started;
 * an overdue task is flagged at any time.
 */
class EventClassifier
{
    public static function classify(Event $event): string
    {
        return self::assess($event)[0];
    }

    // A short reason for the label, e.g. "1 task is overdue".
    public static function reason(Event $event): ?string
    {
        return self::assess($event)[1];
    }

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

    private static function count(int $n, string $noun): string
    {
        return $n.' '.Str::plural($noun, $n);
    }

    private static function when(int $days): string
    {
        return match (true) {
            $days < 0 => 'event has started',
            $days === 0 => 'event is today',
            $days === 1 => 'event is tomorrow',
            default => "event is in {$days} days",
        };
    }
}
```

---

## Module 4. Reports and Document Management

Stores uploaded files in private storage (never in a public folder), serves them only to signed-in users, and generates each event's PDF report on the server.

**Files:** `backend/app/Http/Controllers/Api/DocumentController.php`, `backend/app/Http/Controllers/Api/ReportController.php`, `backend/resources/views/pdf/event-report.blade.php`, `frontend/src/components/EventDetailDrawer.jsx`

**backend/app/Http/Controllers/Api/DocumentController.php**, lines 26–60 (`store()`)

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
```

**backend/app/Http/Controllers/Api/DocumentController.php**, lines 62–67 (`download()`)

```php
public function download(Document $document): StreamedResponse
{
    abort_unless(Storage::disk('local')->exists($document->file_path), 404);

    return Storage::disk('local')->download($document->file_path, $document->file_name);
}
```

**backend/app/Http/Controllers/Api/ReportController.php**, lines 13–43 (`event()`)

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

---

## Module 5. Schedule Management

Shows one year of bookings laid out like the EMO's spreadsheet, exports it to Excel, and keeps one spelling per venue by letting the Administrator merge duplicates.

**Files:** `backend/app/Http/Controllers/Api/ScheduleController.php`, `backend/app/Services/ScheduleExport.php`, `backend/app/Http/Controllers/Api/VenueController.php`, `frontend/src/pages/SchedulePage.jsx`, `frontend/src/pages/VenuesPage.jsx`

**backend/app/Http/Controllers/Api/ScheduleController.php**, lines 18–55 (`index()`)

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
```

**backend/app/Http/Controllers/Api/ScheduleController.php**, lines 57–65 (`export()`)

```php
public function export(Request $request): Response
{
    $year = (int) $request->input('year', today()->year);

    return response(ScheduleExport::build($year), 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => "attachment; filename=\"emo-schedule-{$year}.xlsx\"",
    ]);
}
```

**backend/app/Http/Controllers/Api/VenueController.php**, lines 74–94 (`merge()`)

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

---

## Module 6. Venue Double-Booking Warning

Finds bookings at the same venue and room whose days and hours overlap, so the event form, the event panel and the Schedule can warn about them. Shown in full.

**Files:** `backend/app/Services/VenueClashes.php`, `frontend/src/components/ClashWarning.jsx`, `frontend/src/components/EventFormDialog.jsx`

**backend/app/Services/VenueClashes.php**, lines 1–137 (whole file)

```php
<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Double-booking check. Two bookings clash when they're at the same venue and
 * room (or one of them takes the whole venue), on overlapping days, at
 * overlapping times; an event with no time counts as all day. Cancelled events
 * don't hold the venue, and free-text places can't be compared reliably.
 * It's a warning, not a rule: some overlaps are on purpose, like a rehearsal
 * right before its own event.
 */
class VenueClashes
{
    private const ALL_DAY = [0, 24 * 60];

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

    /**
     * Saved bookings that clash with this one, whether or not it's saved yet
     * (the event form checks before saving). $ignore leaves out the event
     * being edited.
     */
    public static function for(Event $event, ?int $ignore = null): Collection
    {
        if (! $event->venue_id || ! $event->event_date || $event->status === 'cancelled') {
            return collect();
        }
        $ignore ??= $event->exists ? $event->id : null;
        $start = $event->event_date->toDateString();
        $end = ($event->end_date ?? $event->event_date)->toDateString();

        return Event::with('venue:id,name')
            ->where('venue_id', $event->venue_id)
            ->where('status', '!=', 'cancelled')
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))
            ->whereDate('event_date', '<=', $end)
            ->where(fn ($q) => $q
                ->whereDate('end_date', '>=', $start)
                ->orWhere(fn ($single) => $single->whereNull('end_date')->whereDate('event_date', '>=', $start)))
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->filter(fn (Event $other) => self::between($event, $other))
            ->values();
    }

    /**
     * Which events in a list clash with another one in it.
     *
     * @return array<int, Event[]> event id => the events it clashes with
     */
    public static function within(Collection $events): array
    {
        $clashes = [];
        $booked = $events->whereNotNull('venue_id')->where('status', '!=', 'cancelled');
        foreach ($booked->groupBy('venue_id') as $atVenue) {
            $list = $atVenue->sortBy(fn (Event $e) => $e->event_date->toDateString())->values();
            foreach ($list as $i => $a) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    $b = $list[$j];
                    // Sorted by start date: nothing later can overlap $a once one starts after it ends.
                    if ($b->event_date->gt($a->end_date ?? $a->event_date)) {
                        break;
                    }
                    if (self::between($a, $b)) {
                        $clashes[$a->id][] = $b;
                        $clashes[$b->id][] = $a;
                    }
                }
            }
        }

        return $clashes;
    }

    // What the form, panel and Schedule show about a clashing booking.
    public static function summary(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'event_date' => $event->event_date->toDateString(),
            'end_date' => $event->end_date?->toDateString(),
            'event_time' => $event->event_time,
            'end_time' => $event->end_time,
            'location' => $event->location,
        ];
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

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
```

---

## Module 7. Data Protection and Migration

Backs up the database and the uploaded documents once a day without making users wait, keeps the newest 14 backups, and records failures for the Administrator's Home page.

**Files:** `backend/app/Services/Backup.php`, `backend/app/Http/Middleware/DailyBackup.php`, `backend/app/Services/ScheduleImport.php`, `backend/routes/console.php`

**backend/app/Http/Middleware/DailyBackup.php**, lines 22–39 (`handle()`)

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

**backend/app/Services/Backup.php**, lines 31–46 (`create()`)

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
```

**backend/app/Services/Backup.php**, lines 63–91 (`write()`)

```php
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
```

**backend/app/Services/Backup.php**, lines 168–174 (`prune()`)

```php
// Each database keeps its own newest backups, so one can't crowd out another.
private static function prune(): void
{
    foreach (array_slice(self::mine(), max(1, config('backup.keep'))) as $old) {
        @unlink($old);
    }
}
```

---

## Supporting code: login and role checks

Every module relies on these: the login request (with its limit of 10 tries a minute) and the middleware that checks the user's role on every protected request.

**Files:** `backend/app/Http/Controllers/Api/AuthController.php`, `backend/app/Http/Middleware/EnsureUserHasRole.php`, `backend/routes/api.php`, `backend/app/Providers/AppServiceProvider.php`

**backend/app/Http/Controllers/Api/AuthController.php**, lines 14–41 (`login()`)

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

**backend/app/Http/Middleware/EnsureUserHasRole.php**, lines 11–28 (`handle()`)

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
