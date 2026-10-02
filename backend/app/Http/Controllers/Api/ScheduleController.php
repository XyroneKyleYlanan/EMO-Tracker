<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ScheduleExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The EMO's schedule sheet: every NEU event for a year, visible to all roles.
 * Staff can see the whole schedule but only open the events they're assigned to.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $years = Event::pluck('event_date')->map->year->push(today()->year)->unique()->sort()->values();
        $year = (int) $request->input('year', today()->year);

        $events = Event::with(['venue.building', 'tasks:id,event_id,status,assigned_to'])
            ->whereYear('event_date', $year)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        $openable = $user->isStaff() ? Event::visibleTo($user)->pluck('id')->flip() : null;

        $rows = $events->map(fn (Event $e) => [
            'id' => $e->id,
            'name' => $e->name,
            'department' => $e->department,
            'event_date' => $e->event_date->toDateString(),
            'end_date' => $e->end_date?->toDateString(),
            'event_time' => $e->event_time,
            'end_time' => $e->end_time,
            'location' => $e->location,
            'building' => $e->venue?->building?->only(['id', 'name', 'color']),
            'control_number' => $e->control_number,
            'remarks' => $e->remarks,
            'status' => $e->status,
            'readiness' => $e->readiness,
            'needs_preparation' => $e->needs_preparation,
            'can_open' => $openable === null || $openable->has($e->id),
        ]);

        return response()->json(['year' => $year, 'current_year' => today()->year, 'years' => $years, 'events' => $rows]);
    }

    public function export(Request $request): Response
    {
        $year = (int) $request->input('year', today()->year);

        return response(ScheduleExport::build($year), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"emo-schedule-{$year}.xlsx\"",
        ]);
    }
}
