<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(): JsonResponse
    {
        return response()->json(['stats' => $this->stats()]);
    }

    public function officer(): JsonResponse
    {
        return response()->json(['stats' => $this->stats()]);
    }

    public function staff(Request $request): JsonResponse
    {
        $user = $request->user();

        $myTasks = Task::query()
            ->where('assigned_to', $user->id)
            ->whereHas('event', fn ($q) => $q->where('status', 'upcoming'))
            ->with('event:id,name,event_date,status')
            ->orderBy('due_date')
            ->get(['id', 'name', 'status', 'priority', 'due_date', 'event_id']);

        $myEvents = Event::visibleTo($user)
            ->where('status', 'upcoming')
            ->orderBy('event_date')
            ->get(['id', 'name', 'venue', 'event_date', 'event_time', 'status']);

        return response()->json([
            'myTasks' => $myTasks,
            'myEvents' => $myEvents,
            'summary' => [
                'pendingTasks' => $myTasks->where('status', 'pending')->count(),
                'inProgressTasks' => $myTasks->where('status', 'in_progress')->count(),
                'doneTasks' => $myTasks->where('status', 'done')->count(),
                'upcomingEvents' => $myEvents->count(),
            ],
        ]);
    }

    private function stats(): array
    {
        return [
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('status', 'upcoming')->count(),
            'totalTasks' => Task::count(),
            'tasksDone' => Task::where('status', 'done')->count(),
            'activeStaff' => User::where('role', 'staff')
                ->where('is_active', true)
                ->whereHas('eventsAssigned', fn ($q) => $q->where('status', 'upcoming'))
                ->count(),
        ];
    }
}
