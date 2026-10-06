<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * One year of the Schedule as an Excel file laid out like the EMO's own
 * sheet: year title, the same seven columns, rows colored by building.
 * Generated locally (no external service), like the PDF reports.
 */
class ScheduleExport
{
    public const HEADERS = ['DATE', 'TIME', 'EVENT', 'TYPE', 'DEPARTMENT', 'VENUE', 'CONTROL #', 'REMARKS'];

    public static function build(int $year): string
    {
        $events = Event::with('venue.building')
            ->whereYear('event_date', $year)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle((string) $year);

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', (string) $year);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray(self::HEADERS, null, 'A2');
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $row = 3;
        foreach ($events as $event) {
            $cancelled = $event->status === 'cancelled';
            $sheet->fromArray([
                self::dateLabel($event->event_date, $event->end_date),
                self::timeLabel($event->event_time, $event->end_time),
                $cancelled ? "{$event->name} (Cancelled)" : $event->name,
                ucfirst($event->event_type),
                $event->department,
                $event->location,
                $event->control_number,
                implode(' · ', array_filter([self::rescheduledLabel($event), $event->remarks])),
            ], null, "A{$row}");

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
