<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\ScheduleImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

// A made-up spreadsheet with the same hand-typed quirks as the EMO's real one.
class ScheduleImportTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->admin()->create();
        $this->file = $this->workbook();
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_a_dry_run_reports_but_saves_nothing(): void
    {
        $result = ScheduleImport::run($this->file, dryRun: true);

        $this->assertSame(9, $result['summary']['imported']);
        $this->assertSame(1, $result['summary']['skipped']);
        $this->assertSame(0, Event::count());
        $this->assertSame(0, Venue::count());
    }

    public function test_messy_rows_are_read_the_way_a_person_would(): void
    {
        $result = ScheduleImport::run($this->file);
        $event = fn (string $name) => Event::with('venue')->where('name', $name)->firstOrFail();

        $ojt = $event('CAS OJT Seminar');
        $this->assertSame(['2026-08-11', '08:00:00', '17:00:00', 'University Hall', '7/21/26', '260041'],
            [$ojt->event_date->toDateString(), $ojt->event_time, $ojt->end_time, $ojt->location, $ojt->control_number, $ojt->remarks]);

        // A blank date means the same day as the row above; "7:00-12:00pm" starts in the morning.
        $pinning = $event('Psychology Pinning');
        $this->assertSame(['2026-08-11', '07:00:00', '12:00:00'], [$pinning->event_date->toDateString(), $pinning->event_time, $pinning->end_time]);

        // Misspelled month, "10:am", and "PSB-MPH".
        $board = $event('Board Meeting');
        $this->assertSame(['2026-09-19', '10:00:00', '14:00:00', 'PSB MPH'], [$board->event_date->toDateString(), $board->event_time, $board->end_time, $board->location]);

        // A date range, a room inside a building, and "Cancelled" in the remarks.
        $nurses = $event('Nurses Week');
        $this->assertSame(['2026-10-26', '2026-10-28', '08:00:00', '17:00:00', 'SOM Building 505 - 507', 'cancelled'],
            [$nurses->event_date->toDateString(), $nurses->end_date->toDateString(), $nurses->event_time, $nurses->end_time, $nurses->location, $nurses->status]);

        // The tab's year wins over a mistyped year, an Excel time, and a place that isn't on the list yet.
        $xmas = $event('Christmas Program');
        $this->assertSame(['2026-12-15', '09:00:00', 'Philippine Arena'], [$xmas->event_date->toDateString(), $xmas->event_time, $xmas->location]);
        $this->assertSame(['Philippine Arena'], $result['summary']['new_venues']);

        // A name typed into the Time column moves to the remarks; ".." is not a place.
        $flag = $event('Flag Ceremony');
        $this->assertSame([null, 'ate beth', null], [$flag->event_time, $flag->remarks, $flag->location]);

        $this->assertSame('2027-03-09', $event('Guidance Seminar')->event_date->toDateString());
        $this->assertSame('16:00:00', $event('(Untitled booking)')->end_time);
        $this->assertTrue(Event::all()->every(fn ($e) => ! $e->needs_preparation));

        $issues = collect($result['review'])->pluck('issue')->implode(' | ');
        foreach (['Notice row', 'Year was 2025', 'Unreadable time "ate beth"', "doesn't look like a place", 'Starts before 6 AM', 'read as NEU Covered Court', 'No event name'] as $expected) {
            $this->assertStringContainsString($expected, $issues);
        }
    }

    public function test_running_it_twice_never_doubles_anything(): void
    {
        ScheduleImport::run($this->file);
        $second = ScheduleImport::run($this->file);

        $this->assertSame(0, $second['summary']['imported']);
        $this->assertSame(9, $second['summary']['duplicates']);
        $this->assertSame(9, Event::count());
    }

    public function test_the_command_writes_a_review_list(): void
    {
        Storage::fake('local');

        $this->artisan('schedule:import', ['file' => $this->file, '--dry-run' => true])
            ->expectsOutputToContain('Would be imported:    9')
            ->expectsOutputToContain('rows need a quick check')
            ->expectsOutputToContain('Nothing was saved')
            ->assertSuccessful();

        $this->assertCount(1, Storage::disk('local')->files('imports'));
    }

    private function workbook(): string
    {
        $book = new Spreadsheet;
        $date = fn (string $d) => ExcelDate::PHPToExcel(new \DateTime($d));

        $tab = $book->getActiveSheet()->setTitle('Sheet1');
        $tab->setCellValue('A1', 2026);
        $tab->fromArray(['DATE', 'TIME', 'EVENT', 'DEPARTMENT', 'VENUE', 'CONTROLL #', 'REMARKS'], null, 'A2');
        $tab->fromArray([
            [$date('2026-08-11'), '8:00am-5:00pm', 'CAS OJT Seminar', 'CAS', 'UHALL', $date('2026-07-21'), 260041.0],
            [null, '7:00-12:00pm', 'Psychology Pinning', 'CAS', 'University Hall', null, null],
            ['Septmber 19', '10:am-2:00pm', 'Board Meeting', 'Admin', 'PSB-MPH', null, null],
            ['October 26-28', '8:00-5:00', 'Nurses Week', 'CON', 'SOM 505 - 507', null, 'Cancelled'],
            ['SEPT. 4-13 BAR EXAM - NO CLASSES', null, null, null, null, null, null],
            [$date('2025-12-15'), 0.375, 'Christmas Program', 'Admin', 'Philippine Arena', null, null],
            [$date('2026-11-02'), 'ate beth', 'Flag Ceremony', 'IS', '..', null, null],
            [$date('2026-11-03'), '3:00am-5:00pm', 'Research Seminar', 'CBA', 'Covered Court', null, null],
            [$date('2026-11-04'), '8:00am-4:00', '', 'CAS', '1:00-4:00pm', null, null],
        ], null, 'A3', true);
        foreach (['A3', 'A8', 'A9', 'A10', 'A11', 'F3'] as $cell) {
            $tab->getStyle($cell)->getNumberFormat()->setFormatCode('mmmm d');
        }
        $tab->getStyle('B8')->getNumberFormat()->setFormatCode('h:mm');

        $next = $book->createSheet()->setTitle('Copy of Sheet1');
        $next->setCellValue('A1', 2027);
        $next->fromArray(['DATE', 'TIME', 'EVENT', 'DEPARTMENT', 'VENUE', 'CONTROLL #', 'REMARKS'], null, 'A2');
        $next->fromArray([$date('2026-03-09'), '8:00am-5:00pm', 'Guidance Seminar', 'Guidance', 'UHALL'], null, 'A3');
        $next->getStyle('A3')->getNumberFormat()->setFormatCode('mmmm d');

        $letters = $book->createSheet()->setTitle('Sheet2');
        $letters->setCellValue('A1', 2026);
        $letters->fromArray(['Date', 'Time', 'Control Number', 'Department', 'Venue', 'Content of Letter', 'Remarks'], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'schedule').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }
}
