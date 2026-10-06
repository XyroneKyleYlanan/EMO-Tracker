<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueClashes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VenueClashTest extends TestCase
{
    use RefreshDatabase;

    private int $hall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hall = Venue::create(['name' => 'University Hall'])->id;
    }

    private function booking(array $attributes = []): Event
    {
        return Event::factory()->scheduleOnly()->create([
            'venue_id' => $this->hall, 'venue_details' => null,
            'event_date' => today()->addDays(5)->toDateString(), 'event_time' => '08:00', 'end_time' => '12:00',
            ...$attributes,
        ]);
    }

    /**
     * The second booking, against one at University Hall 5 days from now, 8:00–12:00.
     * Dates are days from today.
     */
    public static function cases(): array
    {
        return [
            'same venue, overlapping times' => [['event_time' => '10:00', 'end_time' => '15:00'], true],
            'back to back is fine' => [['event_time' => '12:00', 'end_time' => '17:00'], false],
            'another day is fine' => [['event_date' => 6], false],
            'no time counts as all day' => [['event_time' => null, 'end_time' => null], true],
            'no end time runs to the end of the day' => [['event_time' => '11:00', 'end_time' => null], true],
            'cancelled bookings don\'t hold the venue' => [['event_time' => '10:00', 'status' => 'cancelled'], false],
            'a multi-day event covering that day' => [['event_date' => 3, 'end_date' => 6, 'event_time' => '09:00', 'end_time' => '10:00'], true],
            'free-text places aren\'t compared' => [['venue_id' => null, 'venue_details' => 'University Hall'], false],
        ];
    }

    #[DataProvider('cases')]
    public function test_clash_rules(array $other, bool $clashes): void
    {
        foreach (['event_date', 'end_date'] as $field) {
            if (isset($other[$field])) {
                $other[$field] = today()->addDays($other[$field])->toDateString();
            }
        }

        $a = $this->booking();
        $b = $this->booking($other);

        $this->assertSame($clashes, VenueClashes::between($a, $b));
        $this->assertSame($clashes, VenueClashes::between($b, $a), 'the rule works both ways');
    }

    public function test_a_room_and_the_whole_venue_clash(): void
    {
        $whole = $this->booking();
        $room = $this->booking(['venue_details' => 'Room 201', 'event_time' => '09:00']);

        $this->assertTrue(VenueClashes::between($whole, $room));
        $this->assertTrue(VenueClashes::between($room, $this->booking(['venue_details' => ' room  201 '])), 'same room, typed differently');
        $this->assertFalse(VenueClashes::between($room, $this->booking(['venue_details' => 'Room 202'])), 'a different room is fine');
    }

    public function test_the_form_check_warns_before_saving_and_leaves_out_the_event_being_edited(): void
    {
        $seminar = $this->booking(['name' => 'Lecture Seminar']);
        $date = today()->addDays(5)->toDateString();

        Sanctum::actingAs(User::factory()->admin()->create());
        $check = fn (array $query) => $this->getJson('/api/event-clashes?'.http_build_query([
            'venue_id' => $this->hall, 'event_date' => $date, ...$query,
        ]))->assertOk()->json('clashes');

        $this->assertSame(['Lecture Seminar'], collect($check(['event_time' => '09:00', 'end_time' => '10:00']))->pluck('name')->all());
        $this->assertSame([], $check(['event_time' => '13:00', 'end_time' => '15:00']));
        // Editing the seminar itself doesn't warn about itself.
        $this->assertSame([], $check(['event_time' => '09:00', 'ignore' => $seminar->id]));

        Sanctum::actingAs(User::factory()->staff()->create());
        $this->getJson("/api/event-clashes?venue_id={$this->hall}&event_date={$date}")->assertForbidden();
    }

    public function test_the_schedule_and_event_panel_point_out_upcoming_clashes(): void
    {
        $a = $this->booking(['name' => 'Lecture Seminar']);
        $this->booking(['name' => 'Untitled Booking', 'event_time' => '09:00', 'end_time' => '11:00']);
        $past = ['event_date' => today()->subDays(10)->toDateString(), 'status' => 'completed'];
        $this->booking(['name' => 'Old A', ...$past]);
        $this->booking(['name' => 'Old B', ...$past]);

        Sanctum::actingAs(User::factory()->staff()->create());
        $rows = collect($this->getJson('/api/schedule?year='.today()->addDays(5)->year)->json('events'))->keyBy('name');

        $this->assertSame(['Untitled Booking'], $rows['Lecture Seminar']['clashes']);
        $this->assertSame(['Lecture Seminar'], $rows['Untitled Booking']['clashes']);
        if (isset($rows['Old A'])) {
            $this->assertSame([], $rows['Old A']['clashes'], 'past clashes are not pointed out');
        }

        $this->getJson("/api/events/{$a->id}")->assertJsonPath('event.clashes.0.name', 'Untitled Booking');
    }
}
