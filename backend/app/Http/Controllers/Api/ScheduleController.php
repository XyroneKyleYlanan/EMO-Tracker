<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ScheduleExport;
use App\Services\VenueClashes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * The EMO's schedule sheet: every NEU event for a year, visible to all roles.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $years = Event::pluck('event_date')->map->year->push(today()->year)->unique()->sort()->values();
        $year = (int) $request->input('year', today()->year);

        $events = Event::with(['venue.building', Event::READINESS_TASKS])
            ->whereYear('event_date', $year)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        // Double bookings matter for events still to come; past ones can't be fixed.
        $clashes = VenueClashes::within($events);

        $rows = $events->map(fn (Event $e) => [
            'id' => $e->id,
            'name' => $e->name,
            'event_type' => $e->event_type,
            'department' => $e->department,
            'event_date' => $e->event_date->toDateString(),
            'end_date' => $e->end_date?->toDateString(),
            'event_time' => $e->event_time,
            'end_time' => $e->end_time,
            'original_date' => $e->original_date?->toDateString(),
            'original_time' => $e->original_time,
            'location' => $e->location,
            'building' => $e->venue?->building?->only(['id', 'name', 'color']),
            'control_number' => $e->control_number,
            'remarks' => $e->remarks,
            'status' => $e->status,
            'ongoing' => $e->ongoing,
            'clashes' => $e->status === 'upcoming' ? collect($clashes[$e->id] ?? [])->pluck('name')->values() : [],
            'readiness' => $e->readiness,
            'needs_preparation' => $e->needs_preparation,
        ]);

        return response()->json(['year' => $year, 'current_year' => today()->year, 'years' => $years, 'events' => $rows]);
    }

    /**
     * The year as an Excel (.xlsx, the default) or CSV file, with only the rows
     * the page shows when its type filter or search is in use.
     */
    public function export(Request $request): Response
    {
        $request->validate([
            'format' => ['nullable', Rule::in(['xlsx', 'csv'])],
            'type' => ['nullable', Rule::in(['internal', 'external'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $year = (int) $request->input('year', today()->year);
        $format = $request->input('format', 'xlsx');
        $type = $request->input('type');
        $search = trim((string) $request->input('search'));

        $events = ScheduleExport::events($year, $type, $search);
        $note = implode(' · ', array_filter([
            $type ? ucfirst($type).' events' : null,
            $search !== '' ? "Search: \"{$search}\"" : null,
        ]));
        $name = implode('-', array_filter(['emo-schedule', $year, $type, $search !== '' ? 'filtered' : null]));

        return $format === 'csv'
            ? response(ScheduleExport::csv($events), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$name}.csv\"",
            ])
            : response(ScheduleExport::xlsx($year, $events, $note ?: null), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$name}.xlsx\"",
            ]);
    }
}
