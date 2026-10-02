<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_events_lock_even_if_nobody_opened_the_events_page(): void
    {
        $staff = User::factory()->staff()->create();
        // Date has passed but the stored status was never updated.
        $event = Event::factory()->create(['event_date' => today()->subDays(3)->toDateString(), 'status' => 'upcoming']);
        $task = Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $staff->id]);

        Sanctum::actingAs($staff);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done'])->assertForbidden();
        $this->assertSame('completed', $event->fresh()->status);
        $this->assertSame('pending', $task->fresh()->status);
    }

    public function test_officer_cannot_edit_or_reopen_a_completed_event(): void
    {
        $event = Event::factory()->inDays(-5)->create(['name' => 'Foundation Day']);

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->patchJson("/api/events/{$event->id}", ['status' => 'upcoming'])->assertForbidden();
        $this->patchJson("/api/events/{$event->id}", ['event_date' => today()->addDays(5)->toDateString()])->assertForbidden();

        $this->assertSame('completed', $event->fresh()->status);
        $this->assertSame('Foundation Day', $event->fresh()->name);
    }

    public function test_admin_rescheduling_a_completed_event_reopens_it(): void
    {
        $event = Event::factory()->inDays(-5)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/events/{$event->id}", ['event_date' => today()->addDays(5)->toDateString()])
            ->assertOk()
            ->assertJsonPath('event.status', 'upcoming');
    }

    public function test_status_follows_the_event_date(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $created = $this->postJson('/api/events', [
            'name' => 'Backfilled Seminar',
            'venue_details' => 'Room 101',
            'event_date' => today()->subDay()->toDateString(),
            'event_time' => '09:00',
        ])->assertCreated();
        $this->assertSame('completed', $created->json('event.status'));

        $upcoming = Event::factory()->inDays(10)->create();
        $this->patchJson("/api/events/{$upcoming->id}", ['event_date' => today()->subDay()->toDateString()])
            ->assertOk()
            ->assertJsonPath('event.status', 'completed');
    }

    public function test_dates_are_serialized_as_plain_dates(): void
    {
        $event = Event::factory()->create(['event_date' => '2026-12-01']);
        Task::factory()->create(['event_id' => $event->id, 'due_date' => '2026-11-20']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/events/{$event->id}")
            ->assertJsonPath('event.event_date', '2026-12-01')
            ->assertJsonPath('event.tasks.0.due_date', '2026-11-20');
    }

    public function test_analytics_ranks_urgent_events_by_readiness_then_date(): void
    {
        $staff = User::factory()->staff()->create();

        $green = Event::factory()->inDays(3)->create();
        Task::factory()->done()->create(['event_id' => $green->id, 'assigned_to' => $staff->id]);

        $yellow = Event::factory()->inDays(10)->create();
        Task::factory()->done()->create(['event_id' => $yellow->id, 'assigned_to' => $staff->id]);
        Task::factory()->create(['event_id' => $yellow->id, 'assigned_to' => $staff->id]);

        $red = Event::factory()->inDays(20)->create();
        Task::factory()->create(['event_id' => $red->id, 'assigned_to' => $staff->id]);

        Sanctum::actingAs(User::factory()->officer()->create());

        $ranked = collect($this->getJson('/api/analytics')->assertOk()->json('topUrgent'))->pluck('id')->all();
        $this->assertSame([$red->id, $yellow->id, $green->id], $ranked);
    }
}
