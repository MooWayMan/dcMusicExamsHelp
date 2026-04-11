<?php

// app/Http/Controllers/Admin/PendingResultsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingResultsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ExamEntry::with(['order:id,trinity_order_number,delivery_method,requested_start_date', 'instrument:id,name', 'student:id,first_name,last_name'])
            ->whereNull('score')
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere('notes', 'not ilike', '%cancelled%');
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'ilike', "%{$search}%")
                  ->orWhere('candidate_number', 'ilike', "%{$search}%")
                  ->orWhere('teacher_name', 'ilike', "%{$search}%")
                  ->orWhere('school_name', 'ilike', "%{$search}%");
            });
        }

        // Delivery method filter
        if ($method = $request->input('method')) {
            $query->where('delivery_method', $method);
        }

        $entries = $query->orderBy('created_at', 'asc')->get();

        $data = $entries->map(fn ($e) => [
            'id' => $e->id,
            'candidate_number' => $e->candidate_number ?? '—',
            'candidate_name' => $e->candidate_name
                ?? ($e->student ? "{$e->student->first_name} {$e->student->last_name}" : '—'),
            'instrument' => $e->instrument->name ?? '—',
            'grade' => $e->grade,
            'delivery_method' => $e->delivery_method,
            'subject_area' => $e->subject_area,
            'teacher_name' => $e->teacher_name ?? '—',
            'school_name' => $e->school_name ?? '—',
            'fee' => $e->fee ? number_format($e->fee, 2) : '—',
            'order_number' => $e->order->trinity_order_number ?? '—',
            'order_date' => $e->order->requested_start_date?->format('d M Y') ?? '—',
        ]);

        // Summary — exclude cancellations from all counts
        $excludeCancelled = function ($query) {
            $query->whereNull('notes')->orWhere('notes', 'not ilike', '%cancelled%');
        };

        $totalPending = $data->count();
        $totalWithResults = ExamEntry::whereNotNull('score')->where($excludeCancelled)->count();
        $totalEntries = ExamEntry::where($excludeCancelled)->count();

        return Inertia::render('admin/PendingResults/Index', [
            'entries' => $data,
            'summary' => [
                'pending' => $totalPending,
                'with_results' => $totalWithResults,
                'total' => $totalEntries,
            ],
            'filters' => [
                'search' => $search,
                'method' => $method,
            ],
        ]);
    }
}
