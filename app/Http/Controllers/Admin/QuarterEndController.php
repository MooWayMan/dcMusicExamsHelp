<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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

        // --- PRIZE DRAW DATA ---
        // Student draw: every entry = one ticket (all students eligible)
        $studentTickets = $allEntries->map(fn ($e) => [
            'name' => $e->candidate_name ?? ($e->student ? "{$e->student->first_name} {$e->student->last_name}" : 'Unknown'),
            'instrument' => $e->instrument?->name ?? 'Unknown',
            'grade' => $e->grade,
            'teacher' => $e->teacher_name ?? 'Unknown',
        ])->values()->toArray();

        // Teacher draw: get registered teachers from users table
        $registeredTeacherNames = User::where('role', 'teacher')
            ->get()
            ->map(fn ($u) => strtolower(trim($u->name)))
            ->toArray();

        // Build teacher/applicant eligibility from orders in this quarter
        $applicantEntries = $allEntries->groupBy(function ($e) {
            return $e->order?->applicant_name ?? $e->teacher_name ?? 'Unknown';
        });

        $teacherTickets = [];
        foreach ($applicantEntries as $applicantName => $entries) {
            $entryCount = $entries->count();
            $isRegistered = in_array(strtolower(trim($applicantName)), $registeredTeacherNames);

            // Eligibility: registered = always, 2+ entries = eligible, 1 entry = not eligible (likely parent)
            $eligible = $isRegistered || $entryCount >= 2;

            if ($eligible) {
                // More entries = more tickets
                for ($i = 0; $i < $entryCount; $i++) {
                    $teacherTickets[] = [
                        'name' => $applicantName,
                        'entries' => $entryCount,
                        'is_registered' => $isRegistered,
                    ];
                }
            }
        }

        // Unique eligible teachers for display
        $eligibleTeachers = collect($applicantEntries)->map(function ($entries, $name) use ($registeredTeacherNames) {
            $count = $entries->count();
            $isRegistered = in_array(strtolower(trim($name)), $registeredTeacherNames);
            $eligible = $isRegistered || $count >= 2;

            return [
                'name' => $name,
                'entries' => $count,
                'is_registered' => $isRegistered,
                'eligible' => $eligible,
                'reason' => $isRegistered ? 'Registered teacher' : ($count >= 2 ? "{$count} entries" : 'Only 1 entry (likely parent)'),
            ];
        })->values()->toArray();

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
            'prizeDraw' => [
                'student_tickets' => $studentTickets,
                'teacher_tickets' => $teacherTickets,
                'eligible_teachers' => $eligibleTeachers,
                'student_ticket_count' => count($studentTickets),
                'teacher_ticket_count' => count($teacherTickets),
            ],
        ]);
    }

    /**
     * Run a prize draw (AJAX) — picks a random winner from the ticket pool.
     */
    public function runDraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:student,teacher',
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer',
        ]);

        $quarter = $validated['quarter'];
        $year = $validated['year'];

        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        $allEntries = ExamEntry::with(['instrument:id,name', 'order:id,requested_start_date,applicant_name', 'student:id,first_name,last_name'])
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        if ($validated['type'] === 'student') {
            // Every entry = one ticket, pick one at random
            if ($allEntries->isEmpty()) {
                return response()->json(['error' => 'No entries to draw from'], 422);
            }

            $winner = $allEntries->random();

            return response()->json([
                'type' => 'student',
                'winner' => [
                    'name' => $winner->candidate_name ?? ($winner->student ? "{$winner->student->first_name} {$winner->student->last_name}" : 'Unknown'),
                    'instrument' => $winner->instrument?->name ?? 'Unknown',
                    'grade' => $winner->grade,
                    'teacher' => $winner->teacher_name ?? 'Unknown',
                ],
                'total_tickets' => $allEntries->count(),
            ]);
        }

        // Teacher draw
        $registeredTeacherNames = User::where('role', 'teacher')
            ->get()
            ->map(fn ($u) => strtolower(trim($u->name)))
            ->toArray();

        $applicantEntries = $allEntries->groupBy(function ($e) {
            return $e->order?->applicant_name ?? $e->teacher_name ?? 'Unknown';
        });

        $tickets = [];
        foreach ($applicantEntries as $applicantName => $entries) {
            $entryCount = $entries->count();
            $isRegistered = in_array(strtolower(trim($applicantName)), $registeredTeacherNames);

            if ($isRegistered || $entryCount >= 2) {
                for ($i = 0; $i < $entryCount; $i++) {
                    $tickets[] = $applicantName;
                }
            }
        }

        if (empty($tickets)) {
            return response()->json(['error' => 'No eligible teachers to draw from'], 422);
        }

        $winnerName = $tickets[array_rand($tickets)];
        $winnerEntries = $applicantEntries[$winnerName]->count();
        $isRegistered = in_array(strtolower(trim($winnerName)), $registeredTeacherNames);

        return response()->json([
            'type' => 'teacher',
            'winner' => [
                'name' => $winnerName,
                'entries' => $winnerEntries,
                'is_registered' => $isRegistered,
            ],
            'total_tickets' => count($tickets),
        ]);
    }
}
