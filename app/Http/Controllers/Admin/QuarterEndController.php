<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\PrizeDraw;
use App\Models\TopScorerPublication;
use App\Models\TopScorerWorkflow;
use App\Support\TopScorers;
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

        // School-admin rollup (Phase 2): a school_admin entry credits the
        // SCHOOL it's linked to, not the individual admin — so Learn Music
        // Ltd is the entrant, Emily Bates' private-teacher entries stay hers.
        // When no school_admin-with-school entries exist these maps are empty
        // and every credit name falls straight through to teacher_name, so the
        // existing teacher behaviour is byte-for-byte unchanged.
        [$schoolNameByContactId, $schoolMetaByNameLower] = $this->schoolCreditMaps();
        $creditName = fn ($e) => $this->creditNameFor($e, $schoolNameByContactId);

        // Parents and self-bookers stamped as teacher_name during import need
        // the same Copy Email + Open Gmail workflow as teachers — they just
        // get a parent-variant template. Build a lookup so we can tag each row
        // with is_parent_booking and fetch the correct email from ExamContact.
        // Post Phase D-3: 'self' was folded into 'candidate' on the unified model.
        $parentOrSelfLookup = ExamContact::with('emails')
            ->withType(['parent', 'candidate'])
            ->get()
            ->keyBy(fn ($c) => strtolower(trim($c->name)));

        // Separate lookup for teacher contacts so the Copy Email / Open
        // Gmail buttons can route to the *teacher's* email — not Paul's
        // applicant_email on the order. The previous fallback chain
        // (parent_or_self → order.applicant_email) sent every teacher-
        // booked email back to the operator who submitted the booking
        // to Trinity, because applicant_email captures Paul as the
        // booker even when teacher_name correctly identifies the
        // teacher. This lookup gets us the teacher's actual email.
        $teacherLookup = ExamContact::with('emails')
            ->withType(['teacher', 'school_admin'])
            ->get()
            ->keyBy(fn ($c) => strtolower(trim($c->name)));

        // Group by teacher/parent name — each individual parent becomes their
        // own row (not lumped), so Paul can email each directly. Rows with no
        // teacher_name at all (NULL or empty/whitespace string) stay in the
        // catch-all bucket — empty strings used to slip through and create
        // a phantom blank-name card with Paul's applicant_email attached.
        $teacherGroups = $allEntries->groupBy(function ($e) use ($creditName) {
            $name = trim((string) ($creditName($e) ?? ''));
            return $name === '' ? 'Parent Bookings (no teacher assigned)' : $creditName($e);
        });

        $teachers = $teacherGroups->map(function ($entries, $teacherName) use ($parentOrSelfLookup, $schoolMetaByNameLower) {
            // Is this group a SCHOOL (school_admin entries rolled up)? If so the
            // badge + email belong to the school, not an individual teacher.
            $schoolMeta = $schoolMetaByNameLower[strtolower(trim((string) $teacherName))] ?? null;
            $isSchool = $schoolMeta !== null;
            $withScores = $entries->filter(fn ($e) => $e->score !== null && $e->score >= 60);
            // "Pending" = unscored entries we're still waiting on. NO_SHOW
            // entries also have a null score but are NOT pending — Trinity
            // won't issue a result for them. They stay in $entries (so they
            // still count toward the teacher's volume tally) but get
            // excluded here so the "X results pending" banner is accurate.
            $pending = $entries->filter(
                fn ($e) => $e->score === null && ! in_array($e->notes, ExamEntry::NOTES_NO_RESULT, true)
            );

            // Is this row a parent/self booking?
            //
            // Per-entry override wins. exam_entries.booking_role is set
            // per-row when the system needs to reflect a fact that the
            // contact-type lookup can't infer — e.g. multi-type contacts
            // like Alexandra Bibby (teacher + parent), where Sam Williamson
            // is her STUDENT not her son. Set booking_role = 'teacher' on
            // his entry and we use the teacher voice; if she enters her
            // own kid, set 'parent' on that entry. Default NULL means
            // "use contact-type inference" — works for the typical case.
            $parentContact = $parentOrSelfLookup->get(strtolower(trim($teacherName)));
            $entryRoles = $entries->pluck('booking_role')->filter()->unique();
            $explicitRole = $entryRoles->count() === 1 ? $entryRoles->first() : null;
            $contactInferredRole = match (true) {
                $parentContact === null => null,
                $parentContact->isParent() => 'parent',
                $parentContact->isCandidate() => 'self',
                default => null,
            };
            $bookingRole = $explicitRole ?? $contactInferredRole;
            $isParentBooking = $bookingRole === 'parent' || $bookingRole === 'self';

            // Get email — different strategy for parents vs teachers.
            $firstOrder = $entries->first()?->order;
            $ownOrder = $entries->first(fn ($e) => $e->order && strtolower(trim($e->order->applicant_name ?? '')) === strtolower(trim($teacherName)))?->order;

            if ($isParentBooking) {
                // Parents: use their ExamContact email; fall back to their
                // own-order applicant_email if they actually applied themselves.
                $teacherEmail = $parentContact->primary_email ?? $ownOrder?->applicant_email;
            } else {
                $teacherRecord = ExamContact::with('emails')
                    ->whereRaw('LOWER(name) = ?', [strtolower($teacherName)])
                    ->first();
                $teacherEmail = $teacherRecord?->primary_email
                    ?? $ownOrder?->applicant_email
                    ?? $firstOrder?->applicant_email;
            }

            // School groups route to the school's own email / its admin, since
            // a contact lookup by the school's NAME wouldn't match a person.
            if ($isSchool) {
                $teacherEmail = $schoolMeta['email'] ?? $teacherEmail;
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
                'is_school' => $isSchool,
                'booking_role' => $isSchool ? 'school_admin' : $bookingRole,
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

        // Summary stats. "Pending" excludes NO_SHOW + CANCELLED — Trinity
        // won't issue a result for those, so they're not a straggler we
        // need to wait on. They DO still count in $totalEntries / volume.
        $isStillPending = fn ($e) => $e->score === null
            && ! in_array($e->notes, ExamEntry::NOTES_NO_RESULT, true);

        $totalEntries = $allEntries->count();
        $totalWithResults = $allEntries->filter(fn ($e) => $e->score !== null && $e->score >= 60)->count();
        $totalPending = $allEntries->filter($isStillPending)->count();
        $totalFees = $allEntries->sum('fee');

        // Top scorers — normally only calculated when NO results are pending,
        // but Paul can override this with `?finalise=1` to lock in the awards
        // based on whatever scores ARE in (used when the last few stragglers
        // are very unlikely to top the current leaders, and Paul's happy to
        // honour any after-the-fact tie by topping up the gift token).
        //
        // Banner promises FOUR awards each quarter:
        //   • Highest Distinction — Initial-5
        //   • Highest Distinction — 6-8
        //   • Highest Merit       — Initial-5
        //   • Highest Merit       — 6-8
        //
        // Ties are split (Paul's rule: £20 / 2 = £10 each, £20 / 3+ = £5 each).
        // We return ALL candidates tied at the top score per (group, band) so
        // the front-end can show every winner.
        $hasPending = $allEntries->contains($isStillPending);
        $finalise = $request->boolean('finalise');
        $topDistinction = null;   // legacy single-winner field — overall top Distinction
        $topMerit = null;         // legacy single-winner field — overall top Merit
        $topScorers = [
            'initial_5' => ['distinction' => [], 'merit' => []],
            '6_8'       => ['distinction' => [], 'merit' => []],
        ];

        if (! $hasPending || $finalise) {
            $withScores = $allEntries->filter(fn ($e) => $e->score !== null);

            // Map each candidate into a normalised winner row. The teacher_*
            // fields drive the per-winner "Copy Top Scorer Email" button —
            // they need to know who to email and whether it's a parent-
            // direct booking (different template tone).
            $shapeWinner = function ($e) use ($parentOrSelfLookup, $teacherLookup) {
                $teacherName = $e->teacher_name;
                $teacherKey = $teacherName ? strtolower(trim($teacherName)) : null;
                $parentContact = $teacherKey
                    ? $parentOrSelfLookup->get($teacherKey)
                    : null;

                // Resolve the teacher contact: prefer the explicit FK
                // (exam_entries.teacher_contact_id) so we tolerate any
                // teacher_name string drift, then fall back to a name
                // lookup for older rows that pre-date the FK.
                $teacherContact = null;
                if ($e->teacher_contact_id) {
                    $teacherContact = ExamContact::with('emails')->find($e->teacher_contact_id);
                }
                if (! $teacherContact && $teacherKey) {
                    $teacherContact = $teacherLookup->get($teacherKey);
                }

                // Per-entry override wins (see index() for full reasoning):
                // exam_entries.booking_role lets a row explicitly declare
                // teacher / parent / self for cases where the contact-type
                // lookup can't infer correctly (multi-type contacts).
                $contactInferredRole = match (true) {
                    $parentContact === null => null,
                    $parentContact->isParent() => 'parent',
                    $parentContact->isCandidate() => 'self',
                    default => null,
                };
                $bookingRole = $e->booking_role ?: $contactInferredRole;
                $useParentEmail = $bookingRole === 'parent' || $bookingRole === 'self';
                // Recipient priority depends on the resolved booking role:
                //   - Parent/self voice → parent contact's email.
                //   - Teacher voice    → teacher contact's email (looked
                //                         up via teacher_contact_id FK
                //                         first, then by name fallback).
                //   - Last resort      → order.applicant_email (usually
                //                         Paul as centre operator on F2F).
                $teacherEmail = ($useParentEmail ? $parentContact?->emails->first()?->email : null)
                    ?? ($useParentEmail ? $parentContact?->email : null)
                    ?? $teacherContact?->emails->first()?->email
                    ?? $teacherContact?->email
                    ?? $e->order?->applicant_email;

                return [
                    'name'              => $this->shortName($e->candidate_name),
                    'full_name'         => $e->candidate_name,
                    'score'             => $e->score,
                    'instrument'        => $e->instrument?->name,
                    'grade'             => $e->grade,
                    'teacher_name'      => $teacherName,
                    'teacher_email'     => $teacherEmail,
                    'is_parent_booking' => $useParentEmail,
                    'booking_role'      => $bookingRole,
                ];
            };

            $topScorers = TopScorers::calculate($withScores, $shapeWinner);

            // Overall top Distinction / Merit across both groups, returning
            // ALL candidates tied at the top score. Drives the trophy stat
            // tile — when more than one candidate scored e.g. 93, the tile
            // needs to list all the names, not silently pick the first one
            // alphabetically.
            $distinctions = $withScores->filter(fn ($e) => $e->score >= 87)->sortByDesc('score');
            $topDistinctionScore = $distinctions->first()?->score;
            $topDistinctionWinners = $topDistinctionScore !== null
                ? $distinctions->filter(fn ($e) => $e->score === $topDistinctionScore)->values()
                : collect();

            $merits = $withScores->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->sortByDesc('score');
            $topMeritScore = $merits->first()?->score;
            $topMeritWinners = $topMeritScore !== null
                ? $merits->filter(fn ($e) => $e->score === $topMeritScore)->values()
                : collect();
        } else {
            $topDistinctionWinners = collect();
            $topMeritWinners = collect();
        }

        // --- PRIZE DRAW DATA ---
        // Student draw: every entry = one ticket (all students eligible)
        $studentTickets = $allEntries->map(fn ($e) => [
            'name' => $e->candidate_name ?? ($e->student ? "{$e->student->first_name} {$e->student->last_name}" : 'Unknown'),
            'instrument' => $e->instrument?->name ?? 'Unknown',
            'grade' => $e->grade,
            'teacher' => $creditName($e) ?? 'Unknown',
        ])->values()->toArray();

        // School credit names (lowercased) are eligible like registered
        // teachers — a real school always gets a ticket per rolled-up entry.
        $schoolCreditNamesLower = array_keys($schoolMetaByNameLower);

        // Teacher draw eligibility — built from `exam_entries.teacher_name`
        // (the curated string, not order applicant) but cross-checked against
        // the unified contacts model so non-teachers can't slip in via the
        // ≥2-entry heuristic.
        $registeredTeacherNames = ExamContact::withType('teacher')
            ->get()
            ->map(fn ($c) => strtolower(trim($c->name)))
            ->toArray();

        // Names of contacts who are pure parents or self-applicants — these
        // must NEVER win prize-draw tickets even if they appear on multiple
        // entries (a parent with two kids in the same quarter would otherwise
        // qualify under the ≥2-entry heuristic). School admins like Daniel
        // Rogers are NOT excluded — they represent their school's teachers
        // and are eligible to win on behalf of the school.
        $knownNonTeacherNames = ExamContact::withType(['parent', 'candidate'])
            ->get()
            ->reject(fn ($c) => $c->isTeacher() || $c->hasType('school_admin'))
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->toArray();

        // Operator-self-exclusion: any contact flagged with
        // excluded_from_prize_draw is suppressed from BOTH draws (student
        // ticket pool and teacher eligibility list). Used so the centre
        // operator (Paul Sheridan) doesn't win their own draw.
        $selfExcludedNames = ExamContact::query()
            ->where('excluded_from_prize_draw', true)
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->toArray();

        // Build teacher eligibility from teacher_name. Two filter layers
        // beyond `teacher_name !== null`:
        //   - Reject empty/whitespace-only names (otherwise they group
        //     under a blank key and surface as a "blank applicant" row).
        //   - Drop NO_SHOW entries — the candidate didn't take the exam,
        //     so no draw ticket. (NO_SHOW still flows into $allEntries
        //     because line 44 only filters CANCELLED — that's deliberate
        //     so teacher VOLUME tallies still count NO_SHOW.)
        $applicantEntries = $allEntries
            ->filter(fn ($e) => $creditName($e) !== null && trim((string) $creditName($e)) !== '')
            ->reject(fn ($e) => $e->notes === ExamEntry::NOTE_NO_SHOW)
            ->reject(fn ($e) => in_array(strtolower(trim((string) $creditName($e))), $selfExcludedNames, true))
            ->groupBy(fn ($e) => $creditName($e));

        $teacherTickets = [];
        foreach ($applicantEntries as $applicantName => $entries) {
            $entryCount = $entries->count();
            $nameKey = strtolower(trim($applicantName));
            $isSchool = in_array($nameKey, $schoolCreditNamesLower, true);
            $isRegistered = $isSchool || in_array($nameKey, $registeredTeacherNames);
            $isKnownNonTeacher = in_array($nameKey, $knownNonTeacherNames);

            // Eligibility:
            //  - School (rolled up) → always in
            //  - Registered teacher  → always in
            //  - Known non-teacher   → always out (parent, candidate)
            //  - Unknown name with ≥2 entries → in (the catch-all heuristic
            //    for teachers who haven't been formally added yet)
            //  - Unknown name with 1 entry → out (almost always a parent)
            $eligible = $isRegistered
                || (! $isKnownNonTeacher && $entryCount >= 2);

            if ($eligible) {
                for ($i = 0; $i < $entryCount; $i++) {
                    $teacherTickets[] = [
                        'name' => $applicantName,
                        'entries' => $entryCount,
                        'is_registered' => $isRegistered,
                        'is_school' => $isSchool,
                    ];
                }
            }
        }

        // Unique eligible teachers for display — same eligibility rules as
        // the ticket loop above so the table matches the actual draw.
        $eligibleTeachers = collect($applicantEntries)->map(function ($entries, $name) use ($registeredTeacherNames, $knownNonTeacherNames, $schoolCreditNamesLower) {
            $count = $entries->count();
            $nameKey = strtolower(trim($name));
            $isSchool = in_array($nameKey, $schoolCreditNamesLower, true);
            $isRegistered = $isSchool || in_array($nameKey, $registeredTeacherNames);
            $isKnownNonTeacher = in_array($nameKey, $knownNonTeacherNames);
            $eligible = $isRegistered || (! $isKnownNonTeacher && $count >= 2);

            $reason = match (true) {
                $isSchool          => 'School (entries roll up here)',
                $isRegistered      => 'Registered teacher',
                $isKnownNonTeacher => 'Excluded — '.($this->nonTeacherType($nameKey) ?? 'non-teacher contact'),
                $count >= 2        => "{$count} entries",
                default            => 'Only 1 entry (likely parent)',
            };

            return [
                'name' => $name,
                'is_school' => $isSchool,
                'entries' => $count,
                'is_registered' => $isRegistered,
                'eligible' => $eligible,
                'reason' => $reason,
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

        // Per-winner workflow checkboxes (Bought / Sent / Cert) for the
        // top-scorer awards. Keyed `award_key|winner_full_name` so the Vue
        // side can look up status for each tied winner independently.
        $winnerWorkflow = TopScorerWorkflow::where('quarter', $quarter)
            ->where('year', $year)
            ->get()
            ->mapWithKeys(fn ($r) => [
                "{$r->award_key}|{$r->winner_full_name}" => [
                    'bought' => $r->bought,
                    'sent'   => $r->sent,
                    'cert'   => $r->cert,
                ],
            ])
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
                // showstopper / centre_stage now return ALL candidates tied
                // at the top score so the stat tile can list every name
                // when there's a tie. The legacy single-row fields kept
                // their shape for the FIRST winner (back-compat) — Vue
                // checks `winners` length to decide rendering.
                'showstopper' => $topDistinctionWinners->isNotEmpty() ? [
                    'name' => $this->shortName($topDistinctionWinners->first()->candidate_name),
                    'full_name' => $topDistinctionWinners->first()->candidate_name,
                    'score' => $topDistinctionWinners->first()->score,
                    'instrument' => $topDistinctionWinners->first()->instrument?->name,
                    'winners' => $topDistinctionWinners->map(fn ($e) => [
                        'name' => $this->shortName($e->candidate_name),
                        'full_name' => $e->candidate_name,
                        'instrument' => $e->instrument?->name,
                        'grade' => $e->grade,
                    ])->all(),
                ] : null,
                'centre_stage' => $topMeritWinners->isNotEmpty() ? [
                    'name' => $this->shortName($topMeritWinners->first()->candidate_name),
                    'full_name' => $topMeritWinners->first()->candidate_name,
                    'score' => $topMeritWinners->first()->score,
                    'instrument' => $topMeritWinners->first()->instrument?->name,
                    'winners' => $topMeritWinners->map(fn ($e) => [
                        'name' => $this->shortName($e->candidate_name),
                        'full_name' => $e->candidate_name,
                        'instrument' => $e->instrument?->name,
                        'grade' => $e->grade,
                    ])->all(),
                ] : null,
                // Per-group winners (Initial-5 vs 6-8) — matches the Awards
                // banner on the public site. Each leaf is an array of tied
                // winners (empty array when nobody hit that band in that group).
                'top_scorers' => $topScorers,
                // True when the awards have been computed despite pending
                // results (i.e. Paul clicked "Preview leaders so far").
                // Drives the provisional banner in Step 3.
                'finalised_with_pending' => $finalise && $hasPending,
                // Snapshot of an existing publication, if any. Drives the
                // "Already published on X" indicator + disables the Publish
                // button to prevent accidental double-publish.
                'publication' => TopScorerPublication::forQuarter($quarter, $year)?->only([
                    'published_at',
                    'finalised_with_pending',
                    'pending_count',
                ]),
            ],
            'prizeDraw' => [
                'student_tickets' => $studentTickets,
                'teacher_tickets' => $teacherTickets,
                'eligible_teachers' => $eligibleTeachers,
                'student_ticket_count' => count($studentTickets),
                'teacher_ticket_count' => count($teacherTickets),
            ],
            'persistedBatchResult' => $persistedBatchResult,
            'winnerWorkflow' => $winnerWorkflow,
        ]);
    }

    /**
     * Toggle a single workflow step for a single top-scorer winner.
     * Called from /admin/quarter-end checkboxes (Bought / Sent / Cert).
     *
     * Tied winners are tracked separately because the table key includes
     * winner_full_name — Anna and Maya each have their own row even when
     * they share the same award_key.
     */
    public function toggleWorkflow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quarter'          => 'required|integer|min:1|max:4',
            'year'             => 'required|integer|min:2025|max:2030',
            'award_key'        => 'required|string|in:'.implode(',', TopScorerWorkflow::AWARD_KEYS),
            'winner_full_name' => 'required|string|max:255',
            'step'             => 'required|string|in:'.implode(',', TopScorerWorkflow::STEPS),
            'value'            => 'required|boolean',
        ]);

        $record = TopScorerWorkflow::firstOrNew([
            'quarter'          => $validated['quarter'],
            'year'             => $validated['year'],
            'award_key'        => $validated['award_key'],
            'winner_full_name' => $validated['winner_full_name'],
        ]);

        $record->{$validated['step']} = $validated['value'];
        $record->updated_by = $request->user()->id;
        $record->save();

        return response()->json([
            'success' => true,
            'status' => [
                'bought' => $record->bought,
                'sent'   => $record->sent,
                'cert'   => $record->cert,
            ],
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

        // Strict filter for the prize draw pool — NO_SHOW entries don't
        // earn a ticket. The candidate didn't take the exam; the teacher's
        // VOLUME tally still credits them elsewhere, but draw eligibility
        // is per-exam-actually-taken.
        $allEntries = ExamEntry::with(['instrument:id,name', 'order:id,requested_start_date,applicant_name', 'student:id,first_name,last_name'])
            ->whereResultPossible()
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
        // School-admin rollup: credit the school, and treat school names as
        // registered (always eligible). Empty when no school entries exist,
        // so the plain-teacher path is unchanged.
        [$schoolNameByContactId, $schoolMetaByNameLower] = $this->schoolCreditMaps();
        $creditName = fn ($e) => $this->creditNameFor($e, $schoolNameByContactId);
        $schoolCreditNamesLower = array_keys($schoolMetaByNameLower);

        $registeredTeacherNames = ExamContact::withType('teacher')
            ->get()
            ->map(fn ($c) => strtolower(trim($c->name)))
            ->toArray();

        // Exclude pure parents and self-applicants from the teacher draw.
        // School admins (Daniel Rogers / Pulse Music) STAY eligible —
        // they represent their school's teaching staff and stand in for
        // the teachers there. Multi-type contacts who hold 'teacher' (e.g.
        // Alexandra Bibby = teacher + parent) also stay eligible.
        $pureNonTeachers = ExamContact::withType(['parent', 'candidate'])
            ->get()
            ->reject(fn ($c) => $c->isTeacher() || $c->hasType('school_admin'));

        $excludedContactIds = $pureNonTeachers->pluck('id')->all();

        $excludedNamesLower = $pureNonTeachers
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->filter()
            ->unique()
            ->all();

        // Operator-self-exclusion: the centre operator (Paul Sheridan)
        // doesn't enter their own draws even if they have students entered
        // through centre 120. Toggled per-contact via excluded_from_prize_draw.
        $selfExcludedNames = ExamContact::query()
            ->where('excluded_from_prize_draw', true)
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->toArray();

        $applicantEntries = $allEntries
            ->filter(fn ($e) => $creditName($e) !== null && trim((string) $creditName($e)) !== '')
            ->filter(fn ($e) => ! in_array($e->teacher_contact_id, $excludedContactIds, true))
            ->filter(fn ($e) => ! in_array(strtolower(trim((string) $creditName($e))), $excludedNamesLower, true))
            ->reject(fn ($e) => in_array(strtolower(trim((string) $creditName($e))), $selfExcludedNames, true))
            ->groupBy(fn ($e) => $creditName($e));

        $tickets = [];
        foreach ($applicantEntries as $applicantName => $entries) {
            $entryCount = $entries->count();
            $nameKey = strtolower(trim($applicantName));
            $isRegistered = in_array($nameKey, $schoolCreditNamesLower, true)
                || in_array($nameKey, $registeredTeacherNames);

            // Same eligibility logic as the index() display so the draw
            // matches what the admin sees in the eligible list.
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
        $winnerKey = strtolower(trim($winnerName));
        $isRegistered = in_array($winnerKey, $schoolCreditNamesLower, true)
            || in_array($winnerKey, $registeredTeacherNames);

        if ($isReal) {
            // Build eligible list snapshot for audit
            $eligibleSnapshot = [];
            foreach ($applicantEntries as $name => $entries) {
                $count = $entries->count();
                $nk = strtolower(trim($name));
                $reg = in_array($nk, $schoolCreditNamesLower, true) || in_array($nk, $registeredTeacherNames);
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
     * Publish the four top-scorer awards for a quarter.
     *
     * Snapshots the current winners into `top_scorer_publications` so the
     * public Recognition page can display them. Once published, the
     * snapshot is stable — a late-arriving higher score won't shuffle the
     * leaderboard. Paul can re-publish to refresh, which overwrites the
     * snapshot.
     *
     * Bypasses the "all results in" gate by design — Paul presses this
     * when he's accepted that any pending results either won't change the
     * outcome or will be honoured by topping up the gift token.
     */
    public function publishTopScorers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quarter' => 'required|integer|min:1|max:4',
            'year' => 'required|integer|min:2025|max:2030',
        ]);

        $quarter = $validated['quarter'];
        $year = $validated['year'];

        // Re-run the same calculation the index() page uses so the
        // snapshot matches what Paul saw when he clicked Publish.
        $startMonth = (($quarter - 1) * 3) + 1;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();

        $allEntries = ExamEntry::with([
                'instrument:id,name',
                'order:id,requested_start_date,delivery_method,applicant_name,applicant_email',
                'student:id,first_name,last_name',
            ])
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get()
            ->filter(function ($entry) use ($startDate, $endDate) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && $date->between($startDate, $endDate);
            });

        // "Pending" = unscored entries we're still waiting on. Mirrors the
        // index() rule: NO_SHOW + CANCELLED are excluded — they've taken
        // themselves out of the picture even though score is null. Used
        // here so the publish snapshot's `finalised_with_pending` flag
        // is accurate (won't say "published despite stragglers" when the
        // remaining nulls are just NO_SHOWs).
        $pendingCount = $allEntries->filter(
            fn ($e) => $e->score === null && ! in_array($e->notes, ExamEntry::NOTES_NO_RESULT, true)
        )->count();
        $withScores = $allEntries->filter(fn ($e) => $e->score !== null);

        $parentOrSelfLookup = ExamContact::with('emails')
            ->withType(['parent', 'candidate'])
            ->get()
            ->keyBy(fn ($c) => strtolower(trim($c->name)));

        $shapeWinner = function ($e) use ($parentOrSelfLookup) {
            $teacherName = $e->teacher_name;
            $parentContact = $teacherName
                ? $parentOrSelfLookup->get(strtolower(trim($teacherName)))
                : null;
            return [
                'name'              => $this->shortName($e->candidate_name),
                'full_name'         => $e->candidate_name,
                'show_full_name'    => (bool) $e->show_full_name, // GDPR — public display
                'score'             => $e->score,
                'instrument'        => $e->instrument?->name,
                'grade'             => $e->grade,
                'teacher_name'      => $teacherName,
                'is_parent_booking' => $parentContact !== null,
            ];
        };

        $winners = TopScorers::calculate($withScores, $shapeWinner);

        $publication = TopScorerPublication::updateOrCreate(
            ['quarter' => $quarter, 'year' => $year],
            [
                'winners'                => $winners,
                'finalised_with_pending' => $pendingCount > 0,
                'pending_count'          => $pendingCount,
                'published_by'           => $request->user()->id,
                'published_at'           => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'published_at' => $publication->published_at->toIso8601String(),
            'pending_count' => $pendingCount,
            'winner_count' => count(TopScorers::flatten($winners)),
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
     * Convert full name to "First L" format (e.g. "Alice Jun Mei Khoo" → "Alice K").
     * Mirrors ThankYouController::displayName() so the admin UI and the public
     * Recognition page never disagree on a candidate's display label.
     */
    /**
     * Build the school-admin rollup maps (Phase 2).
     *
     * @return array{0: array<int,string>, 1: array<string,array{name:string,email:?string}>}
     *   [0] teacher_contact_id => school name (for crediting entries)
     *   [1] lowercased school name => ['name','email'] (display + routing)
     *
     * A school_admin contact's linked school is the entity its entries roll
     * up to in the draw and the volume badges. When a contact admins more
     * than one school we take the first (admins map to one school in practice
     * — Clare/Emily → Learn Music Ltd).
     */
    private function schoolCreditMaps(): array
    {
        $byContactId = [];
        $metaByNameLower = [];

        $admins = ExamContact::withType('school_admin')
            ->with(['schools:id,name,email', 'emails'])
            ->get();

        foreach ($admins as $admin) {
            $school = $admin->schools->first();
            if (! $school) {
                continue;
            }
            $byContactId[$admin->id] = $school->name;
            $key = strtolower(trim($school->name));
            if (! isset($metaByNameLower[$key])) {
                $metaByNameLower[$key] = [
                    'name' => $school->name,
                    'email' => $school->email ?: $admin->primary_email,
                ];
            }
        }

        return [$byContactId, $metaByNameLower];
    }

    /**
     * The name an entry is credited to for the draw + badges: the linked
     * school for a school_admin entry, otherwise the teacher_name string.
     *
     * @param  array<int,string>  $schoolNameByContactId
     */
    private function creditNameFor(ExamEntry $e, array $schoolNameByContactId): ?string
    {
        if ($e->booking_role === 'school_admin'
            && $e->teacher_contact_id
            && isset($schoolNameByContactId[$e->teacher_contact_id])) {
            return $schoolNameByContactId[$e->teacher_contact_id];
        }

        return $e->teacher_name;
    }

    private function shortName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));

        if (count($parts) < 2) {
            return $fullName;
        }

        $firstName = $parts[0];
        $surname = end($parts);
        $lastInitial = mb_strtoupper(mb_substr($surname, 0, 1));

        return "{$firstName} {$lastInitial}";
    }

    /**
     * Look up the contact_type(s) for a given non-teacher contact name —
     * used to label why they're excluded from the prize draw eligible list
     * ("Excluded — parent" / "Excluded — school admin" / etc).
     */
    private function nonTeacherType(string $nameKey): ?string
    {
        $contactId = ExamContact::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$nameKey])
            ->value('id');

        if (! $contactId) {
            return null;
        }

        $type = DB::table('contact_types')
            ->where('exam_contact_id', $contactId)
            ->orderByRaw("CASE type
                WHEN 'parent' THEN 1
                WHEN 'candidate' THEN 2
                WHEN 'school_admin' THEN 3
                ELSE 9 END")
            ->value('type');

        return match ($type) {
            'parent'       => 'parent',
            'candidate'    => 'self-applicant',
            'school_admin' => 'school admin',
            default        => $type,
        };
    }
}
