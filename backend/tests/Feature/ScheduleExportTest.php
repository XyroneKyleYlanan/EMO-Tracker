<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ScheduleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_looks_like_the_emo_sheet(): void
    {
        $building = Building::create(['name' => 'UHALL', 'color' => '#A4C2F4']);
        $venue = Venue::create(['name' => 'University Hall', 'building_id' => $building->id]);
        Event::factory()->scheduleOnly()->create([
            'name' => 'CAS OJT Seminar', 'department' => 'CAS', 'venue_id' => $venue->id, 'venue_details' => null,
            'event_date' => '2026-08-11', 'event_time' => '08:00', 'end_time' => '17:00', 'control_number' => '260041', 'remarks' => 'Pencil',
        ]);
        Event::factory()->scheduleOnly()->create([
            'name' => 'Nurses Week', 'event_date' => '2026-10-26', 'end_date' => '2026-10-28', 'event_time' => null, 'status' => 'cancelled',
        ]);
        Event::factory()->scheduleOnly()->external()->create([
            'name' => 'Science Fair', 'event_date' => '2026-11-09', 'original_date' => '2026-11-02', 'remarks' => 'Sponsor booth',
        ]);
        Event::factory()->scheduleOnly()->create(['name' => 'Next Year', 'event_date' => '2027-01-29']);

        Sanctum::actingAs(User::factory()->officer()->create());

        $response = $this->get('/api/schedule/export?year=2026')->assertOk()->assertDownload('emo-schedule-2026.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->getContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        $this->assertSame('2026', $sheet->getTitle());
        $this->assertSame('2026', (string) $sheet->getCell('A1')->getValue());
        $this->assertSame(['DATE', 'TIME', 'EVENT', 'TYPE', 'DEPARTMENT', 'VENUE', 'CONTROL #', 'REMARKS'], $sheet->rangeToArray('A2:H2')[0]);
        $this->assertSame(
            ['August 11', '8:00 AM – 5:00 PM', 'CAS OJT Seminar', 'Internal', 'CAS', 'University Hall', '260041', 'Pencil'],
            $sheet->rangeToArray('A3:H3')[0],
        );
        $this->assertSame('A4C2F4', $sheet->getStyle('C3')->getFill()->getStartColor()->getRGB());

        $this->assertSame('October 26–28', $sheet->getCell('A4')->getValue());
        $this->assertSame('TBA', $sheet->getCell('B4')->getValue());
        $this->assertSame('Nurses Week (Cancelled)', $sheet->getCell('C4')->getValue());
        $this->assertTrue($sheet->getStyle('C4')->getFont()->getStrikethrough());

        // The type, and a reschedule noted in the remarks the way the EMO writes it.
        $this->assertSame(['Science Fair', 'External'], [$sheet->getCell('C5')->getValue(), $sheet->getCell('D5')->getValue()]);
        $this->assertSame('Rescheduled from November 2 · Sponsor booth', $sheet->getCell('H5')->getValue());

        $this->assertNull($sheet->getCell('C6')->getValue(), 'other years are not included');
    }

    public function test_csv_export_has_the_same_columns_as_plain_text(): void
    {
        $building = Building::create(['name' => 'UHALL', 'color' => '#A4C2F4']);
        $venue = Venue::create(['name' => 'University Hall', 'building_id' => $building->id]);
        Event::factory()->scheduleOnly()->create([
            'name' => 'Niño Awards, Night 1', 'department' => 'CAS', 'venue_id' => $venue->id, 'venue_details' => null,
            'event_date' => '2026-08-11', 'event_time' => '08:00', 'end_time' => '17:00', 'control_number' => '260041', 'remarks' => 'Pencil',
        ]);
        Event::factory()->scheduleOnly()->external()->create([
            'name' => 'Science Fair', 'event_date' => '2026-12-30', 'end_date' => '2027-01-02', 'event_time' => null,
            'venue_details' => 'Open field', 'status' => 'cancelled', 'department' => null,
        ]);

        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->get('/api/schedule/export?year=2026&format=csv')->assertOk()->assertDownload('emo-schedule-2026.csv');
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'UTF-8 mark, so Excel shows ñ correctly');
        $rows = array_map('str_getcsv', explode("\r\n", trim(substr($csv, 3))));
        $this->assertSame(
            [
                ['DATE', 'TIME', 'EVENT', 'TYPE', 'DEPARTMENT', 'VENUE', 'CONTROL #', 'REMARKS'],
                ['August 11, 2026', '8:00 AM – 5:00 PM', 'Niño Awards, Night 1', 'Internal', 'CAS', 'University Hall', '260041', 'Pencil'],
                ['December 30 – January 2, 2027', 'TBA', 'Science Fair (Cancelled)', 'External', '', 'Open field', '', ''],
            ],
            $rows,
        );
    }

    public function test_export_has_only_the_rows_the_page_shows(): void
    {
        $hall = Venue::create(['name' => 'University Hall']);
        Event::factory()->scheduleOnly()->create(['name' => 'CAS OJT Seminar', 'event_date' => '2026-12-01', 'venue_id' => $hall->id, 'venue_details' => null]);
        Event::factory()->scheduleOnly()->create(['name' => 'Choir Rehearsal', 'event_date' => '2026-12-01', 'venue_id' => $hall->id, 'venue_details' => null]);
        Event::factory()->scheduleOnly()->external()->create(['name' => 'Science Fair', 'event_date' => '2026-12-05', 'remarks' => 'Sponsor booth']);
        $this->travelTo('2026-11-01');

        Sanctum::actingAs(User::factory()->officer()->create());

        $names = function (string $query, string $file): array {
            $response = $this->get("/api/schedule/export?year=2026&format=csv&{$query}")->assertOk()->assertDownload($file);
            $rows = array_map('str_getcsv', explode("\r\n", trim(substr($response->getContent(), 3))));

            return array_column(array_slice($rows, 1), 2);
        };

        $this->assertSame(['Science Fair'], $names('type=external', 'emo-schedule-2026-external.csv'));
        $this->assertSame(['CAS OJT Seminar', 'Choir Rehearsal'], $names('type=internal', 'emo-schedule-2026-internal.csv'));
        $this->assertSame(['Science Fair'], $names('search=SPONSOR', 'emo-schedule-2026-filtered.csv'), 'remarks are searched, any case');
        $this->assertSame(['CAS OJT Seminar', 'Choir Rehearsal'], $names('search=overlap', 'emo-schedule-2026-filtered.csv'));
        $this->assertSame([], $names('type=external&search=seminar', 'emo-schedule-2026-external-filtered.csv'));

        // The Excel title says what the rows were narrowed to.
        $response = $this->get('/api/schedule/export?year=2026&type=external')->assertOk()->assertDownload('emo-schedule-2026-external.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->getContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);
        $this->assertSame('2026 · External events', $sheet->getCell('A1')->getValue());
        $this->assertSame('Science Fair', $sheet->getCell('C3')->getValue());
        $this->assertNull($sheet->getCell('C4')->getValue());

        $this->getJson('/api/schedule/export?format=pdf')->assertStatus(422)->assertJsonValidationErrors('format');
        $this->getJson('/api/schedule/export?type=other')->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_staff_cannot_export(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->get('/api/schedule/export')->assertForbidden();
    }
}
