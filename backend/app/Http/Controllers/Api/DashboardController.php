<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Home pages that answer "what needs my attention?" rather than show totals.
 */
class DashboardController extends Controller
{
    private const URGENCY = ['red' => 0, 'yellow' => 1];

    public function admin(): JsonResponse
    {
        return response()->json($this->overview());
    }

    public function officer(): JsonResponse
    {
        return response()->json($this->overview());
    }

    public function staff(Request $request): JsonResponse
    {
        $user = $request->user();

        $myTasks = Task::query()
            ->where('assigned_to', $user->id)
            ->whereHas('event', fn ($q) => $q->where('status', 'upcoming'))
            ->with([
                'event:id,name,event_date,end_date,event_time,end_time,venue_id,venue_details,needs_preparation,status',
                'event.venue:id,name',
                'assignee:id,name,email,role',
            ])
            ->orderBy('due_date')
            ->get();

        $myEvents = Event::visibleTo($user)
            ->with('venue:id,name')
            ->where('status', 'upcoming')
            ->orderBy('event_date')
            ->get(['id', 'name', 'venue_id', 'venue_details', 'event_date', 'end_date', 'event_time', 'end_time', 'needs_preparation', 'status']);

        return response()->json([
            // Only what's left to do, matching the My Tasks page's default view.
            'openTasks' => $myTasks->where('status', '!=', 'done')->values(),
            'myEvents' => $myEvents,
            'summary' => [
                'pendingTasks' => $myTasks->where('status', 'pending')->count(),
                'inProgressTasks' => $myTasks->where('status', 'in_progress')->count(),
                'doneTasks' => $myTasks->where('status', 'done')->count(),
                'upcomingEvents' => $myEvents->count(),
            ],
        ]);
    }

    private function overview(): array
    {
        $today = today();
        $weekEnd = today()->addDays(6);

        // Upcoming events the EMO prepares, and how their preparation is going.
        $prepared = Event::with(['tasks:id,event_id,status,assigned_to,due_date', 'venue:id,name'])
            ->where('needs_preparation', true)
            ->where('status', 'upcoming')
            ->orderBy('event_date')
            ->get();

        $attention = $prepared
            ->filter(fn (Event $e) => isset(self::URGENCY[$e->readiness]))
            ->sortBy([
                fn ($a, $b) => self::URGENCY[$a->readiness] <=> self::URGENCY[$b->readiness],
                fn ($a, $b) => $a->event_date <=> $b->event_date,
            ])
            ->values();

        $tasks = $prepared->flatMap->tasks;
        $open = $tasks->where('status', '!=', 'done');

        // Everything on the schedule in the next 7 days, including multi-day
        // events that started earlier and are still running.
        $thisWeek = Event::with(['venue.building', 'tasks:id,event_id,status,assigned_to'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('event_date', '<=', $weekEnd)
            ->where(fn ($q) => $q->whereDate('event_date', '>=', $today)->orWhereDate('end_date', '>=', $today))
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        return [
            'today' => $today->toDateString(),
            'stats' => [
                'thisWeek' => $thisWeek->count(),
                'preparedUpcoming' => $prepared->count(),
                'needAttention' => $attention->count(),
                'openTasks' => $open->count(),
                'overdueTasks' => $open->filter(fn (Task $t) => $t->due_date->lt($today))->count(),
                'tasksDone' => $tasks->where('status', 'done')->count(),
                'tasksTotal' => $tasks->count(),
            ],
            'needsAttention' => $attention->map(fn (Event $e) => [
                ...$this->row($e),
                'task_summary' => ['total' => $e->tasks->count(), 'done' => $e->tasks->where('status', 'done')->count()],
            ]),
            'thisWeek' => $thisWeek->map(fn (Event $e) => [
                ...$this->row($e),
                'department' => $e->department,
                'building' => $e->venue?->building?->only(['name', 'color']),
            ]),
        ];
    }

    private function row(Event $e): array
    {
        return [
            'id' => $e->id,
            'name' => $e->name,
            'event_date' => $e->event_date->toDateString(),
            'end_date' => $e->end_date?->toDateString(),
            'event_time' => $e->event_time,
            'end_time' => $e->end_time,
            'location' => $e->location,
            'needs_preparation' => $e->needs_preparation,
            'readiness' => $e->readiness,
        ];
    }
}
