<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function event(Request $request, Event $event): Response
    {
        abort_unless($event->isVisibleTo($request->user()), 403, 'You are not assigned to this event.');

        $event->load([
            'tasks.assignee:id,name,email,role',
            'staff:id,name,email,role',
            'creator:id,name',
            'documents.uploader:id,name',
        ]);

        $taskSummary = [
            'total' => $event->tasks->count(),
            'done' => $event->tasks->where('status', 'done')->count(),
            'in_progress' => $event->tasks->where('status', 'in_progress')->count(),
            'pending' => $event->tasks->where('status', 'pending')->count(),
        ];
        $taskSummary['percent'] = $taskSummary['total'] > 0
            ? round(($taskSummary['done'] / $taskSummary['total']) * 100)
            : 0;

        $pdf = Pdf::loadView('pdf.event-report', [
            'event' => $event,
            'taskSummary' => $taskSummary,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'event-report-' . Str::slug($event->name) . '.pdf';

        return $pdf->download($filename);
    }
}
