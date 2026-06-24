<?php

// app/Http/Controllers/Admin/PendingResultsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Pending Results" — strict definition.
 *
 *   pending  =  exam_date is in the past
 *               AND no score recorded
 *               AND not cancelled
 *
 * Future-dated F2F exams aren't pending — they're just scheduled, so they
 * have no business inflating this list. Quarter selector mirrors QuarterEnd
 * so both pages anchor on the same time window.
 *
 * Date resolution falls back exam_date → order.requested_start_date so
 * legacy Q1 imports that never stamped an exam_date still show up under the
 * correct quarter.
 *
 * Cancellation filter is aligned with QuarterEnd (`notes != 'CANCELLED'`)
 * to keep both pages in agreement on what "cancelled" means in the data.
 */
class PendingResultsController extends Controller
{
    public function index(Request $request): Response
    {
        // Default to the CURRENT quarter — same as /admin/quarter-end and
        // /admin/certificates, so all three pages land on the same window
        // when opened.
        $defaultQuarter = (int) ceil(now()->month / 3);
        $defaultYear = (int) now()->year;

        $quarter = (int) $request->query('quarter', $defaultQuarter);
        $year = (int) $request->query('year', $defaultYear);

        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
            default => '?',
        };
        $quarterLabel = "{$suffix} Quarter {$year}";

        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();
        $today = now()->endOfDay();

        // Pull every unscored, non-cancelled entry, then filter in PHP so we
        // can use the exam_date ?? order.requested_start_date fallback (the
        // same approach QuarterEnd uses).
        $candidates = ExamEntry::with([
                'order:id,trinity_order_number,delivery_method,requested_start_date',
                'instrument:id,name',
                'student:id,first_name,last_name',
            ])
            ->whereNull('score')
            ->whereResultPossible();

        if ($search = $request->input('search')) {
            $candidates->where(function ($q) use ($search): void {
                $q->where('candidate_name', 'ilike', "%{$search}%")
                    ->orWhere('candidate_number', 'ilike', "%{$search}%")
                    ->orWhere('teacher_name', 'ilike', "%{$search}%")
                    ->orWhere('school_name', 'ilike', "%{$search}%");
            });
        }

        if ($method = $request->input('method')) {
            $candidates->where('delivery_method', $method);
        }

        $entries = $candidates
            ->orderBy('created_at', 'asc')
            ->get()
            ->filter(function (ExamEntry $entry) use ($startDate, $endDate, $today): bool {
                $effectiveDate = $entry->exam_date ?? $entry->order?->requested_start_date;

                // Strict pending: must have a date that's in the past AND
                // falls inside the selected quarter window.
                if (! $effectiveDate) {
                    return false;
                }

                return $effectiveDate->lte($today)
                    && $effectiveDate->between($startDate, $endDate);
            })
            ->values();

        $data = $entries->map(fn (ExamEntry $e) => [
            'id' => $e->id,
            'order_id' => $e->order_id,
            'student_id' => $e->student_id,
            'teacher_contact_id' => $e->teacher_contact_id,
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
            'order_date' => ($e->exam_date ?? $e->order?->requested_start_date)?->format('d M Y') ?? '—',
        ]);

        // Summary — scoped to the selected quarter, mirroring QuarterEnd. The
        // 'with_results' and 'total' counts use the same date window so the
        // three numbers always describe the same population.
        $quarterScoped = ExamEntry::with('order:id,requested_start_date')
            ->whereResultPossible()
            ->get()
            ->filter(function (ExamEntry $entry) use ($startDate, $endDate): bool {
                $effectiveDate = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $effectiveDate && $effectiveDate->between($startDate, $endDate);
            });

        $totalPending = $entries->count();
        $totalWithResults = $quarterScoped->whereNotNull('score')->count();
        $totalEntries = $quarterScoped->count();

        // Orders booked but with no candidate triple imported yet. These have
        // ZERO exam_entries rows, so they're invisible to the entries-driven
        // list above — without this the page reads "All results collected"
        // while orders are genuinely waiting on Trinity to issue enrolment
        // data. Scoped to the same quarter window and method pill as the rest
        // of the page; date-passed only (a future requested_start is just
        // scheduled, not awaiting), mirroring the strict "pending" definition.
        $awaitingImportQuery = Order::query()
            ->whereDoesntHave('examEntries')
            ->whereNotNull('requested_start_date')
            ->whereBetween('requested_start_date', [$startDate, $endDate])
            ->where('requested_start_date', '<=', $today)
            ->where(function ($q): void {
                $q->whereNull('notes')
                    ->orWhere('notes', '!=', ExamEntry::NOTE_CANCELLED);
            });

        if ($method) {
            $awaitingImportQuery->where('delivery_method', $method);
        }

        $awaitingImport = $awaitingImportQuery
            ->orderBy('requested_start_date', 'asc')
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'order_number' => $o->trinity_order_number ?? '—',
                'status' => $o->order_status ?? '—',
                'delivery_method' => $o->delivery_method ?? '—',
                'requested_start_date' => $o->requested_start_date?->format('d M Y') ?? '—',
                'days_waiting' => $o->requested_start_date
                    ? (int) round(abs($o->requested_start_date->diffInDays($today)))
                    : null,
            ])
            ->values();

        return Inertia::render('admin/PendingResults/Index', [
            'entries' => $data,
            'awaitingImport' => $awaitingImport,
            'summary' => [
                'pending' => $totalPending,
                'with_results' => $totalWithResults,
                'total' => $totalEntries,
                'awaiting_import' => $awaitingImport->count(),
            ],
            'filters' => [
                'search' => $search,
                'method' => $method,
            ],
            'quarter' => $quarter,
            'year' => $year,
            'quarterLabel' => $quarterLabel,
        ]);
    }
}
