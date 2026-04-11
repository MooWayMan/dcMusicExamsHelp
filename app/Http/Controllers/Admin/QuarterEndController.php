<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuarterEndController extends Controller
{
    public function index(Request $request): Response
    {
        $quarter = (int) ($request->query('quarter', ceil(now()->month / 3)));
        $year = (int) ($request->query('year', now()->year));

        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
        };
        $quarterLabel = "{$suffix} Quarter {$year}";

        // Date range
        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        // Get all entries for this quarter (with and without scores)
        $allEntries = ExamEntry::with(['instrument:id,name', 'order:id,requested_start_date,delivery_method,applicant_name,applicant_email', 'student:id,first_name,last_name'])
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        // Group by teacher
        $teacherGroups = $allEntries->groupBy(fn ($e) => $e->teacher_name ?? 'Parent Bookings (no teacher assigned)');

        $teachers = $teacherGroups->map(function ($entries, $teacherName) use ($quarterLabel) {
            $withScores = $entries->filter(fn ($e) => $e->score !== null && $e->score >= 60);
            $pending = $entries->filter(fn ($e) => $e->score === null);

            // Get applicant email from the first order (for contact)
            $firstOrder = $entries->first()?->order;

            // Certificate breakdown
            $distinctions = $withScores->filter(fn ($e) => $e->score >= 87)->count();
            $merits = $withScores->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();
            $passes = $withScores->filter(fn ($e) => $e->score >= 60 && $e->score < 75)->count();

            // Total entries for badge eligibility (across ALL time, not just this quarter)
            $totalAllTime = ExamEntry::where('teacher_name', $teacherName)
                ->where(function ($q) {
                    $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
                })
                ->count();

            $badgeTier = match (true) {
                $totalAllTime >= 40 => 'Top Award',
                $totalAllTime >= 30 => 'Gold',
                $totalAllTime >= 20 => 'Silver',
                $totalAllTime >= 10 => 'Bronze',
                default => null,
            };

            return [
                'teacher_name' => $teacherName,
                'applicant_email' => $firstOrder?->applicant_email,
                'applicant_name' => $firstOrder?->applicant_name,
                'total_entries' => $entries->count(),
                'with_results' => $withScores->count(),
                'pending' => $pending->count(),
                'distinctions' => $distinctions,
                'merits' => $merits,
                'passes' => $passes,
                'badge_tier' => $badgeTier,
                'total_all_time' => $totalAllTime,
                'students' => $withScores->map(fn ($e) => [
                    'name' => $e->candidate_name,
                    'instrument' => $e->instrument?->name ?? 'Unknown',
                    'grade' => $e->grade,
                    'score' => $e->score,
                    'result' => $e->result_band,
                    'certificate' => $e->certificate_name,
                    'method' => $e->delivery_method,
                ])->values()->toArray(),
            ];
        })->sortByDesc('total_entries')->values()->toArray();

        // Summary stats
        $totalEntries = $allEntries->count();
        $totalWithResults = $allEntries->filter(fn ($e) => $e->score !== null && $e->score >= 60)->count();
        $totalPending = $allEntries->filter(fn ($e) => $e->score === null)->count();
        $totalFees = $allEntries->sum('fee');

        // Top scorer
        $topScorer = $allEntries->filter(fn ($e) => $e->score !== null)->sortByDesc('score')->first();

        return Inertia::render('admin/QuarterEnd/Index', [
            'quarter' => $quarter,
            'year' => $year,
            'quarterLabel' => $quarterLabel,
            'teachers' => $teachers,
            'summary' => [
                'total_entries' => $totalEntries,
                'with_results' => $totalWithResults,
                'pending' => $totalPending,
                'total_fees' => number_format($totalFees, 2),
                'teacher_count' => $teacherGroups->count(),
                'top_scorer' => $topScorer ? [
                    'name' => $topScorer->candidate_name,
                    'score' => $topScorer->score,
                    'instrument' => $topScorer->instrument?->name,
                ] : null,
            ],
        ]);
    }
}
