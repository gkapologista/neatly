<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Auth::user()->tasks()
            ->orderByRaw("CASE 
                WHEN type = 'daily' THEN 1 
                WHEN type = 'weekly' THEN 2 
                WHEN type = 'monthly' THEN 3 
                WHEN type = 'custom' THEN 4 
                ELSE 5 
            END")
            ->orderBy('is_completed')
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly,custom',
            'scheduled_at' => 'nullable|date|required_if:type,custom',
            'scheduled_time' => 'nullable|date_format:H:i|required_if:type,daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|between:1,7|required_if:type,weekly',
            'day_of_month' => 'nullable|integer|between:1,31|required_if:type,monthly',
            'frequency' => 'nullable|string',
        ]);

        $task = Auth::user()->tasks()->create($validated);

        \App\Events\TaskCreated::dispatch($task);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        // Ensure user owns the task
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_completed' => 'sometimes|boolean',
            'type' => 'sometimes|in:daily,weekly,monthly,custom',
            'scheduled_at' => 'nullable|date|required_if:type,custom',
            'scheduled_time' => 'nullable|date_format:H:i|required_if:type,daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|between:1,7|required_if:type,weekly',
            'day_of_month' => 'nullable|integer|between:1,31|required_if:type,monthly',
        ]);

        if (isset($validated['is_completed'])) {
            $validated['completed_at'] = $validated['is_completed'] ? now() : null;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tasks', 'public');
            $validated['image_path'] = $path;
        }

        $task->update($validated);

        \App\Events\TaskUpdated::dispatch($task);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $taskId = $task->id;
        $userId = $task->user_id;

        $task->delete();

        \App\Events\TaskDeleted::dispatch($taskId, $userId);

        return redirect()->back();
    }
}
