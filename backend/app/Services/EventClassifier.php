<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class EventClassifier
{
    public static function classify(Event $event): string
    {
        if ($event->status === 'completed') {
            return 'completed';
        }

        $tasks = $event->relationLoaded('tasks') ? $event->tasks : $event->tasks()->get();

        if ($tasks->isEmpty()) {
            return 'yellow';
        }

        $taskCount = $tasks->count();
        $doneCount = $tasks->where('status', 'done')->count();
        $unassignedCount = $tasks->whereNull('assigned_to')->count();

        $completedPct = ($doneCount / $taskCount) * 100;
        $unassignedPct = ($unassignedCount / $taskCount) * 100;

        $daysRemaining = Carbon::today()->diffInDays(Carbon::parse($event->event_date), false);

        if ($completedPct < 40 || $daysRemaining <= 2 || $unassignedPct > 50) {
            return 'red';
        }

        if ($completedPct < 70 || $daysRemaining <= 6 || $unassignedCount > 0) {
            return 'yellow';
        }

        return 'green';
    }
}
