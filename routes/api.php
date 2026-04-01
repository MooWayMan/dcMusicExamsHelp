<?php

// routes/api.php

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – used by automated integrations (e.g. Google Calendar sync)
|--------------------------------------------------------------------------
| Protected by a simple bearer token stored in TASK_API_TOKEN env var.
*/

Route::middleware('throttle:30,1')->group(function () {

    Route::post('/tasks/from-calendar', function (Request $request) {
        // Verify bearer token
        $token = $request->bearerToken();
        $expected = config('services.task_api.token');

        if (! $token || ! $expected || $token !== $expected) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'priority' => 'nullable|in:high,medium,low',
            'assigned_to' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:50',
            'calendar_event_id' => 'nullable|string|max:255',
        ]);

        // Skip if a task with the same calendar_event_id already exists
        if (! empty($validated['calendar_event_id'])) {
            $existing = Task::where('calendar_event_id', $validated['calendar_event_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Task already exists',
                    'task_id' => $existing->id,
                    'skipped' => true,
                ]);
            }
        }

        // Also skip if an identical title exists and was created today
        $duplicate = Task::where('title', $validated['title'])
            ->whereDate('created_at', now()->toDateString())
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicate task found for today',
                'task_id' => $duplicate->id,
                'skipped' => true,
            ]);
        }

        $task = Task::create([
            'title' => $validated['title'],
            'detail' => $validated['detail'] ?? 'Added via voice (Google Calendar sync)',
            'priority' => $validated['priority'] ?? 'medium',
            'assigned_to' => $validated['assigned_to'] ?? 'Paul',
            'category' => $validated['category'] ?? 'general',
            'calendar_event_id' => $validated['calendar_event_id'] ?? null,
            'sort_order' => Task::max('sort_order') + 1,
        ]);

        return response()->json([
            'message' => 'Task created',
            'task_id' => $task->id,
            'title' => $task->title,
            'skipped' => false,
        ], 201);
    })->name('api.tasks.from-calendar');

});
