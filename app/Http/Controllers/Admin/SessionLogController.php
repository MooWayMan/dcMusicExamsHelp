<?php

// app/Http/Controllers/Admin/SessionLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SessionLog;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionLogController extends Controller
{
    public function index(): Response
    {
        $logs = SessionLog::orderBy('date', 'asc')->get();

        $totalHours = $logs->sum('hours');

        // Get tasks completed per day
        $tasksPerDay = Task::whereNotNull('completed_at')
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->pluck('count', 'date')
            ->toArray();

        $totalTasksCompleted = array_sum($tasksPerDay);

        $chartData = $logs->map(fn (SessionLog $log) => [
            'date' => $log->date->format('D j M'),
            'fullDate' => $log->date->format('Y-m-d'),
            'hours' => (float) $log->hours,
            'tasks' => $tasksPerDay[$log->date->format('Y-m-d')] ?? 0,
            'notes' => $log->notes,
        ])->values()->toArray();

        return Inertia::render('admin/SessionLogs/Index', [
            'logs' => $logs->map(fn (SessionLog $log) => [
                'id' => $log->id,
                'date' => $log->date->format('Y-m-d'),
                'dateFormatted' => $log->date->format('D j M Y'),
                'hours' => (float) $log->hours,
                'tasks' => $tasksPerDay[$log->date->format('Y-m-d')] ?? 0,
                'notes' => $log->notes,
            ])->reverse()->values(),
            'chartData' => $chartData,
            'totalHours' => round($totalHours, 1),
            'totalDays' => $logs->count(),
            'totalTasksCompleted' => $totalTasksCompleted,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:session_logs,date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'notes' => 'nullable|string|max:1000',
        ]);

        SessionLog::create($validated);

        return redirect()->route('admin.session-logs.index');
    }

    public function update(Request $request, SessionLog $sessionLog): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:session_logs,date,' . $sessionLog->id,
            'hours' => 'required|numeric|min:0.5|max:24',
            'notes' => 'nullable|string|max:1000',
        ]);

        $sessionLog->update($validated);

        return redirect()->route('admin.session-logs.index');
    }

    public function destroy(SessionLog $sessionLog): RedirectResponse
    {
        $sessionLog->delete();

        return redirect()->route('admin.session-logs.index');
    }
}
