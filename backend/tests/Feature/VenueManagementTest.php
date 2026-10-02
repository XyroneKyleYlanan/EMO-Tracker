<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VenueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_manage_buildings_and_venues(): void
    {
        $building = Building::create(['name' => 'SOM', 'color' => '#B6D7A8']);
        $venue = Venue::create(['name' => 'SOM MPH', 'building_id' => $building->id]);

        Sanctum::actingAs(User::factory()->officer()->create());

        $this->postJson('/api/buildings', ['name' => 'CEA', 'color' => '#9FC5E8'])->assertForbidden();
        $this->putJson("/api/buildings/{$building->id}", ['name' => 'X', 'color' => '#9FC5E8'])->assertForbidden();
        $this->putJson("/api/venues/{$venue->id}", ['name' => 'X'])->assertForbidden();
        $this->deleteJson("/api/venues/{$venue->id}")->assertForbidden();
    }

    public function test_building_colors_must_come_from_the_palette(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/buildings', ['name' => 'CEA', 'color' => '#000080'])
            ->assertStatus(422)->assertJsonValidationErrors('color');
        $this->postJson('/api/buildings', ['name' => 'CEA', 'color' => Building::PALETTE[0]])->assertCreated();
        $this->postJson('/api/buildings', ['name' => 'CEA', 'color' => Building::PALETTE[1]])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_recoloring_a_building_recolors_its_schedule_rows(): void
    {
        $building = Building::create(['name' => 'SOM', 'color' => '#B6D7A8']);
        $venue = Venue::create(['name' => 'SOM MPH', 'building_id' => $building->id]);
        Event::factory()->scheduleOnly()->create(['venue_id' => $venue->id]);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->putJson("/api/buildings/{$building->id}", ['name' => 'SOM Building', 'color' => '#A2C4C9'])->assertOk();

        $row = $this->getJson('/api/schedule')->json('events.0');
        $this->assertSame('#A2C4C9', $row['building']['color']);
        $this->assertSame('SOM Building', $row['building']['name']);
    }

    public function test_renaming_a_venue_updates_every_event_using_it(): void
    {
        $venue = Venue::create(['name' => 'UHALL']);
        $event = Event::factory()->scheduleOnly()->create(['venue_id' => $venue->id, 'venue_details' => 'Lobby']);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->putJson("/api/venues/{$venue->id}", ['name' => 'University Hall', 'building_id' => null])->assertOk();

        $this->assertSame('University Hall Lobby', $event->fresh()->location);
    }

    public function test_used_venues_cannot_be_deleted_but_unused_ones_can(): void
    {
        $used = Venue::create(['name' => 'PSB MPH']);
        Event::factory()->scheduleOnly()->create(['venue_id' => $used->id]);
        $unused = Venue::create(['name' => 'Old Gym']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson("/api/venues/{$used->id}")->assertStatus(422);
        $this->assertNotNull($used->fresh());
        $this->deleteJson("/api/venues/{$unused->id}")->assertOk();
        $this->assertNull($unused->fresh());
    }

    public function test_merging_moves_events_and_removes_the_duplicate(): void
    {
        $keep = Venue::create(['name' => 'University Hall']);
        $duplicate = Venue::create(['name' => 'UHALL']);
        $events = Event::factory()->scheduleOnly()->count(3)->create(['venue_id' => $duplicate->id]);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/venues/{$duplicate->id}/merge", ['into_id' => $duplicate->id])->assertStatus(422);
        $this->postJson("/api/venues/{$duplicate->id}/merge", ['into_id' => $keep->id])
            ->assertOk()->assertJsonPath('moved', 3);

        $this->assertNull($duplicate->fresh());
        $this->assertTrue($events->every(fn ($e) => $e->fresh()->venue_id === $keep->id));
    }

    public function test_deleting_a_building_keeps_its_venues_without_a_building(): void
    {
        $building = Building::create(['name' => 'PSB', 'color' => '#D5A6BD']);
        $venue = Venue::create(['name' => 'PSB MPH', 'building_id' => $building->id]);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->deleteJson("/api/buildings/{$building->id}")->assertOk();

        $this->assertNotNull($venue->fresh());
        $this->assertNull($venue->fresh()->building_id);
    }
}
