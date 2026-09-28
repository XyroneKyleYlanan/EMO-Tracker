<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Rules\AssignableStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::visibleTo($request->user())
            ->with([
                'tasks:id,event_id,status,assigned_to',
                'staff:id,name,role',
                'creator:id,name',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('event_date')
            ->get();

        $events->transform(function ($event) {
            $event->task_summary = [
                'total' => $event->tasks->count(),
                'done' => $event->tasks->where('status', 'done')->count(),
            ];
            return $event;
        });

        return response()->json(['events' => $events]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isVisibleTo($request->user()), 403, 'You are not assigned to this event.');

        $event->load([
            'tasks.assignee:id,name,email,role',
            'staff:id,name,email,role',
            'creator:id,name',
            'documents.uploader:id,name',
        ]);

        return response()->json(['event' => $event]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_time' => ['required', 'date_format:H:i'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', new AssignableStaff],
        ]);

        $event = Event::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'venue' => $data['venue'],
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'],
            'budget' => $data['budget'] ?? null,
            'status' => Event::statusForDate($data['event_date']),
            'created_by' => $request->user()->id,
        ]);

        if (! empty($data['staff_ids'])) {
            $event->staff()->sync($data['staff_ids']);
        }

        $event->load(['tasks', 'staff:id,name,role', 'creator:id,name']);
        $event->task_summary = ['total' => 0, 'done' => 0];

        return response()->json(['event' => $event], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        if ($event->status === 'completed' && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'This event is completed. Only an administrator can edit it.',
            ], 403);
        }

        $currentStaffIds = $event->staff()->pluck('users.id')->all();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['sometimes', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'event_time' => ['sometimes', 'date_format:H:i'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'staff_ids' => ['sometimes', 'array'],
            'staff_ids.*' => ['integer', new AssignableStaff($currentStaffIds)],
        ]);

        // Status always follows the date, so rescheduling a past event into
        // the future (admin only) reopens it, and moving one into the past closes it.
        $event->fill(collect($data)->except('staff_ids')->all());
        $event->status = Event::statusForDate($event->event_date);
        $event->save();

        if (array_key_exists('staff_ids', $data)) {
            $event->staff()->sync($data['staff_ids'] ?? []);
        }

        $event->load(['tasks:id,event_id,status,assigned_to', 'staff:id,name,role', 'creator:id,name']);
        $event->task_summary = [
            'total' => $event->tasks->count(),
            'done' => $event->tasks->where('status', 'done')->count(),
        ];

        return response()->json(['event' => $event]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }
}
