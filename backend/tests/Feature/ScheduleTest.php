<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_role_sees_and_can_open_the_whole_schedule(): void
    {
        $staff = User::factory()->staff()->create();
        $other = Event::factory()->scheduleOnly()->create(['event_date' => today()->addDays(4)->toDateString()]);
        Event::factory()->scheduleOnly()->create(['event_date' => today()->addDays(3)->toDateString()]);

        Sanctum::actingAs($staff);

        $this->assertCount(2, $this->getJson('/api/schedule?year='.today()->year)->assertOk()->json('events'));
        $this->getJson("/api/events/{$other->id}")->assertOk();
    }

    public function test_schedule_is_split_by_year(): void
    {
        Event::factory()->scheduleOnly()->create(['event_date' => '2026-11-05']);
        Event::factory()->scheduleOnly()->create(['event_date' => '2027-01-29']);

        Sanctum::actingAs(User::factory()->officer()->create());

        $response = $this->getJson('/api/schedule?year=2027')->assertOk();
        $this->assertSame(['2027-01-29'], collect($response->json('events'))->pluck('event_date')->all());
        $this->assertContains(2026, $response->json('years'));
        $this->assertContains(2027, $response->json('years'));
    }

    public function test_only_the_admin_edits_event_details(): void
    {
        $event = Event::factory()->create();
        $details = ['name' => 'Seminar', 'venue_details' => 'Room 1', 'event_date' => today()->addDay()->toDateString()];

        foreach ([User::factory()->officer()->create(), User::factory()->staff()->create()] as $user) {
            Sanctum::actingAs($user);
            $this->postJson('/api/events', $details)->assertForbidden();
            $this->patchJson("/api/events/{$event->id}", ['name' => 'Renamed'])->assertForbidden();
        }

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson('/api/events', $details)->assertCreated();
        $this->patchJson("/api/events/{$event->id}", ['name' => 'Renamed'])->assertOk();
    }

    public function test_adding_a_task_starts_readiness_tracking(): void
    {
        $event = Event::factory()->scheduleOnly()->create();

        Sanctum::actingAs(User::factory()->officer()->create());
        $this->assertSame('scheduled', $event->fresh()->readiness);

        $this->postJson("/api/events/{$event->id}/tasks", ['name' => 'Book sound system', 'due_date' => today()->addDay()->toDateString()])
            ->assertCreated();

        $this->assertTrue($event->fresh()->needs_preparation);
        $this->assertNotSame('scheduled', $event->fresh()->readiness);
    }

    public function test_analytics_leaves_out_schedule_only_and_cancelled_events(): void
    {
        $staff = User::factory()->staff()->create();
        $prepared = Event::factory()->inDays(20)->create();
        Task::factory()->done()->create(['event_id' => $prepared->id, 'assigned_to' => $staff->id]);
        Event::factory()->scheduleOnly()->inDays(10)->create();
        Event::factory()->inDays(5)->create(['status' => 'cancelled']);

        Sanctum::actingAs(User::factory()->officer()->create());

        $response = $this->getJson('/api/analytics')->assertOk();
        $this->assertSame(1, $response->json('stats.totalEvents'));
        $this->assertSame([$prepared->id], collect($response->json('topUrgent'))->pluck('id')->all());
    }

    public function test_cancelling_keeps_the_event_and_survives_edits_until_restored(): void
    {
        $event = Event::factory()->inDays(10)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/events/{$event->id}", ['cancelled' => true])->assertJsonPath('event.status', 'cancelled');
        $this->patchJson("/api/events/{$event->id}", ['remarks' => 'Moved online'])->assertJsonPath('event.status', 'cancelled');
        $this->patchJson("/api/events/{$event->id}", ['cancelled' => false])->assertJsonPath('event.status', 'upcoming');

        // Passing time never turns a cancelled event into a completed one.
        $past = Event::factory()->create(['event_date' => today()->subDays(2)->toDateString(), 'status' => 'cancelled']);
        $this->getJson('/api/events');
        $this->assertSame('cancelled', $past->fresh()->status);
    }

    public function test_multi_day_events_stay_upcoming_until_their_last_day(): void
    {
        $event = Event::factory()->create([
            'event_date' => today()->subDays(2)->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/events');
        $this->assertSame('upcoming', $event->fresh()->status);

        $event->update(['end_date' => today()->subDay()->toDateString()]);
        $this->getJson('/api/events');
        $this->assertSame('completed', $event->fresh()->status);
    }

    public function test_times_and_dates_are_validated(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $base = ['name' => 'Seminar', 'venue_details' => 'Room 1', 'event_date' => today()->addDays(3)->toDateString()];

        $this->postJson('/api/events', [...$base, 'event_time' => '13:00', 'end_time' => '09:00'])
            ->assertStatus(422)->assertJsonValidationErrors('end_time');
        $this->postJson('/api/events', [...$base, 'end_date' => today()->toDateString()])
            ->assertStatus(422)->assertJsonValidationErrors('end_date');
        $this->postJson('/api/events', [...$base, 'venue_details' => null])
            ->assertStatus(422)->assertJsonValidationErrors('venue_details');

        // Overnight across days is fine, and time can be left out entirely.
        $this->postJson('/api/events', [...$base, 'end_date' => today()->addDays(4)->toDateString(), 'event_time' => '20:00', 'end_time' => '02:00'])
            ->assertCreated();
        $this->postJson('/api/events', $base)->assertCreated()->assertJsonPath('event.event_time', null);
    }

    public function test_admin_adds_venues_and_location_combines_venue_and_room(): void
    {
        $building = Building::create(['name' => 'SOM', 'color' => '#B6D7A8']);

        Sanctum::actingAs(User::factory()->officer()->create());
        $this->postJson('/api/venues', ['name' => 'SOM Building'])->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $venueId = $this->postJson('/api/venues', ['name' => 'SOM Building', 'building_id' => $building->id])
            ->assertCreated()->json('venue.id');
        $this->postJson('/api/venues', ['name' => 'SOM Building'])->assertStatus(422);

        $this->postJson('/api/events', [
            'name' => 'Team Building',
            'venue_id' => $venueId,
            'venue_details' => '504–507',
            'event_date' => today()->addDays(3)->toDateString(),
        ])->assertCreated()->assertJsonPath('event.location', 'SOM Building 504–507');

        $row = collect($this->getJson('/api/schedule')->json('events'))->firstWhere('name', 'Team Building');
        $this->assertSame('#B6D7A8', $row['building']['color']);
        $this->assertSame(Venue::count(), count($this->getJson('/api/venues')->json('venues')));
    }

    public function test_department_suggestions_list_each_department_once(): void
    {
        Event::factory()->scheduleOnly()->create(['department' => 'CAS']);
        Event::factory()->scheduleOnly()->create(['department' => 'CAS']);
        Event::factory()->scheduleOnly()->create(['department' => 'CBA']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/departments')->assertOk()->assertExactJson(['departments' => ['CAS', 'CBA']]);
    }
}
