<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_users_tokens_are_rejected_on_every_route(): void
    {
        $staff = User::factory()->staff()->create();
        $task = Task::factory()->create(['assigned_to' => $staff->id]);
        $headers = ['Authorization' => 'Bearer '.$staff->createToken('t')->plainTextToken];

        $staff->update(['is_active' => false]);

        $this->withHeaders($headers)->getJson('/api/events')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withHeaders($headers)->getJson('/api/my-tasks')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withHeaders($headers)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertUnauthorized();

        $this->assertSame('pending', $task->fresh()->status);
    }

    public function test_deactivating_a_user_revokes_their_tokens(): void
    {
        $staff = User::factory()->staff()->create();
        $staff->createToken('t');

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->deleteJson("/api/users/{$staff->id}")->assertOk();

        $this->assertSame(0, $staff->tokens()->count());
    }

    // In a small office everyone handles events, so everyone can see and open
    // every event; "my events" is just the ones where you have a task.
    public function test_everyone_sees_every_event_and_my_events_are_the_ones_with_my_tasks(): void
    {
        $staff = User::factory()->staff()->create();
        $mine = Event::factory()->create();
        Task::factory()->create(['event_id' => $mine->id, 'assigned_to' => $staff->id]);
        $other = Event::factory()->create();

        Sanctum::actingAs($staff);

        $this->assertCount(2, $this->getJson('/api/events')->assertOk()->json('events'));
        $this->assertSame([$mine->id], collect($this->getJson('/api/events?mine=1')->json('events'))->pluck('id')->all());
        $this->getJson("/api/events/{$other->id}")->assertOk();
        $this->getJson("/api/events/{$other->id}/tasks")->assertOk();
        $this->getJson("/api/events/{$other->id}/documents")->assertOk();
        $this->get("/api/events/{$other->id}/report")->assertOk();

        $this->assertSame([$mine->id], collect($this->getJson('/api/dashboard/staff')->json('myEvents'))->pluck('id')->all());
    }

    public function test_people_on_an_event_are_the_ones_with_tasks(): void
    {
        $event = Event::factory()->create();
        [$a, $b] = User::factory()->staff()->count(2)->create();
        Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $a->id]);
        Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $a->id]);
        Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $b->id]);
        Task::factory()->create(['event_id' => $event->id, 'assigned_to' => null]);

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->assertSame(2, $this->getJson('/api/events')->json('events.0.task_summary.people'));
    }

    public function test_admins_and_officers_see_all_events(): void
    {
        Event::factory()->count(3)->create();

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->assertCount(3, $this->getJson('/api/events')->json('events'));
    }

    public function test_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}", ['is_active' => false])->assertStatus(422);
        $this->putJson("/api/users/{$admin->id}", ['role' => 'staff'])->assertStatus(422);
        $this->putJson("/api/users/{$admin->id}", ['name' => 'Renamed Admin'])->assertOk();

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertSame('admin', $admin->role);
    }

    public function test_any_active_member_can_be_assigned_whatever_their_role(): void
    {
        $event = Event::factory()->create();
        $inactive = User::factory()->staff()->create(['is_active' => false]);
        $officer = User::factory()->officer()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($officer);
        $task = ['name' => 'Book venue', 'due_date' => today()->addDay()->toDateString()];

        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $inactive->id])->assertStatus(422);
        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $officer->id])->assertCreated();
        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $admin->id])->assertCreated();

        // The officer can update the status of their own task, and sees it in My Tasks.
        $own = $event->tasks()->where('assigned_to', $officer->id)->first();
        $this->patchJson("/api/tasks/{$own->id}/status", ['status' => 'done'])->assertOk();
        $this->assertSame([$own->id], collect($this->getJson('/api/my-tasks')->json('tasks'))->pluck('id')->all());
    }

    public function test_deactivated_existing_assignees_do_not_block_edits(): void
    {
        $staff = User::factory()->staff()->create();
        $task = Task::factory()->create(['assigned_to' => $staff->id]);
        $staff->update(['is_active' => false]);

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->putJson("/api/tasks/{$task->id}", ['name' => 'Renamed', 'assigned_to' => $staff->id])->assertOk();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
    }
}
