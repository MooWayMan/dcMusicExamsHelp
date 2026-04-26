<?php
// app/Http/Controllers/Admin/ExamEntryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use Illuminate\Http\Request;
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
                'teacher_name' => $entry->teacher_name,
                'school_name' => $entry->school_name,
                'fee' => $entry->fee !== null ? number_format((float) $entry->fee, 2) : null,
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
}