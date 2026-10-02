<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'all');

        $range = match ($period) {
            'week' => [today(), today()->copy()->addDays(7)],
            'month' => [today(), today()->copy()->addDays(30)],
            default => null,
        };

        // Readiness only covers events the EMO prepares; schedule-only and cancelled events are left out.
        $eventsQuery = Event::with([Event::READINESS_TASKS, 'venue:id,name'])
            ->where('needs_preparation', true)
            ->where('status', '!=', 'cancelled');
        if ($range) {
            $eventsQuery->whereBetween('event_date', $range);
        }
        $events = $eventsQuery->get();

        $totalTasks = $events->sum(fn ($e) => $e->tasks->count());
        $tasksDone = $events->sum(fn ($e) => $e->tasks->where('status', 'done')->count());

        // Members with at least one task on these events.
        $activeMembers = User::where('is_active', true)
            ->whereIn('id', $events->flatMap->tasks->pluck('assigned_to')->filter()->unique())
            ->count();

        $distribution = ['green' => 0, 'yellow' => 0, 'red' => 0, 'completed' => 0];
        foreach ($events as $event) {
            $key = $event->readiness;
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }

        $urgencyRank = ['red' => 0, 'yellow' => 1, 'green' => 2];

        $topUrgent = $events
            ->where('status', 'upcoming')
            ->sortBy([
                fn ($a, $b) => $urgencyRank[$a->readiness] <=> $urgencyRank[$b->readiness],
                fn ($a, $b) => $a->event_date <=> $b->event_date,
            ])
            ->take(5)
            ->values()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'event_date' => $e->event_date->toDateString(),
                    'event_time' => $e->event_time,
                    'location' => $e->location,
                    'readiness' => $e->readiness,
                    'task_summary' => [
                        'total' => $e->tasks->count(),
                        'done' => $e->tasks->where('status', 'done')->count(),
                    ],
                ];
            });

        return response()->json([
            'period' => $period,
            'stats' => [
                'totalEvents' => $events->count(),
                'totalTasks' => $totalTasks,
                'tasksDone' => $tasksDone,
                'tasksDonePercent' => $totalTasks > 0 ? round(($tasksDone / $totalTasks) * 100) : 0,
                'activeMembers' => $activeMembers,
            ],
            'distribution' => $distribution,
            'topUrgent' => $topUrgent,
        ]);
    }
}
