<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Venue;
use App\Services\ScheduleTidy;
use App\Services\TextTidy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduleTidyTest extends TestCase
{
    use RefreshDatabase;

    public function test_titles_keep_acronyms_brands_and_filipino_particles(): void
    {
        $this->assertSame('Annual Fun Run', TextTidy::title('Annual Fun run'));
        $this->assertSame('Initial Assessment ng mga Anak', TextTidy::title('Initial Assestment ng mga anak'));
        $this->assertSame('Nurses Week', TextTidy::title('NURSES WEEK'));
        $this->assertSame('IS Graduation and Moving Up', TextTidy::title('IS GRADUATION AND MOVING UP'));
        $this->assertSame('COE Meet and Greet (Dry Run)', TextTidy::title('COE Meet And Greet (dryrun)'));
        $this->assertSame('Guidance: A Seminar on Values', TextTidy::title('Guidance : a seminar on values'));
        $this->assertSame('Product InNEUvation at NEU-ACES', TextTidy::title('Product InNEUvation at NEU-ACES'));
        $this->assertSame('Pagdiriwang ng Buwan ng Wika', TextTidy::title('Pagdiriwang ng Buwanang Wika'));
        $this->assertSame('Buwanang Pulong', TextTidy::title('Buwanang Pulong'));
        $this->assertSame('Battalion Formation (Training Day)', TextTidy::title('Battalion Formation (training day'));
        $this->assertSame('c/o Ka Jeff', TextTidy::title('c/o Ka Jeff'));
        $this->assertNull(TextTidy::title('.'));
        $this->assertSame('A Vision We Share, A System of Care', TextTidy::title('A Vision We Share, A System of Care'));
        $this->assertSame('NEUIS Bldg A Room 111', TextTidy::title('NEUIS BLldg A room 111'));
        $this->assertSame('CAS-GE', TextTidy::title('CAS -GE'));
        $this->assertSame('Seminar KRA4 - Community Engagement', TextTidy::title('Seminar KRA4 -Community Enggagement'));
    }

    public function test_rooms_and_remarks(): void
    {
        $this->assertSame('Room 201', TextTidy::room('rm201'));
        $this->assertSame('Rooms 505, 506, 507', TextTidy::room('505,506,507'));
        $this->assertSame('Rooms 505-507', TextTidy::room('505 - 507'));
        $this->assertSame('Resched to August 17', TextTidy::remark('resched to august 17'));
        $this->assertSame('c/o Ate Cha', TextTidy::remark('c/o ate cha'));
    }

    public function test_tidy_updates_events_and_venues_and_is_safe_to_rerun(): void
    {
        Storage::fake('local');
        $venue = Venue::create(['name' => 'Multimedia room C']);
        Venue::create(['name' => 'School Corridors']);
        $duplicate = Venue::create(['name' => 'School corridors']);
        $event = Event::factory()->scheduleOnly()->create([
            'name' => 'Family Fun Day - HINDI NA PO TULOY ITO', 'department' => 'College of law',
            'venue_id' => $venue->id, 'venue_details' => 'rm 201', 'remarks' => 'resched oct 22',
        ]);

        $this->artisan('schedule:tidy', ['--dry-run' => true])->expectsOutputToContain('Nothing was saved')->assertSuccessful();
        $this->assertSame('College of law', $event->fresh()->department);

        $this->artisan('schedule:tidy')->assertSuccessful();
        $event->refresh();
        $this->assertSame(['Family Fun Day', 'College of Law', 'Room 201', 'Resched Oct 22 · Hindi na po tuloy ito', 'cancelled'],
            [$event->name, $event->department, $event->venue_details, $event->remarks, $event->status]);
        $this->assertSame('Multimedia Room C', $venue->fresh()->name);
        $this->assertSame('School corridors', $duplicate->fresh()->name, 'not renamed onto an existing venue');

        $this->artisan('schedule:tidy')->expectsOutputToContain('(not renamed')->assertSuccessful();
        $this->assertSame(1, count(ScheduleTidy::run(true)), 'only the duplicate venue is left');
    }
}
