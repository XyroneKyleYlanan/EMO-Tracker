<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// The event lifecycle: upcoming → ongoing → completed by date and time,
// cancelled by hand, and rescheduled as a mark on an event that moved.
class EventStatusTest extends TestCase
{
    use RefreshDatabase;

    private function statusAt(string $time, Event $event): array
    {
        $this->travelTo(today()->setTimeFromTimeString($time));
        $this->getJson('/api/events');

        $fresh = $event->fresh();

        return [$fresh->status, $fresh->ongoing];
    }

    public function test_an_event_is_ongoing_while_it_runs_and_completed_when_it_ends(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $event = Event::factory()->create(['event_date' => today()->toDateString(), 'event_time' => '09:00', 'end_time' => '17:00']);

        $this->assertSame(['upcoming', false], $this->statusAt('08:59', $event));
        $this->assertSame(['upcoming', true], $this->statusAt('09:00', $event));
        $this->assertSame(['upcoming', true], $this->statusAt('16:59', $event));
        $this->assertSame(['completed', false], $this->statusAt('17:00', $event));
    }

    public function test_without_times_an_event_runs_all_day(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $event = Event::factory()->create(['event_date' => today()->toDateString(), 'event_time' => null]);

        $this->assertSame(['upcoming', true], $this->statusAt('00:00', $event));
        $this->assertSame(['upcoming', true], $this->statusAt('23:59', $event));

        $this->travelTo(today()->addDay()->setTime(0, 0));
        $this->getJson('/api/events');
        $this->assertSame('completed', $event->fresh()->status);
    }

    public function test_a_multi_day_event_is_ongoing_from_its_first_start_to_its_last_end(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $event = Event::factory()->create([
            'event_date' => today()->subDay()->toDateString(), 'end_date' => today()->addDay()->toDateString(),
            'event_time' => '08:00', 'end_time' => '17:00',
        ]);

        // Evenings between the days still count: the event period is under way.
        $this->assertSame(['upcoming', true], $this->statusAt('20:00', $event));
        $this->assertTrue($this->getJson("/api/events/{$event->id}")->json('event.ongoing'));
    }

    public function test_tasks_lock_once_the_event_has_ended_not_at_midnight(): void
    {
        $staff = User::factory()->staff()->create();
        $event = Event::factory()->create(['event_date' => today()->toDateString(), 'event_time' => '09:00', 'end_time' => '12:00']);
        $task = Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $staff->id]);
        Sanctum::actingAs($staff);

        $this->travelTo(today()->setTime(11, 0));
        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertOk();

        $this->travelTo(today()->setTime(12, 30));
        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done'])->assertForbidden();
    }

    public function test_a_reschedule_remembers_where_the_event_was_first_scheduled(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $first = today()->addDays(10)->toDateString();
        $event = Event::factory()->create(['event_date' => $first, 'event_time' => '09:00:00']);

        // A correction isn't a reschedule.
        $this->patchJson("/api/events/{$event->id}", ['event_time' => '10:00', 'rescheduled' => false])
            ->assertJsonPath('event.original_date', null);

        $this->patchJson("/api/events/{$event->id}", ['event_date' => today()->addDays(20)->toDateString(), 'rescheduled' => true])
            ->assertJsonPath('event.original_date', $first)
            ->assertJsonPath('event.original_time', '10:00:00')
            ->assertJsonPath('event.status', 'upcoming');

        // Moved again: still "rescheduled from" the very first date.
        $this->patchJson("/api/events/{$event->id}", ['event_date' => today()->addDays(25)->toDateString(), 'rescheduled' => true])
            ->assertJsonPath('event.original_date', $first);

        // Editing other details doesn't touch it.
        $this->patchJson("/api/events/{$event->id}", ['remarks' => 'Bigger hall', 'event_type' => 'external'])
            ->assertJsonPath('event.original_date', $first)
            ->assertJsonPath('event.event_type', 'external');

        // Back to the original slot: no longer rescheduled.
        $this->patchJson("/api/events/{$event->id}", ['event_date' => $first, 'event_time' => '10:00', 'rescheduled' => true])
            ->assertJsonPath('event.original_date', null);
    }

    public function test_moving_only_the_time_is_a_reschedule_too(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $event = Event::factory()->create(['event_date' => today()->addDays(3)->toDateString(), 'event_time' => '09:00:00']);

        $this->patchJson("/api/events/{$event->id}", ['event_time' => '14:00', 'rescheduled' => true])
            ->assertJsonPath('event.original_date', today()->addDays(3)->toDateString())
            ->assertJsonPath('event.original_time', '09:00:00');
    }

    public function test_a_cancelled_event_puts_its_tasks_on_hold(): void
    {
        $staff = User::factory()->staff()->create();
        $live = Event::factory()->inDays(5)->create();
        $cancelled = Event::factory()->inDays(6)->create(['status' => 'cancelled']);
        Task::factory()->create(['event_id' => $live->id, 'assigned_to' => $staff->id]);
        Task::factory()->create(['event_id' => $cancelled->id, 'assigned_to' => $staff->id]);

        Sanctum::actingAs($staff);
        $mine = $this->getJson('/api/my-tasks')->assertOk();
        $this->assertCount(2, $mine->json('tasks'), 'still listed, so nothing silently disappears');
        $this->assertSame(['pending' => 1, 'in_progress' => 0, 'done' => 0, 'total' => 1], $mine->json('summary'));
        $this->assertCount(1, $this->getJson('/api/dashboard/staff')->json('openTasks'));

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson("/api/events/{$cancelled->id}/tasks", ['name' => 'Book sound', 'due_date' => today()->addDay()->toDateString()])
            ->assertStatus(422);
    }

    public function test_events_are_internal_unless_marked_external(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $details = ['name' => 'Seminar', 'venue_details' => 'Room 1', 'event_date' => today()->addDays(3)->toDateString()];

        $this->postJson('/api/events', $details)->assertCreated()->assertJsonPath('event.event_type', 'internal');
        $external = $this->postJson('/api/events', [...$details, 'event_type' => 'external'])->assertCreated()->json('event.id');
        $this->postJson('/api/events', [...$details, 'event_type' => 'partner'])->assertStatus(422)->assertJsonValidationErrors('event_type');

        $row = collect($this->getJson('/api/schedule')->json('events'))->firstWhere('id', $external);
        $this->assertSame('external', $row['event_type']);
    }

    public function test_budget_is_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('events', 'budget'));

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson('/api/events', [
            'name' => 'Seminar', 'venue_details' => 'Room 1', 'event_date' => today()->addDays(3)->toDateString(), 'budget' => 5000,
        ])->assertCreated()->assertJsonMissingPath('event.budget');
    }
}
