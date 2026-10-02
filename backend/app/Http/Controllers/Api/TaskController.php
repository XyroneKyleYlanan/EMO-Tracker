<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Task;
use App\Rules\AssignableStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isVisibleTo($request->user()), 403, 'You are not assigned to this event.');

        $tasks = $event->tasks()
            ->with('assignee:id,name,email,role')
            ->orderBy('due_date')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $tasks = Task::where('assigned_to', $request->user()->id)
            ->with([
                'event:id,name,event_date,end_date,event_time,end_time,venue_id,venue_details,needs_preparation,status',
                'event.venue:id,name',
                'assignee:id,name,email,role',
            ])
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'tasks' => $tasks,
            'summary' => [
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'done' => $tasks->where('status', 'done')->count(),
                'total' => $tasks->count(),
            ],
        ]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        if ($event->status === 'completed' && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'This event is completed. Only an administrator can add tasks to it.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'assigned_to' => ['nullable', 'integer', new AssignableStaff],
        ]);

        $task = $event->tasks()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'],
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        // Planning tasks for an event means the EMO is preparing it.
        if (! $event->needs_preparation) {
            $event->update(['needs_preparation' => true]);
        }

        $task->load('assignee:id,name,email,role');

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if ($task->event->status === 'completed' && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'This event is completed. Only an administrator can modify its tasks.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'done'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'assigned_to' => ['nullable', 'integer', new AssignableStaff(array_filter([$task->assigned_to]))],
        ]);

        $task->update($data);
        $task->load('assignee:id,name,email,role');

        return response()->json(['task' => $task]);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        $isOwner = $task->assigned_to === $user->id;
        $canManage = in_array($user->role, ['admin', 'officer'], true);

        if (! $isOwner && ! $canManage) {
            return response()->json(['message' => 'You can only update your own tasks.'], 403);
        }

        if ($task->event->status === 'completed' && $user->role !== 'admin') {
            return response()->json([
                'message' => 'This event is completed. Only an administrator can modify its tasks.',
            ], 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'done'])],
        ]);

        $task->update($data);
        $task->load('assignee:id,name,email,role');

        return response()->json(['task' => $task]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($task->event->status === 'completed' && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'This event is completed. Only an administrator can delete its tasks.',
            ], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }
}
