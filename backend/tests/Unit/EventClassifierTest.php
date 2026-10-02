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
     * Each task is [status, assigned_to].
     */
    public static function cases(): array
    {
        $assigned = 1;

        return [
            'no tasks is at risk' => [30, [], 'yellow'],
            'all done tomorrow is on track' => [1, [['done', $assigned], ['done', $assigned]], 'green'],
            'all done today is on track' => [0, [['done', $assigned]], 'green'],
            'under 40% done is critical' => [20, [['done', $assigned], ['pending', $assigned], ['pending', $assigned], ['pending', $assigned]], 'red'],
            'open work within 2 days is critical' => [1, [['done', $assigned], ['done', $assigned], ['done', $assigned], ['pending', $assigned]], 'red'],
            'majority unassigned is critical' => [20, [['done', null], ['done', null], ['done', null], ['done', $assigned], ['pending', $assigned]], 'red'],
            'under 70% done is at risk' => [20, [['done', $assigned], ['pending', $assigned]], 'yellow'],
            'open work within 6 days is at risk' => [5, [['done', $assigned], ['done', $assigned], ['done', $assigned], ['pending', $assigned]], 'yellow'],
            'any unassigned task is at risk' => [20, [['done', $assigned], ['done', $assigned], ['done', $assigned], ['pending', null]], 'yellow'],
            'mostly done, staffed, and far off is on track' => [20, [['done', $assigned], ['done', $assigned], ['done', $assigned], ['pending', $assigned]], 'green'],
        ];
    }

    #[DataProvider('cases')]
    public function test_classifies_upcoming_events(int $daysAway, array $tasks, string $expected): void
    {
        $this->assertSame($expected, EventClassifier::classify($this->event($daysAway, $tasks)));
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

        $event->setRelation('tasks', collect($tasks)->map(
            fn ($task) => new Task(['status' => $task[0], 'assigned_to' => $task[1]])
        ));

        return $event;
    }
}
