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
    public const HEADERS = ['DATE', 'TIME', 'EVENT', 'DEPARTMENT', 'VENUE', 'CONTROL #', 'REMARKS'];

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

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', (string) $year);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray(self::HEADERS, null, 'A2');
        $sheet->getStyle('A2:G2')->getFont()->setBold(true);
        $sheet->getStyle('A2:G2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $row = 3;
        foreach ($events as $event) {
            $cancelled = $event->status === 'cancelled';
            $sheet->fromArray([
                self::dateLabel($event->event_date, $event->end_date),
                self::timeLabel($event->event_time, $event->end_time),
                $cancelled ? "{$event->name} (Cancelled)" : $event->name,
                $event->department,
                $event->location,
                $event->control_number,
                $event->remarks,
            ], null, "A{$row}");

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            if ($color = $event->venue?->building?->color) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($color, '#'));
            }
            if ($cancelled) {
                $sheet->getStyle("C{$row}")->getFont()->setStrikethrough(true);
            }
            $row++;
        }

        $last = max(2, $row - 1);
        $sheet->getStyle("A1:G{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
        $sheet->getStyle("A3:G{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        foreach (['A' => 22, 'B' => 20, 'C' => 42, 'D' => 26, 'E' => 26, 'F' => 14, 'G' => 30] as $column => $width) {
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

    private static function timeLabel(?string $start, ?string $end): string
    {
        if (! $start) {
            return 'TBA';
        }

        $format = fn ($time) => Carbon::parse($time)->format('g:i A');

        return $end ? "{$format($start)} – {$format($end)}" : $format($start);
    }
}
