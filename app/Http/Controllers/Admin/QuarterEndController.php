<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\PrizeDraw;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuarterEndController extends Controller
{
    public function index(Request $request): Response
    {
        // Default to the CURRENT quarter — same as /admin/certificates, so
        // both pages land on the same place when opened. If Paul wants a past
        // quarter's end-of-quarter tidy-up, he clicks the selector.
        $defaultQuarter = (int) ceil(now()->month / 3);
        $defaultYear = (int) now()->year;

        $quarter = (int) ($request->query('quarter', $defaultQuarter));
        $year = (int) ($request->query('year', $defaultYear));

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

        // Parents and self-bookers stamped as teacher_name during import need
        // the same Copy Email + Open Gmail workflow as teachers — they just
        // get a parent-variant template. Build a lookup so we can tag each row
        // with is_parent_booking and fetch the correct email from ExamContact.
        $parentOrSelfLookup = ExamContact::with('emails')
            ->whereIn('role', ['parent', 'self'])
            ->get()
            ->keyBy(fn ($c) => strtolower(trim($c->name)));

        // Group by teacher/parent name — each individual parent becomes their
        // own row (not lumped), so Paul can email each directly. Rows with no
        // teacher_name at all stay in the catch-all bucket.
        $teacherGroups = $allEntries->groupBy(fn ($e) => $e->teacher_name ?? 'Parent Bookings (no teacher assigned)');

        $teachers = $teacherGroups->map(function ($entries, $teacherName) use ($parentOrSelfLookup) {
            $withScores = $entries->filter(fn ($e) => $e->score !== null && $e->score >= 60);
            $pending = $entries->filter(fn ($e) => $e->score === null);

            // Is this row a parent/self booking?
            $parentContact = $parentOrSelfLookup->get(strtolower(trim($teacherName)));
            $isParentBooking = $parentContact !== null;
            $bookingRole = $parentContact?->role; // 'parent' | 'self' | null

            // Get email — different strategy for parents vs teachers.
            $firstOrder = $entries->first()?->order;
            $ownOrder = $entries->first(fn ($e) => $e->order && strtolower(trim($e->order->applicant_name ?? '')) === strtolower(trim($teacherName)))?->order;

            if ($isParentBooking) {
                // Parents: use their ExamContact email; fall back to their
                // own-order applicant_email if they actually applied themselves.
                $teacherEmail = $parentContact->primary_email ?? $ownOrder?->applicant_email;
            } else {
                $teacherRecord = Teacher::with('emails')->where('name', $teacherName)->first();
                $teacherEmail = $teacherRecord?->primary_email
                    ?? $ownOrder?->applicant_email
                    ?? $firstOrder?->applicant_email;
            }

            // Orphaned bucket has no real recipient — null the email so the UI
            // can hide the Copy Email / Open Gmail buttons rather than prefill
            // a junk draft addressed to Paul himself.
            if ($teacherName === 'Parent Bookings (no teacher assigned)') {
                $teacherEmail = null;
            }

            // Certificate breakdown
            $distinctions = $withScores->filter(fn ($e) => $e->score >= 87)->count();
            $merits = $withScores->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();
            $passes = $withScores->filter(fn ($e) => $e->score >= 60 && $e->score < 75)->count();

            // Badges reset per-quarter — count only non-cancelled entries in the
            // selected quarter. A teacher who earned Gold in Q1 but has 3 in Q2
            // doesn't get a Q2 badge.
            $quarterCount = $entries->count();

            $badgeTier = match (true) {
                $quarterCount >= 40 => 'Top Award',
                $quarterCount >= 30 => 'Gold',
                $quarterCount >= 20 => 'Silver',
                $quarterCount >= 10 => 'Bronze',
                default => null,
            };

            return [
                'teacher_name' => $teacherName,
                'applicant_email' => $teacherEmail,
                'applicant_name' => $firstOrder?->applicant_name,
                'is_parent_booking' => $isParentBooking,
                'booking_role' => $bookingRole,
                'total_entries' => $entries->count(),
                'with_results' => $withScores->count(),
                'pending' => $pending->count(),
                'distinctions' => $distinctions,
                'merits' => $merits,
                'passes' => $passes,
                'badge_tier' => $badgeTier,
                'total_all_time' => $quarterCount, // kept for prop-compat; now means quarter count
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

        // Top scorers — only calculated when NO results are pending
        $hasPending = $allEntries->contains(fn ($e) => $e->score === null);
        $topDistinction = null;
        $topMerit = null;

        if (! $hasPending) {
            $withScores = $allEntries->filter(fn ($e) => $e->score !== null);

            // Showstopper — highest Distinction (87+)
            $topDistinction = $withScores->filter(fn ($e) => $e->score >= 87)->sortByDesc('score')->first();

            // Centre Stage — highest Merit (75–86)
            $topMerit = $withScores->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->sortByDesc('score')->first();
        }

        // --- PRIZE DRAW DATA ---
        // Student draw: every entry = one ticket (all students eligible)
        $studentTickets = $allEntries->map(fn ($e) => [
            'name' => $e->candidate_name ?? ($e->student ? "{$e->student->first_name} {$e->student->last_name}" : 'Unknown'),
            'instrument' => $e->instrument?->name ?? 'Unknown',
            'grade' => $e->grade,
            'teacher' => $e->teacher_name ?? 'Unknown',
        ])->values()->toArray();

        // Teacher draw: get registered teachers from users table
        $registeredTeacherNames = ExamContact::withType('teacher')
            ->get()
            ->map(fn ($c) => strtolower(trim($c->name)))
            ->toArray();

        // Build teacher eligibility from teacher_name (curated field, not order applicant)
        $applicantEntries = $allEntries
            ->filter(fn ($e) => $e->teacher_name !== null)
            ->groupBy(fn ($e) => $e->teacher_name);

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

        // Check if real draws have already been run for this quarter
        $existingDraws = PrizeDraw::where('quarter', $quarter)
            ->where('year', $year)
            ->get()
            ->keyBy('type');

        // Email tracking — which teachers have been marked as sent
        $emailTracking = DB::table('quarter_end_email_tracking')
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->where('email_sent', true)
            ->pluck('teacher_name')
            ->toArray();

        // Persistent batch result — scan the storage dir for existing ZIPs so
        // download links survive navigation and Inertia flash consumption.
        // Flash data is still used for the just-generated feedback, but this
        // ensures users coming back to Step 1 always see the downloads.
        $persistedBatchResult = null;
        $zipDir = "certificates/{$year}-Q{$quarter}/zips";
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($zipDir)) {
            $files = \Illuminate\Support\Facades\Storage::disk('local')->files($zipDir);
            if (! empty($files)) {
                // Infer teacher name from the filename pattern `Teacher_Name_Q1_2026.zip`
                $downloadLinks = [];
                foreach ($files as $file) {
                    $basename = basename($file);
                    $teacherKey = preg_replace('/_Q\d_\d{4}\.zip$/', '', $basename);
                    $teacherName = str_replace('_', ' ', $teacherKey);
                    $downloadLinks[$teacherName] = $file;
                }
                $masterZip = "certificates/{$year}-Q{$quarter}/ALL_Q{$quarter}_{$year}_Certificates.zip";
                $masterExists = \Illuminate\Support\Facades\Storage::disk('local')->exists($masterZip);

                $persistedBatchResult = [
                    'total' => null, // count unknown without running the batch
                    'quarter_label' => $quarterLabel,
                    'teachers' => [],
                    'download_links' => $downloadLinks,
                    'master_zip' => $masterExists ? $masterZip : null,
                    'from_disk' => true, // flag so UI can show "previously generated" vs "just generated"
                ];
            }
        }

        return Inertia::render('admin/QuarterEnd/Index', [
            'quarter' => $quarter,
            'year' => $year,
            'quarterLabel' => $quarterLabel,
            'teachers' => $teachers,
            'emailsSent' => $emailTracking,
            'existingDraws' => [
                'student' => $existingDraws->get('student')?->only(['winner_name', 'winner_instrument', 'winner_grade', 'winner_teacher', 'total_tickets', 'created_at']),
                'teacher' => $existingDraws->get('teacher')?->only(['winner_name', 'winner_entries', 'total_tickets', 'created_at']),
            ],
            'summary' => [
                'total_entries' => $totalEntries,
                'with_results' => $totalWithResults,
                'pending' => $totalPending,
                'total_fees' => number_format($totalFees, 2),
                'teacher_count' => $teacherGroups->count(),
                'has_pending' => $hasPending,
                'showstopper' => $topDistinction ? [
                    'name' => $this->shortName($topDistinction->candidate_name),
                    'full_name' => $topDistinction->candidate_name,
                    'score' => $topDistinction->score,
                    'instrument' => $topDistinction->instrument?->name,
                ] : null,
                'centre_stage' => $topMerit ? [
                    'name' => $this->shortName($topMerit->candidate_name),
                    'full_name' => $topMerit->candidate_name,
                    'score' => $topMerit->score,
                    'instrument' => $topMerit->instrument?->name,
                ] : null,
            ],
            'prizeDraw' => [
                'student_tickets' => $studentTickets,
                'teacher_tickets' => $teacherTickets,
                'eligible_teachers' => $eligibleTeachers,
                'student_ticket_count' => count($studentTickets),
                'teacher_ticket_count' => count($teacherTickets),
            ],
            'persistedBatchResult' => $persistedBatchResult,
        ]);
    }

    /**
     * Run a prize draw (AJAX) — picks a random winner from the ticket pool.
     * mode = 'test' (practice, not saved) or 'real' (saved to DB, one-shot)
     */
    public function runDraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:student,teacher',
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer',
            'mode' => 'required|in:test,real',
        ]);

        $isReal = $validated['mode'] === 'real';

        // Check if real draw already exists
        if ($isReal) {
            $existing = PrizeDraw::where('type', $validated['type'])
                ->where('quarter', $validated['quarter'])
                ->where('year', $validated['year'])
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => "The real {$validated['type']} draw for Q{$validated['quarter']} {$validated['year']} has already been run. Winner: {$existing->winner_name}",
                ], 422);
            }
        }

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
            $winnerData = [
                'name' => $winner->candidate_name ?? ($winner->student ? "{$winner->student->first_name} {$winner->student->last_name}" : 'Unknown'),
                'instrument' => $winner->instrument?->name ?? 'Unknown',
                'grade' => $winner->grade,
                'teacher' => $winner->teacher_name ?? 'Unknown',
            ];

            if ($isReal) {
                PrizeDraw::create([
                    'type' => 'student',
                    'quarter' => $quarter,
                    'year' => $year,
                    'winner_name' => $winnerData['name'],
                    'winner_instrument' => $winnerData['instrument'],
                    'winner_grade' => $winnerData['grade'],
                    'winner_teacher' => $winnerData['teacher'],
                    'total_tickets' => $allEntries->count(),
                    'all_eligible' => $allEntries->map(fn ($e) => [
                        'name' => $e->candidate_name,
                        'instrument' => $e->instrument?->name,
                        'grade' => $e->grade,
                    ])->values()->toArray(),
                    'drawn_by' => $request->user()->id,
                ]);
            }

            return response()->json([
                'type' => 'student',
                'mode' => $validated['mode'],
                'winner' => $winnerData,
                'total_tickets' => $allEntries->count(),
            ]);
        }

        // Teacher draw
        $registeredTeacherNames = ExamContact::withType('teacher')
            ->get()
            ->map(fn ($c) => strtolower(trim($c->name)))
            ->toArray();

        // Exclude contacts explicitly flagged as parent/candidate from the
        // teacher draw. Multi-type contacts (e.g. Alexandra Bibby is
        // teacher AND parent) stay eligible because they have the teacher
        // type — we only exclude pure parents/candidates.
        $pureNonTeachers = ExamContact::withType(['parent', 'candidate'])
            ->get()
            ->reject(fn ($c) => $c->isTeacher());

        $excludedContactIds = $pureNonTeachers->pluck('id')->all();

        $excludedNamesLower = $pureNonTeachers
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->filter()
            ->unique()
            ->all();

        $applicantEntries = $allEntries
            ->filter(fn ($e) => $e->teacher_name !== null)
            ->filter(fn ($e) => ! in_array($e->teacher_contact_id, $excludedContactIds, true))
            ->filter(fn ($e) => ! in_array(strtolower(trim($e->teacher_name)), $excludedNamesLower, true))
            ->groupBy(fn ($e) => $e->teacher_name);

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

        if ($isReal) {
            // Build eligible list snapshot for audit
            $eligibleSnapshot = [];
            foreach ($applicantEntries as $name => $entries) {
                $count = $entries->count();
                $reg = in_array(strtolower(trim($name)), $registeredTeacherNames);
                if ($reg || $count >= 2) {
                    $eligibleSnapshot[] = ['name' => $name, 'entries' => $count, 'registered' => $reg];
                }
            }

            PrizeDraw::create([
                'type' => 'teacher',
                'quarter' => $quarter,
                'year' => $year,
                'winner_name' => $winnerName,
                'winner_entries' => $winnerEntries,
                'total_tickets' => count($tickets),
                'all_eligible' => $eligibleSnapshot,
                'drawn_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'type' => 'teacher',
            'mode' => $validated['mode'],
            'winner' => [
                'name' => $winnerName,
                'entries' => $winnerEntries,
                'is_registered' => $isRegistered,
            ],
            'total_tickets' => count($tickets),
        ]);
    }

    /**
     * Toggle email sent status for a teacher (AJAX).
     */
    public function markSent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_name' => 'required|string',
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer',
            'sent' => 'required|boolean',
        ]);

        DB::table('quarter_end_email_tracking')->updateOrInsert(
            [
                'teacher_name' => $validated['teacher_name'],
                'quarter' => $validated['quarter'],
                'year' => $validated['year'],
            ],
            [
                'email_sent' => $validated['sent'],
                'sent_at' => $validated['sent'] ? now() : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Convert full name to "First L" format (e.g. "Seth James Barraclough" → "James B").
     * Uses the second name if available (first name is often a formal/unused name),
     * otherwise falls back to the first name.
     */
    private function shortName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));

        if (count($parts) < 2) {
            return $fullName;
        }

        $surname = array_pop($parts);
        // Use the last "first name" — often the name they actually go by
        $firstName = end($parts);

        return $firstName.' '.strtoupper($surname[0]);
    }
}
