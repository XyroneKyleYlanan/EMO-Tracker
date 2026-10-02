<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function event(Event $event): Response
    {
        $event->load([
            'tasks.assignee:id,name,email,role',
            'creator:id,name',
            'documents.uploader:id,name',
            'venue',
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
            // The people on an event are everyone with a task on it.
            'people' => $event->tasks->pluck('assignee')->filter()->unique('id')->sortBy('name')->values(),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'event-report-'.Str::slug($event->name).'.pdf';

        return $pdf->download($filename);
    }
}
