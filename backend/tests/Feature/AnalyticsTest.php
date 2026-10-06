<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday, October 14, 10 AM.
        $this->travelTo('2026-10-14 10:00:00');
        $hall = Venue::create(['name' => 'University Hall'])->id;
        $som = Venue::create(['name' => 'SOM MPH'])->id;
        $event = fn (array $attributes) => Event::factory()->scheduleOnly()->create($attributes);

        $event(['name' => 'Done', 'event_date' => '2026-10-02', 'status' => 'completed', 'venue_id' => $hall]);
        $event(['name' => 'Happening now', 'event_date' => '2026-10-14', 'event_time' => '08:00', 'end_time' => '17:00', 'venue_id' => $som, 'event_type' => 'external']);
        $event(['name' => 'Called off', 'event_date' => '2026-10-20', 'status' => 'cancelled', 'venue_id' => $hall]);
        $event(['name' => 'Moved', 'event_date' => '2026-10-25', 'original_date' => '2026-10-18', 'venue_id' => $hall]);
        $event(['name' => 'Next month', 'event_date' => '2026-11-05', 'venue_id' => $som]);
        $event(['name' => 'Last year', 'event_date' => '2025-12-10', 'status' => 'completed']);

        Sanctum::actingAs(User::factory()->officer()->create());
    }

    public function test_status_counts_cover_every_event_and_add_up_to_the_total(): void
    {
        $schedule = $this->getJson('/api/analytics?period=month')->assertOk()->json('schedule');

        $this->assertSame(4, $schedule['total']);
        $this->assertSame(['upcoming' => 1, 'ongoing' => 1, 'completed' => 1, 'cancelled' => 1], $schedule['status']);
        $this->assertSame(1, $schedule['rescheduled'], 'also counted in its current status');
        $this->assertSame(['internal' => 3, 'external' => 1], $schedule['type']);
        // Cancelled bookings don't count toward a venue.
        $this->assertSame([['name' => 'University Hall', 'events' => 2], ['name' => 'SOM MPH', 'events' => 1]], $schedule['venues']);
    }

    public function test_periods_are_calendar_periods(): void
    {
        $total = fn (string $period) => $this->getJson("/api/analytics?period={$period}")->json('schedule.total');

        $this->assertSame(1, $total('week'), 'Sunday Oct 11 to Saturday Oct 17');
        $this->assertSame(4, $total('month'));
        $this->assertSame(5, $total('year'));
        $this->assertSame(6, $total('all'));
        $this->assertSame(6, $this->getJson('/api/analytics')->json('schedule.total'), 'all time by default');
    }

    public function test_staff_cannot_see_analytics(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->getJson('/api/analytics')->assertForbidden();
    }
}
