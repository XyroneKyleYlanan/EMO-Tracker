<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One-time import of the EMO's schedule spreadsheet (one tab per year, columns
 * DATE, TIME, EVENT, DEPARTMENT, VENUE, CONTROL #, REMARKS). The sheet was typed
 * by hand, so anything that can't be read with confidence is imported as far as
 * possible and listed for a person to review, rather than guessed silently.
 */
class ScheduleImport
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    // [normalized spelling prefix, venue name]. The rest of the text becomes the room/details.
    private const VENUE_ALIASES = [
        ['university hall', 'University Hall'], ['uhall', 'University Hall'],
        ['building b is mph', 'IS MPH'], ['is b mph', 'IS MPH'], ['is mph', 'IS MPH'], ['neu is mph', 'IS MPH'],
        ['psb mph', 'PSB MPH'], ['psb', 'PSB Building'],
        ['som mph', 'SOM MPH'], ['som', 'SOM Building'],
        ['is covered court', 'IS Covered Court'], ['neu is covered court', 'IS Covered Court'], ['is court', 'IS Covered Court'],
        ['hs covered court', 'IS Covered Court', 'check'],
        ['neu covered court', 'NEU Covered Court'], ['covered court', 'NEU Covered Court', 'check'],
        ['neu open field', 'NEU Open Field'], ['openfield', 'NEU Open Field'], ['open field', 'NEU Open Field'],
        ['neu main library', 'Main Library'], ['neu library', 'Main Library'], ['main library', 'Main Library'],
        ['is library', 'IS Library'], ['primary library', 'IS Library', 'check'],
        ['2nd floor lobby', '2nd Floor Lobby'], ['2nd floor main bldg', 'Main Building'], ['main bldg', 'Main Building'],
        ['neu is bldg', 'IS Building'], ['is bldg', 'IS Building'], ['multimedia learning center is', 'IS Building'],
    ];

    private array $report = ['tabs' => [], 'read' => 0, 'imported' => 0, 'duplicates' => 0, 'skipped' => 0, 'new_venues' => []];

    private array $review = [];

    private int $adminId;

    public static function run(string $file, bool $dryRun = false): array
    {
        return (new self)->import($file, $dryRun);
    }

    private function import(string $file, bool $dryRun): array
    {
        $this->adminId = User::where('role', 'admin')->orderBy('id')->value('id')
            ?? throw new \RuntimeException('Create an administrator account before importing.');

        $book = IOFactory::load($file);

        DB::beginTransaction();
        try {
            foreach ($book->getWorksheetIterator() as $sheet) {
                $this->importSheet($sheet);
            }
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['summary' => $this->report, 'review' => $this->review];
    }

    private function importSheet(Worksheet $sheet): void
    {
        $title = $sheet->getCell('A1')->getValue();
        $headers = array_map(fn ($h) => strtoupper(trim((string) $h)), $sheet->rangeToArray('A2:G2', null, false, false)[0]);

        // Only year tabs with the schedule's columns (e.g. not the request-letter log).
        if (! is_numeric($title) || $headers[0] !== 'DATE' || $headers[2] !== 'EVENT') {
            $this->report['tabs'][] = "\"{$sheet->getTitle()}\": skipped (not a schedule tab)";

            return;
        }

        $year = (int) $title;
        $lastDate = null;
        $count = 0;

        foreach ($sheet->getRowIterator(3) as $row) {
            $n = $row->getRowIndex();
            [$date, $time, $name, $department, $venue, $control, $remarks] = array_map(
                fn ($col) => $sheet->getCell("{$col}{$n}")->getValue(),
                ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
            );
            $isTimeCell = ExcelDate::isDateTime($sheet->getCell("B{$n}"));
            $isDateCell = ExcelDate::isDateTime($sheet->getCell("A{$n}"));
            if (ExcelDate::isDateTime($sheet->getCell("F{$n}")) && is_numeric($control)) {
                $control = ExcelDate::excelToDateTimeObject($control);
            }
            if (ExcelDate::isDateTime($sheet->getCell("G{$n}")) && is_numeric($remarks)) {
                $remarks = ExcelDate::excelToDateTimeObject($remarks);
            }

            if ($this->blank($time) && $this->blank($name) && $this->blank($department) && $this->blank($venue)) {
                if (! $this->blank($date)) {
                    $label = $isDateCell && is_numeric($date) ? ExcelDate::excelToDateTimeObject($date)->format('M j') : (string) $date;
                    $this->flag($sheet, $n, $label, '', 'Notice row (e.g. "no classes"), not imported');
                    $this->report['skipped']++;
                }

                continue;
            }

            $this->report['read']++;
            $count++;
            $issues = [];

            // DATE: a blank date means the same day as the row above.
            [$start, $end, $dateIssue] = $this->parseDate($date, $isDateCell, $year, $lastDate);
            if ($dateIssue) {
                $issues[] = $dateIssue;
            }
            if (! $start) {
                $this->flag($sheet, $n, (string) $date, (string) $name, implode('; ', $issues ?: ['No readable date']).', not imported');
                $this->report['skipped']++;

                continue;
            }
            $lastDate = $start;

            // TIME
            [$from, $to, $timeIssue, $timeText] = $this->parseTime($time, $isTimeCell);
            if ($timeIssue) {
                $issues[] = $timeIssue;
            }

            // VENUE
            [$venueId, $details, $venueIssue] = $this->matchVenue($venue);
            if ($venueIssue) {
                $issues[] = $venueIssue;
            }

            // Consistent capitalization and spelling (see TextTidy), and a
            // "won't push through" note in the name means the event is cancelled.
            [$name, $cancelNote] = TextTidy::cancellation(trim((string) $name));
            $name = TextTidy::title($name);
            if ($name === null) {
                $name = '(Untitled Booking)';
                $issues[] = 'No event name';
            }
            $remarks = $this->text($remarks);
            foreach (array_filter([$timeText, $cancelNote]) as $note) {
                $remarks = trim(($remarks ? "{$remarks} · " : '').$note);
            }
            $remarks = TextTidy::remark($remarks);

            $attributes = [
                'name' => mb_substr($name, 0, 255),
                'event_date' => $start->toDateString(),
                'event_time' => $from,
                'venue_id' => $venueId,
                'venue_details' => $details,
            ];
            $exists = Event::whereDate('event_date', $attributes['event_date'])
                ->where(collect($attributes)->except('event_date')->all())
                ->exists();
            if ($exists) {
                $this->report['duplicates']++;

                continue;
            }

            Event::create([
                ...$attributes,
                'department' => TextTidy::title($this->text($department)),
                'end_date' => $end?->toDateString(),
                'end_time' => $to,
                'control_number' => $this->text($control),
                'remarks' => $remarks,
                'needs_preparation' => false,
                'status' => $cancelNote || ($remarks && str_contains(strtolower($remarks), 'cancel'))
                    ? 'cancelled'
                    : Event::statusForDate($start, $end),
                'created_by' => $this->adminId,
            ]);
            $this->report['imported']++;

            if ($issues) {
                $this->flag($sheet, $n, $start->format('M j'), $name, implode('; ', $issues));
            }
        }

        $this->report['tabs'][] = "\"{$sheet->getTitle()}\" ({$year}): {$count} rows";
    }

    /** @return array{0: ?Carbon, 1: ?Carbon, 2: ?string} */
    private function parseDate(mixed $value, bool $isDateCell, int $year, ?Carbon $previous): array
    {
        if ($this->blank($value)) {
            return [$previous, null, $previous ? null : 'No date'];
        }

        // Real dates keep their month and day, but the tab's year wins: typing
        // "March 9" in the 2027 tab stores the current year by mistake.
        if ($isDateCell && is_numeric($value)) {
            $date = Carbon::instance(ExcelDate::excelToDateTimeObject($value));
            $fixed = $date->copy()->setYear($year);

            return [$fixed, null, $date->year !== $year ? "Year was {$date->year}, used {$year}" : null];
        }

        // Text like "October 26-28", "Septmber 19" or "SEPT. 4-13".
        $text = strtolower(trim((string) $value));
        if (preg_match('/^([a-z]{3,9})\.?\s*(\d{1,2})(?:\s*-\s*(\d{1,2}))?\s*(.*)$/', $text, $m) && isset(self::MONTHS[substr($m[1], 0, 3)])) {
            if (trim($m[4]) !== '') {
                return [null, null, 'Notice row (e.g. "no classes")'];
            }
            $month = self::MONTHS[substr($m[1], 0, 3)];
            $start = Carbon::create($year, $month, (int) $m[2]);
            $end = isset($m[3]) && $m[3] !== '' ? Carbon::create($year, $month, (int) $m[3]) : null;

            return [$start, $end, null];
        }

        return [null, null, 'Unreadable date "'.trim((string) $value).'"'];
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string} [start, end, issue, text moved to remarks] */
    private function parseTime(mixed $value, bool $isTimeCell): array
    {
        if ($this->blank($value)) {
            return [null, null, null, null];
        }
        if ($isTimeCell && is_numeric($value)) {
            return [ExcelDate::excelToDateTimeObject($value)->format('H:i:s'), null, null, null];
        }

        $text = str_replace([';', ' '], [':', ''], strtolower(trim((string) $value)));
        $text = str_replace('nn', 'pm', $text);
        $parts = explode('-', $text);
        $start = $this->clock($parts[0] ?? '');
        $end = isset($parts[1]) ? $this->clock($parts[1]) : null;

        if (! $start || (isset($parts[1]) && ! $end) || count($parts) > 2) {
            return [null, null, 'Unreadable time "'.trim((string) $value).'" (moved to remarks)', trim((string) $value)];
        }

        // Fill in a missing am/pm the way a person would read it.
        [$sh, $sm, $smer] = $start;
        [$eh, $em, $emer] = $end ?? [null, null, null];
        if (! $smer && $emer) {
            $smer = ($sh === 12 || ($sh % 12) > ($eh % 12)) ? 'am' : $emer;
        } elseif ($smer && $end && ! $emer) {
            $emer = $smer;
        } elseif (! $smer) {
            $smer = $sh >= 7 && $sh <= 11 ? 'am' : 'pm';
            $emer = $smer;
        }

        $from = $this->to24($sh, $sm, $smer);
        $to = $end ? $this->to24($eh, $em, $emer) : null;
        if ($to && $to <= $from && $emer === 'am') {
            $to = $this->to24($eh, $em, 'pm');
        }

        $issue = null;
        if ((int) substr($from, 0, 2) < 6) {
            $issue = 'Starts before 6 AM ('.trim((string) $value).'), check the time';
        }
        if ($to && $to <= $from) {
            $issue = 'End time before start ('.trim((string) $value).'), end time left out';
            $to = null;
        }

        return [$from, $to, $issue, null];
    }

    /** @return ?array{0: int, 1: int, 2: ?string} hour, minute, am/pm */
    private function clock(string $text): ?array
    {
        if (! preg_match('/^(\d{1,2})(?::(\d{0,2}))?(?::\d{2})?(am|pm)?$/', $text, $m)) {
            return null;
        }
        $hour = (int) $m[1];
        $minute = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
        if ($hour > 23 || $minute > 59) {
            return null;
        }
        $meridiem = $m[3] ?? null;
        if ($hour > 12) {
            [$hour, $meridiem] = [$hour - 12, 'pm'];
        }

        return [$hour, $minute, $meridiem ?: null];
    }

    private function to24(int $hour, int $minute, string $meridiem): string
    {
        $hour = $hour % 12 + ($meridiem === 'pm' ? 12 : 0);

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    /** @return array{0: ?int, 1: ?string, 2: ?string} [venue id, room/details, issue] */
    private function matchVenue(mixed $value): array
    {
        $original = trim(preg_replace('/\s+/', ' ', (string) $value));
        if ($original === '') {
            return [null, null, 'No venue'];
        }
        if (! preg_match('/[a-z]{2}/i', $original) || preg_match('/^\d{1,2}:\d{2}/', $original)) {
            return [null, null, "Venue \"{$original}\" doesn't look like a place, left empty"];
        }

        $normalized = $this->normalizeVenue($original);
        foreach (self::VENUE_ALIASES as $entry) {
            [$alias, $venueName] = $entry;
            if ($normalized === $alias || str_starts_with($normalized, $alias.' ')) {
                $venue = Venue::firstOrCreate(['name' => $venueName], ['building_id' => $this->guessBuilding($venueName)]);
                $rest = trim(substr($normalized, strlen($alias)));
                $issue = isset($entry[2]) ? "Venue \"{$original}\" read as {$venueName}, please confirm" : null;

                return [$venue->id, $rest !== '' ? TextTidy::room($this->restOf($original, $rest)) : null, $issue];
            }
        }

        // A real place that isn't on the list yet: add it, so the admin can give it
        // a building or merge it into an existing venue on the Venues page.
        $name = mb_substr(TextTidy::title($original), 0, 255);
        $venue = Venue::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if (! $venue) {
            $venue = Venue::create(['name' => $name]);
            $this->report['new_venues'][] = $name;
        }

        return [$venue->id, null, null];
    }

    private function normalizeVenue(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/\bneuis\b/', 'neu is', $text);
        $text = preg_replace('/\b(is|psb|som)mph\b/', '$1 mph', $text);
        $text = str_replace(['.', '-', '/', ','], ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    // Keep the person's own spelling for the room part, e.g. "505 - 507".
    private function restOf(string $original, string $normalizedRest): string
    {
        $firstWord = strtok($normalizedRest, ' ');
        $pos = stripos($original, $firstWord);

        return trim($pos !== false ? substr($original, $pos) : $normalizedRest, ' -,/');
    }

    private function guessBuilding(string $venueName): ?int
    {
        $code = match (true) {
            str_starts_with($venueName, 'University Hall') => 'UHALL',
            str_starts_with($venueName, 'PSB') => 'PSB',
            str_starts_with($venueName, 'SOM') => 'SOM',
            str_starts_with($venueName, 'IS ') => 'IS BLDG',
            $venueName === 'Main Library' => 'NEU Library',
            str_starts_with($venueName, 'NEU ') => 'Outdoor areas',
            default => 'Main',
        };

        return Building::where('name', $code)->value('id');
    }

    // Cells are typed by hand: whole numbers come back as floats and some notes are dates.
    private function text(mixed $value): ?string
    {
        if ($this->blank($value)) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('n/j/y');
        }
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        return mb_substr(trim((string) $value), 0, 2000);
    }

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function flag(Worksheet $sheet, int $row, string $date, string $event, string $issue): void
    {
        $this->review[] = ['tab' => $sheet->getTitle(), 'row' => $row, 'date' => trim($date), 'event' => trim($event), 'issue' => $issue];
    }
}
