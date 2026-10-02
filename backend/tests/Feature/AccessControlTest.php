<?php

namespace Tests\Feature;

use App\Models\Document;
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

    public function test_staff_only_see_events_they_are_assigned_to(): void
    {
        $staff = User::factory()->staff()->create();
        $viaEventStaff = Event::factory()->create();
        $viaEventStaff->staff()->attach($staff);
        $viaTask = Event::factory()->create();
        Task::factory()->create(['event_id' => $viaTask->id, 'assigned_to' => $staff->id]);
        $other = Event::factory()->create();

        Sanctum::actingAs($staff);

        $ids = collect($this->getJson('/api/events')->assertOk()->json('events'))->pluck('id')->sort()->values()->all();
        $this->assertSame([$viaEventStaff->id, $viaTask->id], $ids);

        $this->getJson("/api/events/{$viaTask->id}")->assertOk();
        $this->getJson("/api/events/{$other->id}")->assertForbidden();
        $this->getJson("/api/events/{$other->id}/tasks")->assertForbidden();
        $this->getJson("/api/events/{$other->id}/documents")->assertForbidden();
        $this->get("/api/events/{$other->id}/report")->assertForbidden();

        $dashboardIds = collect($this->getJson('/api/dashboard/staff')->json('myEvents'))->pluck('id')->sort()->values()->all();
        $this->assertSame([$viaEventStaff->id, $viaTask->id], $dashboardIds);
    }

    public function test_staff_cannot_download_documents_of_other_events(): void
    {
        $document = Document::create([
            'event_id' => Event::factory()->create()->id,
            'uploaded_by' => User::factory()->officer()->create()->id,
            'file_name' => 'budget.pdf',
            'file_path' => 'documents/budget.pdf',
            'file_size' => 10,
            'mime_type' => 'application/pdf',
        ]);

        Sanctum::actingAs(User::factory()->staff()->create());

        $this->get("/api/documents/{$document->id}/download")->assertForbidden();
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

    public function test_only_active_staff_can_be_newly_assigned(): void
    {
        $event = Event::factory()->create();
        $inactive = User::factory()->staff()->create(['is_active' => false]);
        $officer = User::factory()->officer()->create();
        $active = User::factory()->staff()->create();

        Sanctum::actingAs($officer);
        $task = ['name' => 'Book venue', 'due_date' => today()->addDay()->toDateString()];

        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $inactive->id])->assertStatus(422);
        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $officer->id])->assertStatus(422);
        $this->postJson("/api/events/{$event->id}/tasks", [...$task, 'assigned_to' => $active->id])->assertCreated();

        $this->putJson("/api/events/{$event->id}/staff", ['staff_ids' => [$inactive->id]])->assertStatus(422);
        $this->putJson("/api/events/{$event->id}/staff", ['staff_ids' => [$active->id]])->assertOk();
    }

    public function test_deactivated_existing_assignees_do_not_block_edits(): void
    {
        $staff = User::factory()->staff()->create();
        $event = Event::factory()->create();
        $event->staff()->attach($staff);
        $task = Task::factory()->create(['event_id' => $event->id, 'assigned_to' => $staff->id]);
        $staff->update(['is_active' => false]);

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->putJson("/api/tasks/{$task->id}", ['name' => 'Renamed', 'assigned_to' => $staff->id])->assertOk();
        $this->putJson("/api/events/{$event->id}/staff", ['staff_ids' => [$staff->id]])->assertOk();
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
