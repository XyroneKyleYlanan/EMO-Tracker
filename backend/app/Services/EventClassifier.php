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
