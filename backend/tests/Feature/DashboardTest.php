<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_what_needs_attention_and_this_weeks_schedule(): void
    {
        $staff = User::factory()->staff()->create();

        // Prepared and Critical (no work done, 2 days away), with one overdue task.
        $critical = Event::factory()->inDays(2)->create(['name' => 'Faculty Night']);
        Task::factory()->create(['event_id' => $critical->id, 'assigned_to' => $staff->id, 'due_date' => today()->subDay()->toDateString()]);
        // Prepared and At Risk (half done, 20 days away).
        $atRisk = Event::factory()->inDays(20)->create(['name' => 'Quiz Bee']);
        Task::factory()->done()->create(['event_id' => $atRisk->id, 'assigned_to' => $staff->id]);
        Task::factory()->create(['event_id' => $atRisk->id, 'assigned_to' => $staff->id]);
        // Prepared and On Track: doesn't need attention.
        $fine = Event::factory()->inDays(25)->create(['name' => 'Orientation']);
        Task::factory()->done()->create(['event_id' => $fine->id, 'assigned_to' => $staff->id]);

        // Schedule-only bookings: this week, a multi-day one still running, cancelled, and next month.
        Event::factory()->scheduleOnly()->inDays(3)->create(['name' => 'CAS Seminar']);
        Event::factory()->scheduleOnly()->create(['name' => 'Nurses Week', 'event_date' => today()->subDay()->toDateString(), 'end_date' => today()->addDay()->toDateString()]);
        Event::factory()->scheduleOnly()->inDays(4)->create(['name' => 'Cancelled Talk', 'status' => 'cancelled']);
        Event::factory()->scheduleOnly()->inDays(30)->create(['name' => 'Far Away']);

        Sanctum::actingAs(User::factory()->officer()->create());
        $home = $this->getJson('/api/dashboard/officer')->assertOk();

        $this->assertSame(['Faculty Night', 'Quiz Bee'], collect($home->json('needsAttention'))->pluck('name')->all());
        $this->assertSame(['Nurses Week', 'Faculty Night', 'CAS Seminar'], collect($home->json('thisWeek'))->pluck('name')->all());
        $this->assertSame([
            'thisWeek' => 3, 'preparedUpcoming' => 3, 'needAttention' => 2,
            'openTasks' => 2, 'overdueTasks' => 1, 'tasksDone' => 2, 'tasksTotal' => 4,
        ], $home->json('stats'));
    }

    public function test_staff_home_lists_only_open_tasks(): void
    {
        $staff = User::factory()->staff()->create();
        $event = Event::factory()->inDays(10)->create();
        $event->staff()->attach($staff);
        Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $staff->id, 'name' => 'Open one', 'description' => 'Details']);
        Task::factory()->done()->create(['event_id' => $event->id, 'assigned_to' => $staff->id, 'name' => 'Done one']);

        Sanctum::actingAs($staff);
        $home = $this->getJson('/api/dashboard/staff')->assertOk();

        $this->assertSame(['Open one'], collect($home->json('openTasks'))->pluck('name')->all());
        $this->assertSame('Details', $home->json('openTasks.0.description'));
        $this->assertSame(1, $home->json('summary.doneTasks'));
        $this->assertSame(1, $home->json('summary.upcomingEvents'));
    }
}
