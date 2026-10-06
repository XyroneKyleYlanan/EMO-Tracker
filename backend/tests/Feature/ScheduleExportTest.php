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

    public function test_staff_cannot_export(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());

        $this->get('/api/schedule/export')->assertForbidden();
    }
}
