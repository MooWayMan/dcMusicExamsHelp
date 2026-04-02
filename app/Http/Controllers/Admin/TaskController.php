<?php

// app/Http/Controllers/Admin/TaskController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Task::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('detail', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%")
                  ->orWhere('assigned_to', 'ilike', "%{$search}%");
            });
        }

        // Priority filter
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        // Status filter (default: show all tasks so completed ones stay visible)
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        }
        // 'all' shows everything

        // Category filter
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Active tasks first, then today's completions, then older completions at the very bottom
        $query->orderByRaw("CASE
            WHEN status != 'completed' THEN 0
            WHEN status = 'completed' AND completed_at >= CURRENT_DATE THEN 1
            ELSE 2
        END");

        // Default: newest first (date/time order)
        $query->orderBy('created_at', 'desc');

        $tasks = $query->paginate(50)->withQueryString();

        $tasks->through(fn ($task) => [
            'id' => $task->id,
            'title' => $task->title,
            'detail' => $task->detail,
            'notes' => $task->notes,
            'priority' => $task->priority,
            'status' => $task->status,
            'assigned_to' => $task->assigned_to,
            'category' => $task->category,
            'completed_at' => $task->completed_at?->format('d M Y H:i'),
            'completed_today' => $task->completed_at && $task->completed_at->isToday(),
            'created_at' => $task->created_at->format('d M Y'),
        ]);

        // Summary counts
        $summary = [
            'total' => Task::count(),
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
        ];

        return Inertia::render('admin/Tasks/Index', [
            'tasks' => $tasks,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'priority' => $priority,
                'status' => $status,
                'category' => $category,
                'sort' => 'created_at',
                'direction' => 'desc',
            ],
            'priorities' => Task::PRIORITIES,
            'statuses' => Task::STATUSES,
            'categories' => Task::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Tasks/Create', [
            'priorities' => Task::PRIORITIES,
            'categories' => Task::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'notes' => 'nullable|string',
            'priority' => 'required|in:high,medium,low',
            'assigned_to' => 'nullable|string|max:100',
            'category' => 'nullable|in:' . implode(',', Task::CATEGORIES),
        ]);

        $task = Task::create([
            'title' => $validated['title'],
            'detail' => $validated['detail'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? 'Paul',
            'category' => $validated['category'] ?? null,
            'sort_order' => Task::max('sort_order') + 1,
        ]);

        return redirect()->route('admin.tasks.index')
            ->with('success', "Task \"{$task->title}\" has been added.");
    }

    public function edit(Task $task): Response
    {
        return Inertia::render('admin/Tasks/Edit', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'detail' => $task->detail,
                'notes' => $task->notes,
                'priority' => $task->priority,
                'status' => $task->status,
                'assigned_to' => $task->assigned_to,
                'category' => $task->category,
            ],
            'priorities' => Task::PRIORITIES,
            'statuses' => Task::STATUSES,
            'categories' => Task::CATEGORIES,
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'notes' => 'nullable|string',
            'priority' => 'required|in:high,medium,low',
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|string|max:100',
            'category' => 'nullable|in:' . implode(',', Task::CATEGORIES),
        ]);

        // Track completion
        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        return redirect()->route('admin.tasks.index')
            ->with('success', "Task \"{$task->title}\" has been updated.");
    }

    public function destroy(Task $task): RedirectResponse
    {
        $title = $task->title;
        $task->delete();

        // Preserve current filters so search doesn't clear after deletion
        $filters = array_filter(request()->only(['search', 'priority', 'status', 'category']));

        return redirect()->route('admin.tasks.index', $filters)
            ->with('success', "Task \"{$title}\" has been removed.");
    }

    /**
     * AJAX: trigger GCal sync and return fresh active task count.
     * Called by frontend polling (e.g. sidebar refreshing every few minutes).
     * The actual sync is handled by the SyncCalendarTasks middleware,
     * so this just returns the current counts.
     */
    public function sync(): JsonResponse
    {
        // Middleware already ran the throttled sync, so just return fresh counts
        return response()->json([
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'total_active' => Task::where('status', '!=', 'completed')->count(),
        ]);
    }

    /**
     * AJAX: save notes inline from the index page expandable textarea.
     */
    public function updateNotes(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $task->update(['notes' => $validated['notes'] ?? null]);

        return response()->json(['saved' => true]);
    }

    /**
     * Toggle task completion via AJAX (for tick-box on index page).
     */
    public function toggle(Task $task): JsonResponse
    {
        if ($task->isCompleted()) {
            $task->markPending();
        } else {
            $task->markCompleted();
        }

        return response()->json([
            'status' => $task->fresh()->status,
            'completed_at' => $task->fresh()->completed_at?->format('d M Y H:i'),
        ]);
    }
}
