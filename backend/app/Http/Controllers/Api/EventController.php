<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::with([
            Event::READINESS_TASKS,
            'creator:id,name',
            'venue:id,name',
        ])
            ->when($request->boolean('mine'), fn ($q) => $q->involving($request->user()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('prepared'), fn ($q) => $q->where('needs_preparation', true))
            ->orderBy('event_date')
            ->get();

        $events->transform(fn ($event) => $this->summarize($event));

        return response()->json(['events' => $events]);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load([
            'tasks.assignee:id,name,email,role',
            'creator:id,name',
            'documents.uploader:id,name',
            'venue.building',
        ]);

        return response()->json(['event' => $event->append('readiness_reason')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateDetails($request);

        $event = Event::create([
            ...$data,
            'needs_preparation' => $data['needs_preparation'] ?? false,
            'status' => Event::statusForDate($data['event_date'], $data['end_date'] ?? null),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['event' => $this->withSummary($event)], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $data = $this->validateDetails($request, $event);

        $event->fill(collect($data)->except('cancelled')->all());

        // Status follows the date, so rescheduling a past event into the future
        // reopens it and moving one into the past closes it. A cancelled event
        // stays cancelled until it's explicitly restored.
        $cancelled = $data['cancelled'] ?? $event->status === 'cancelled';
        $event->status = $cancelled ? 'cancelled' : Event::statusForDate($event->event_date, $event->end_date);
        $event->save();

        return response()->json(['event' => $this->withSummary($event)]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    /**
     * Department names used so far, for suggestions in the event form.
     */
    public function departments(): JsonResponse
    {
        $departments = Event::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return response()->json(['departments' => $departments]);
    }

    private function validateDetails(Request $request, ?Event $event = null): array
    {
        $creating = $event === null;
        $required = $creating ? 'required' : 'sometimes';

        // End time must come after the start time, unless the event runs over several days.
        $startDate = $request->input('event_date', $event?->event_date?->toDateString());
        $endDate = $request->exists('end_date') ? $request->input('end_date') : $event?->end_date?->toDateString();
        $singleDay = ! $endDate || $endDate === $startDate;

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['nullable', 'string', 'max:255'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'venue_details' => ['nullable', 'string', 'max:255', Rule::requiredIf($creating && ! $request->filled('venue_id'))],
            'event_date' => [$required, 'date'],
            'end_date' => array_filter(['nullable', 'date', $startDate ? "after_or_equal:{$startDate}" : null]),
            'event_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', Rule::when($singleDay && $request->filled('event_time'), 'after:event_time')],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'control_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'needs_preparation' => ['sometimes', 'boolean'],
            'cancelled' => [$creating ? 'prohibited' : 'sometimes', 'boolean'],
        ], [
            'venue_details.required' => 'Choose a venue, or type where the event is held.',
            'end_date.after_or_equal' => 'The end date can\'t be before the start date.',
            'end_time.after' => 'The end time must be after the start time.',
        ]);
    }

    private function withSummary(Event $event): Event
    {
        $event->load([Event::READINESS_TASKS, 'creator:id,name', 'venue.building']);

        return $this->summarize($event);
    }

    // Tasks done so far, and how many people have a task on the event.
    private function summarize(Event $event): Event
    {
        $event->task_summary = [
            'total' => $event->tasks->count(),
            'done' => $event->tasks->where('status', 'done')->count(),
            'people' => $event->tasks->whereNotNull('assigned_to')->pluck('assigned_to')->unique()->count(),
        ];

        return $event;
    }
}
