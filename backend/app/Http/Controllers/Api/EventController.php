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
        Event::where('status', 'upcoming')
            ->whereDate('event_date', '<', today())
            ->update(['status' => 'completed']);

        $events = Event::with([
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

    public function show(Event $event): JsonResponse
    {
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
            'staff_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $event = Event::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'venue' => $data['venue'],
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'],
            'budget' => $data['budget'] ?? null,
            'status' => 'upcoming',
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
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['sometimes', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'event_time' => ['sometimes', 'date_format:H:i'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['upcoming', 'completed'])],
            'staff_ids' => ['sometimes', 'array'],
            'staff_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $event->update(collect($data)->except('staff_ids')->all());

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
