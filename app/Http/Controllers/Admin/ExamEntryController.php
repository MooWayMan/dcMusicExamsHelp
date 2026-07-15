<?php
// app/Http/Controllers/Admin/ExamEntryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ExamEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $allowedSorts = [
            'exam_date',
            'order_number',
            'candidate_name',
            'candidate_number',
            'subject_area',
            'grade',
            'delivery_method',
            'result',
            'score',
            'teacher_name',
            'school_name',
        ];

        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString());
        $search = trim($request->string('search')->toString());
        $quarter = strtoupper(trim($request->string('quarter')->toString()));
        $studentId = $request->input('student_id');

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'exam_date';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        if (! in_array($quarter, ['', 'Q1', 'Q2', 'Q3', 'Q4'], true)) {
            $quarter = '';
        }

        // Shared filter logic — applied to both the paginated entries query AND the summary counts
        // so that switching the quarter pill or typing in search updates the stat cards too.
        $applyFilters = function ($q) use ($studentId, $search, $quarter) {
            if ($studentId) {
                $q->where('exam_entries.student_id', $studentId);
            }

            if ($search !== '') {
                $q->where(function ($qq) use ($search) {
                    $qq->where('exam_entries.candidate_name', 'ilike', "%{$search}%")
                        ->orWhere('exam_entries.candidate_number', 'ilike', "%{$search}%")
                        ->orWhere('exam_entries.teacher_name', 'ilike', "%{$search}%")
                        ->orWhere('exam_entries.school_name', 'ilike', "%{$search}%")
                        ->orWhere('orders.trinity_order_number', 'ilike', "%{$search}%");
                });
            }

            if ($quarter !== '') {
                $months = match ($quarter) {
                    'Q1' => [1, 2, 3],
                    'Q2' => [4, 5, 6],
                    'Q3' => [7, 8, 9],
                    'Q4' => [10, 11, 12],
                    default => [],
                };

                if ($months !== []) {
                    $q->whereIn(\DB::raw('EXTRACT(MONTH FROM exam_entries.exam_date)'), $months);
                }
            }

            return $q;
        };

        $query = ExamEntry::query()
            ->with('teacherContact:id,name')
            ->leftJoin('orders', 'exam_entries.order_id', '=', 'orders.id')
            ->select([
                'exam_entries.*',
                'orders.trinity_order_number as order_number',
            ]);
        $applyFilters($query);

        if ($sort === 'order_number') {
            $query->orderBy('orders.trinity_order_number', $direction);
        } else {
            $query->orderBy("exam_entries.{$sort}", $direction);
        }

        $query->orderBy('exam_entries.id', 'desc');

        $entries = $query
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($entry) => [
                'id' => $entry->id,
                'order_id' => $entry->order_id,
                'order_number' => $entry->order_number ?? '—',
                'candidate_number' => $entry->candidate_number,
                'candidate_name' => $entry->candidate_name,
                'grade' => $entry->grade,
                'subject_area' => $entry->subject_area,
                'delivery_method' => $entry->delivery_method,
                'result' => $entry->result,
                'score' => $entry->score,
                'exam_date' => $entry->exam_date?->format('d M Y'),
                // Teacher: prefer the FK relation (source of truth, set by
                // TrinityCsvImporter from 30 May 2026). Fall back to the
                // denormalised teacher_name string for pre-fix rows until
                // `php artisan exam-entries:repair-teacher-links` runs.
                'teacher_name' => $entry->teacherContact?->name ?? $entry->teacher_name,
                'teacher_contact_id' => $entry->teacher_contact_id,
                'school_name' => $entry->school_name,
                'fee' => $entry->fee !== null ? number_format((float) $entry->fee, 2) : null,
                // Editable via the inline edit modal (raw string, source of truth
                // for teacher is teacher_contact_id — see update()).
                'raw_teacher_name' => $entry->teacher_name,
                'notes' => $entry->notes,
                'show_full_name' => (bool) $entry->show_full_name,
            ]);

        // Summary stats — same filters applied so the cards reflect the visible table
        $summaryBase = fn () => $applyFilters(
            ExamEntry::query()->leftJoin('orders', 'exam_entries.order_id', '=', 'orders.id')
        );

        $summary = [
            'total' => $summaryBase()->count('exam_entries.id'),
            'with_results' => $summaryBase()->whereNotNull('exam_entries.result')->count('exam_entries.id'),
            'distinctions' => $summaryBase()->where('exam_entries.result', 'Distinction')->count('exam_entries.id'),
            'merits' => $summaryBase()->where('exam_entries.result', 'Merit')->count('exam_entries.id'),
            // Awaiting = no result AND not CANCELLED / NO_SHOW (those will never produce one).
            // Inline the filter (instead of the whereResultPossible scope) and qualify the
            // column explicitly because the query is joined with `orders`, which also has a
            // `notes` column — would otherwise throw "ambiguous column" on Postgres.
            'awaiting' => $summaryBase()
                ->whereNull('exam_entries.result')
                ->where(function ($q) {
                    $q->whereNull('exam_entries.notes')
                        ->orWhereNotIn('exam_entries.notes', \App\Models\ExamEntry::NOTES_NO_RESULT);
                })
                ->count('exam_entries.id'),
        ];

        return Inertia::render('admin/ExamEntries/Index', [
            'entries' => $entries,
            'summary' => $summary,
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
                'quarter' => $quarter,
                'student_id' => $studentId,
                'from' => $request->input('from'),
            ],
        ]);
    }

    /**
     * Inline correction of a single imported exam entry — the admin-panel
     * replacement for hopping into TablePlus to fix a wrong candidate name,
     * a parent-in-the-teacher-field attribution, or a result/score Trinity
     * reported wrong. Every change is written to the log with the before/after
     * values and the admin who made it, so corrections are auditable.
     *
     * NOTE: editing `teacher_name` sets the denormalised string only. If this
     * entry already has a confirmed `teacher_contact_id`, the list keeps
     * showing the linked contact's name (source of truth); use the contact
     * tools to re-point the FK. This edit is for the raw imported fields.
     */
    public function update(Request $request, ExamEntry $examEntry): RedirectResponse
    {
        $validated = $request->validate([
            'candidate_name' => ['nullable', 'string', 'max:255'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'show_full_name' => ['boolean'],
        ]);

        // Normalise blank text fields to null so we never store "" (and so the
        // audit diff below doesn't report null -> "" as a change).
        $blankToNull = fn (?string $v) => ($v === null || trim($v) === '') ? null : trim($v);

        $changes = [
            'candidate_name' => $blankToNull($validated['candidate_name'] ?? null),
            'teacher_name' => $blankToNull($validated['teacher_name'] ?? null),
            'result' => $blankToNull($validated['result'] ?? null),
            'score' => $validated['score'] ?? null,
            'notes' => $blankToNull($validated['notes'] ?? null),
            'show_full_name' => (bool) ($validated['show_full_name'] ?? false),
        ];

        // Capture only the fields that actually changed, for the audit line.
        $before = $examEntry->only(array_keys($changes));
        $examEntry->update($changes);
        $diff = collect($changes)
            ->filter(fn ($new, $key) => $before[$key] != $new)
            ->map(fn ($new, $key) => ['from' => $before[$key], 'to' => $new])
            ->all();

        if ($diff !== []) {
            Log::info('admin.exam_entry.updated', [
                'exam_entry_id' => $examEntry->id,
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'changes' => $diff,
            ]);
        }

        return back()->with('success', 'Exam entry updated.');
    }
}