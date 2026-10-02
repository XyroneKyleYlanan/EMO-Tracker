<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Event Report — {{ $event->name }}</title>
    <style>
        @page { margin: 40px 50px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.5; }

        .header { border-bottom: 2px solid #0a5c3a; padding-bottom: 12px; margin-bottom: 20px; }
        .header .brand { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .header h1 { font-size: 22px; margin: 4px 0 6px 0; color: #111827; }
        .header .sub { font-size: 11px; color: #4b5563; font-weight: 600; }
        .header .sub-row { margin-top: 6px; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #e2e8f0; color: #475569; }
        .badge-scheduled { background: #cffafe; color: #155e75; }
        .badge-cancelled { background: #f3f4f6; color: #6b7280; }
        .badge-status { background: #f3f4f6; color: #4b5563; }
        .reason { font-size: 10px; color: #4b5563; margin-left: 6px; }

        .section { margin-bottom: 18px; }
        .section h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin: 0 0 8px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }

        .meta-grid { width: 100%; }
        .meta-grid td { padding: 4px 0; vertical-align: top; color: #111827; }
        .meta-grid td.label { color: #6b7280; width: 110px; font-size: 10px; }

        .stat-row { width: 100%; margin: 6px 0 12px 0; }
        .stat-row td { width: 25%; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; text-align: center; }
        .stat-row .num { font-size: 16px; font-weight: bold; color: #111827; }
        .stat-row .lbl { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

        table.tasks { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.tasks th { background: #f3f4f6; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        table.tasks td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 10px; }
        .priority-low { color: #64748b; font-size: 9px; }
        .priority-medium { color: #d97706; font-size: 9px; }
        .priority-high { color: #dc2626; font-size: 9px; font-weight: bold; }

        .staff-list { color: #4b5563; }
        .staff-list .pill { display: inline-block; padding: 2px 8px; background: #f3f4f6; border-radius: 3px; margin: 2px 4px 2px 0; font-size: 10px; }

        .doc-list td { padding: 4px 0; font-size: 10px; }
        .empty { color: #9ca3af; font-style: italic; font-size: 10px; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 60px; vertical-align: middle; border: none;">
                <img src="{{ public_path('images/neu-logo.png') }}" alt="NEU" style="width: 50px; height: 50px;">
            </td>
            <td style="vertical-align: middle; border: none; padding-left: 4px;">
                <div class="brand">EMO Tracker · Events Management Office</div>
                <div style="font-size: 9px; color: #6b7280; letter-spacing: 0.5px;">NEW ERA UNIVERSITY</div>
            </td>
        </tr>
    </table>
    <h1>{{ $event->name }}</h1>
    <div class="sub">Event Report</div>
    <div class="sub-row">
        <span class="badge badge-{{ $event->readiness }}">
            @if($event->readiness === 'green') On Track
            @elseif($event->readiness === 'yellow') At Risk
            @elseif($event->readiness === 'red') Critical
            @elseif($event->readiness === 'scheduled') Scheduled
            @elseif($event->readiness === 'cancelled') Cancelled
            @else Completed
            @endif
        </span>
        @if($event->readiness_reason)
            <span class="reason">{{ $event->readiness_reason }}</span>
        @endif
    </div>
</div>

<div class="section">
    <h2>Event Details</h2>
    <table class="meta-grid">
        <tr>
            <td class="label">Date</td>
            <td>
                {{ $event->event_date->format('l, F j, Y') }}
                @if($event->end_date && ! $event->end_date->equalTo($event->event_date))
                    – {{ $event->end_date->format('l, F j, Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Time</td>
            <td>
                @if($event->event_time)
                    {{ \Carbon\Carbon::parse($event->event_time)->format('g:i A') }}@if($event->end_time) – {{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }}@endif
                @else
                    To be announced
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Venue</td>
            <td>{{ $event->location ?? 'To be announced' }}</td>
        </tr>
        @if($event->department)
        <tr>
            <td class="label">Department</td>
            <td>{{ $event->department }}</td>
        </tr>
        @endif
        @if($event->budget)
        <tr>
            <td class="label">Budget</td>
            <td>PHP {{ number_format($event->budget, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Status</td>
            <td>{{ ucfirst($event->status) }}</td>
        </tr>
        @if($event->creator)
        <tr>
            <td class="label">Created by</td>
            <td>{{ $event->creator->name }}</td>
        </tr>
        @endif
        @if($event->description)
        <tr>
            <td class="label">Description</td>
            <td>{{ $event->description }}</td>
        </tr>
        @endif
    </table>
</div>

<div class="section">
    <h2>Task Summary</h2>
    <table class="stat-row">
        <tr>
            <td><div class="num">{{ $taskSummary['total'] }}</div><div class="lbl">Total Tasks</div></td>
            <td><div class="num">{{ $taskSummary['done'] }}</div><div class="lbl">Done</div></td>
            <td><div class="num">{{ $taskSummary['in_progress'] }}</div><div class="lbl">In Progress</div></td>
            <td><div class="num">{{ $taskSummary['percent'] }}%</div><div class="lbl">Completion</div></td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>People ({{ $people->count() }})</h2>
    @if($people->count() > 0)
        <div class="staff-list">
            @foreach($people as $s)
                <span class="pill">{{ $s->name }}</span>
            @endforeach
        </div>
    @else
        <div class="empty">No one has a task on this event yet.</div>
    @endif
</div>

<div class="section">
    <h2>Tasks ({{ $event->tasks->count() }})</h2>
    @if($event->tasks->count() > 0)
        <table class="tasks">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Assignee</th>
                    <th>Due</th>
                    <th>Priority</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($event->tasks as $task)
                <tr>
                    <td>{{ $task->name }}</td>
                    <td>{{ $task->assignee->name ?? 'Unassigned' }}</td>
                    <td>{{ \Carbon\Carbon::parse($task->due_date)->format('M j, Y') }}</td>
                    <td class="priority-{{ $task->priority }}">{{ strtoupper($task->priority) }}</td>
                    <td>
                        @if($task->status === 'done')
                            <span class="badge badge-green">DONE</span>
                        @elseif($task->status === 'in_progress')
                            <span class="badge badge-yellow">IN PROGRESS</span>
                        @else
                            <span class="badge badge-status">PENDING</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No tasks for this event.</div>
    @endif
</div>

<div class="section">
    <h2>Attached Documents ({{ $event->documents->count() }})</h2>
    @if($event->documents->count() > 0)
        <table class="doc-list">
            @foreach($event->documents as $doc)
            <tr>
                <td>· {{ $doc->file_name }} <span style="color:#9ca3af;">— uploaded by {{ $doc->uploader->name }}, {{ \Carbon\Carbon::parse($doc->created_at)->format('M j, Y') }}</span></td>
            </tr>
            @endforeach
        </table>
    @else
        <div class="empty">No documents attached.</div>
    @endif
</div>

<div class="footer">
    Generated on {{ $generatedAt->format('F j, Y · g:i A') }} · EMO Tracker · For internal use only
</div>

</body>
</html>
