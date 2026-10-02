<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Task;
use App\Services\EventClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EventClassifierTest extends TestCase
{
    /**
     * Each task is [status, assigned_to, due in days (default: the event day)].
     */
    public static function cases(): array
    {
        $a = 1;

        return [
            'no tasks is at risk' => [30, [], 'yellow', 'No tasks yet'],
            'all done tomorrow is on track' => [1, [['done', $a], ['done', $a]], 'green', 'All tasks done'],
            'all done today is on track' => [0, [['done', $a]], 'green', 'All tasks done'],

            'an overdue task is critical, however far off' => [30, [['done', $a], ['done', $a], ['done', $a], ['pending', $a, -1]], 'red', '1 task is overdue'],
            'open work within 2 days is critical' => [1, [['done', $a], ['done', $a], ['done', $a], ['pending', $a]], 'red', '1 task still open, event is tomorrow'],
            'open work on a multi-day event under way is critical' => [-1, [['in_progress', $a, 1], ['pending', $a, 1]], 'red', '2 tasks still open, event has started'],
            'under 40% done within a week is critical' => [5, [['done', $a], ['pending', $a], ['pending', $a], ['pending', $a]], 'red', 'Only 25% done, 5 days to go'],
            'most open tasks without an owner within a week is critical' => [5, [['done', $a], ['done', $a], ['done', $a], ['pending', null], ['pending', null], ['pending', $a]], 'red', '2 of 3 open tasks have no owner, 5 days to go'],

            'one task in progress two weeks out is at risk' => [14, [['in_progress', $a]], 'yellow', '0% done, 14 days to go'],
            'past 40% but under 70% within a week is at risk' => [5, [['done', $a], ['done', $a], ['pending', $a], ['pending', $a]], 'yellow', '50% done, 5 days to go'],
            'an open task without an owner is at risk' => [20, [['done', $a], ['done', $a], ['done', $a], ['pending', null]], 'yellow', '1 open task has no owner'],

            'work not started more than two weeks out is on track' => [15, [['pending', $a], ['pending', $a]], 'green', '0% done, nothing overdue'],
            'mostly done within a week is on track' => [5, [['done', $a], ['done', $a], ['done', $a], ['done', $a], ['pending', $a]], 'green', '80% done, nothing overdue'],
            'a done task needs no owner' => [20, [['done', null], ['pending', $a]], 'green', '50% done, nothing overdue'],
            'due today is not overdue yet' => [10, [['done', $a], ['done', $a], ['done', $a], ['pending', $a, 0]], 'green', '75% done, nothing overdue'],
        ];
    }

    #[DataProvider('cases')]
    public function test_classifies_upcoming_events(int $daysAway, array $tasks, string $expected, string $reason): void
    {
        $event = $this->event($daysAway, $tasks);

        $this->assertSame($expected, EventClassifier::classify($event));
        $this->assertSame($reason, EventClassifier::reason($event));
    }

    public function test_events_the_emo_does_not_prepare_are_scheduled(): void
    {
        $event = $this->event(1, []);
        $event->needs_preparation = false;

        $this->assertSame('scheduled', EventClassifier::classify($event));
    }

    public function test_cancelled_events_are_cancelled(): void
    {
        $event = $this->event(5, [['pending', null]]);
        $event->status = 'cancelled';

        $this->assertSame('cancelled', EventClassifier::classify($event));
    }

    public function test_completed_events_stay_completed(): void
    {
        $event = $this->event(-3, [['pending', null]]);
        $event->status = 'completed';

        $this->assertSame('completed', EventClassifier::classify($event));
    }

    private function event(int $daysAway, array $tasks): Event
    {
        $event = new Event([
            'event_date' => today()->addDays($daysAway)->toDateString(),
            'status' => 'upcoming',
            'needs_preparation' => true,
        ]);

        $event->setRelation('tasks', collect($tasks)->map(fn ($task) => new Task([
            'status' => $task[0],
            'assigned_to' => $task[1],
            'due_date' => today()->addDays($task[2] ?? $daysAway)->toDateString(),
        ])));

        return $event;
    }
}
