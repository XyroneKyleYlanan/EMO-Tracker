<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * One year of the Schedule as a file: an Excel file laid out like the EMO's
 * own sheet (year title, the same columns, rows colored by building), or a
 * plain CSV file that opens in any spreadsheet or text app. Generated locally
 * (no external service), like the PDF reports.
 */
class ScheduleExport
{
    public const HEADERS = ['DATE', 'TIME', 'EVENT', 'TYPE', 'DEPARTMENT', 'VENUE', 'CONTROL #', 'REMARKS'];

    /**
     * The year's events in Schedule order, narrowed the way the Schedule page
     * narrows them, so the file has exactly the rows on screen: by type, and by
     * the search box (the same fields the page searches, "overlap" included).
     */
    public static function events(int $year, ?string $type = null, ?string $search = null): Collection
    {
        $events = Event::with('venue.building')
            ->whereYear('event_date', $year)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        // Overlaps are found among the whole year, as on the page, before any filter.
        $search = mb_strtolower(trim((string) $search));
        $clashes = $search === '' ? [] : VenueClashes::within($events);

        return $events
            ->filter(fn (Event $e) => ! $type || $e->event_type === $type)
            ->filter(fn (Event $e) => $search === '' || collect([
                $e->name, $e->event_type, $e->department, $e->location, $e->control_number, $e->remarks,
                $e->status === 'upcoming' && ! empty($clashes[$e->id]) ? 'overlap' : null,
            ])->contains(fn ($value) => $value !== null && str_contains(mb_strtolower($value), $search)))
            ->values();
    }

    /** @param  string|null  $note  what the rows were narrowed to, shown after the year */
    public static function xlsx(int $year, Collection $events, ?string $note = null): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle((string) $year);

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', $note ? "{$year} · {$note}" : (string) $year);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray(self::HEADERS, null, 'A2');
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $row = 3;
        foreach ($events as $event) {
            $cancelled = $event->status === 'cancelled';
            $sheet->fromArray(self::row($event), null, "A{$row}");

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            if ($color = $event->venue?->building?->color) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($color, '#'));
            }
            if ($cancelled) {
                $sheet->getStyle("C{$row}")->getFont()->setStrikethrough(true);
            }
            $row++;
        }

        $last = max(2, $row - 1);
        $sheet->getStyle("A1:H{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
        $sheet->getStyle("A3:H{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        foreach (['A' => 22, 'B' => 20, 'C' => 42, 'D' => 11, 'E' => 26, 'F' => 26, 'G' => 14, 'H' => 30] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A3');

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');

        return ob_get_clean();
    }

    /**
     * The same rows as plain text: no colors or title, and each date carries
     * its year, since there is no year title above it.
     */
    public static function csv(Collection $events): string
    {
        $file = fopen('php://temp', 'r+');
        // The byte-order mark makes Excel read the file as UTF-8, so "ñ", "–" and "·" show correctly.
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, self::HEADERS, ',', '"', '', "\r\n");
        foreach ($events as $event) {
            fputcsv($file, self::row($event, withYear: true), ',', '"', '', "\r\n");
        }
        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        return $csv;
    }

    // One event's cells, in the order of HEADERS.
    private static function row(Event $event, bool $withYear = false): array
    {
        $date = self::dateLabel($event->event_date, $event->end_date);

        return [
            $withYear ? $date.', '.($event->end_date ?? $event->event_date)->year : $date,
            self::timeLabel($event->event_time, $event->end_time),
            $event->status === 'cancelled' ? "{$event->name} (Cancelled)" : $event->name,
            ucfirst($event->event_type),
            $event->department,
            $event->location,
            $event->control_number,
            implode(' · ', array_filter([self::rescheduledLabel($event), $event->remarks])),
        ];
    }

    // "October 2", "October 11–13", or "October 30 – November 1", like the EMO's sheet.
    private static function dateLabel(Carbon $start, ?Carbon $end): string
    {
        if (! $end || $end->equalTo($start)) {
            return $start->format('F j');
        }

        return $start->month === $end->month
            ? $start->format('F j').'–'.$end->format('j')
            : $start->format('F j').' – '.$end->format('F j');
    }

    // "Rescheduled from May 15", or "Rescheduled from 9:00 AM" when only the time moved.
    private static function rescheduledLabel(Event $event): ?string
    {
        if (! $event->original_date) {
            return null;
        }

        return 'Rescheduled from '.($event->original_date->equalTo($event->event_date)
            ? self::timeLabel($event->original_time, null)
            : $event->original_date->format('F j'));
    }

    private static function timeLabel(?string $start, ?string $end): string
    {
        if (! $start) {
            return 'TBA';
        }

        $format = fn ($time) => Carbon::parse($time)->format('g:i A');

        return $end ? "{$format($start)} – {$format($end)}" : $format($start);
    }
}
