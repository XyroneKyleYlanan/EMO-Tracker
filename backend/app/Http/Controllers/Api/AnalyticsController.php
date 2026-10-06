<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
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

    /**
     * Every event on the schedule: how many are upcoming, ongoing, completed or
     * cancelled (these add up to the total), how many were rescheduled, the
     * internal/external split, and the venues booked most.
     */
    private function schedule($events): array
    {
        $status = ['upcoming' => 0, 'ongoing' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($events as $event) {
            $status[$event->lifecycle]++;
        }

        // Cancelled bookings didn't use the venue.
        $venues = $events->where('status', '!=', 'cancelled')
            ->whereNotNull('venue_id')
            ->groupBy('venue_id')
            ->map(fn ($bookings) => ['name' => $bookings->first()->venue->name, 'events' => $bookings->count()])
            ->sortByDesc('events')
            ->take(5)
            ->values();

        return [
            'total' => $events->count(),
            'status' => $status,
            'rescheduled' => $events->whereNotNull('original_date')->count(),
            'type' => [
                'internal' => $events->where('event_type', 'internal')->count(),
                'external' => $events->where('event_type', 'external')->count(),
            ],
            'venues' => $venues,
        ];
    }

    // Readiness only covers events the EMO prepares; schedule-only and cancelled events are left out.
    private function preparation($events): array
    {
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
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'event_date' => $e->event_date->toDateString(),
                'event_time' => $e->event_time,
                'location' => $e->location,
                'status' => $e->status,
                'ongoing' => $e->ongoing,
                'needs_preparation' => $e->needs_preparation,
                'readiness' => $e->readiness,
                'task_summary' => [
                    'total' => $e->tasks->count(),
                    'done' => $e->tasks->where('status', 'done')->count(),
                ],
            ]);

        return [
            'stats' => [
                'totalEvents' => $events->count(),
                'totalTasks' => $totalTasks,
                'tasksDone' => $tasksDone,
                'tasksDonePercent' => $totalTasks > 0 ? round(($tasksDone / $totalTasks) * 100) : 0,
                'activeMembers' => $activeMembers,
            ],
            'distribution' => $distribution,
            'topUrgent' => $topUrgent,
        ];
    }
}
