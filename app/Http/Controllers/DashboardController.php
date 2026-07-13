<?php

// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Mail\CorrectionRequestConfirmed;
use App\Mail\CorrectionRequestSubmitted;
use App\Mail\LinkRequestConfirmed;
use App\Mail\LinkRequestSubmitted;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\PrizeDraw;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Post-login dashboard for non-admin users (teachers, parents, self, school
 * admins). Admins still see this page but it just renders the existing admin
 * quick-links variant inside Dashboard.vue.
 *
 * For non-admins we look up their exam_entries by email — first via a matching
 * exam_contacts row (the canonical people system), falling back to direct
 * email match on exam_entries.applicant_email and exam_entries.teacher_contact
 * for users who registered before/without a contact link.
 *
 * If no entries match, we show a linkage form letting the user tell us which
 * email was used on their Trinity application; submitting it creates an admin
 * task on /admin/tasks for Paul to action.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Find a matching exam_contacts row (handles canonical email + the
        // legacy contact_emails relation for older imports).
        $contact = ExamContact::query()
            ->where('email', $user->email)
            ->orWhereHas('emails', fn ($q) => $q->where('email', $user->email))
            ->first();

        // Pull the user's exam entries. Three paths because the existing data
        // is messy: contact match → entries via teacher_contact_id; falling
        // back to applicant_email; finally entries where their email appears
        // anywhere on the entry.
        $entriesCollection = ExamEntry::query()
            ->select([
                'id', 'student_id', 'instrument_id', 'candidate_number', 'candidate_name', 'date_of_birth',
                'grade', 'subject_area', 'delivery_method',
                'result', 'score', 'exam_date', 'report',
            ])
            ->with('instrument:id,name')
            ->where(function ($q) use ($user, $contact) {
                $q->where('applicant_email', $user->email);
                if ($contact) {
                    $q->orWhere('teacher_contact_id', $contact->id);
                }
            })
            ->orderBy('candidate_name')
            ->orderByDesc('exam_date')
            ->get();

        // Build a map of entry_id → existing pending correction task so the
        // dashboard can show "Correction sent" indicators and let the user
        // re-read their submitted note. Title pattern is set in
        // correctionRequest() below: "Correction request: {name} (#{id})".
        $correctionMap = [];
        if ($entriesCollection->isNotEmpty()) {
            $entryIds = $entriesCollection->pluck('id')->toArray();

            $tasks = Task::query()
                ->where('category', 'admin')
                ->where('status', 'pending')
                ->where('title', 'ILIKE', 'Correction request%')
                ->get();

            foreach ($tasks as $task) {
                if (preg_match('/\(#(\d+)\)$/', (string) $task->title, $m)) {
                    $entryId = (int) $m[1];
                    if (! in_array($entryId, $entryIds, true)) {
                        continue;
                    }
                    // Pull just the user's note out of the task detail.
                    $note = (string) $task->detail;
                    if (str_contains($note, "User's note:\n")) {
                        $note = trim(explode("User's note:\n", $note, 2)[1] ?? '');
                    }
                    $correctionMap[$entryId] = [
                        'submitted_at' => $task->created_at?->format('d M Y, H:i'),
                        'note' => $note,
                    ];
                }
            }
        }

        $entries = $entriesCollection->map(fn (ExamEntry $e) => [
            'id' => $e->id,
            'student_id' => $e->student_id,
            'instrument' => $e->instrument?->name,
            'candidate_number' => $e->candidate_number,
            'candidate_name' => $e->candidate_name,
            'date_of_birth' => $e->date_of_birth?->format('d M Y'),
            'grade' => $e->grade,
            'subject_area' => $e->subject_area,
            'delivery_method' => $e->delivery_method,
            'result' => $e->result,
            'score' => $e->score,
            'exam_date' => $e->exam_date?->format('d M Y'),
            'pending_correction' => $correctionMap[$e->id] ?? null,
            // The deciphered F2F report (piece names, marks, examiner comments)
            // when this candidate's paper report has been scanned in. Null for
            // digital exams and anything not yet captured.
            'report' => $e->report,
        ]);

        return Inertia::render('Dashboard', [
            'examEntries' => $entries,
            'hasLinkedContact' => $contact !== null || $entries->isNotEmpty(),
            'teacherPrizeDraw' => $this->buildTeacherPrizeDrawPayload($contact),
        ]);
    }

    /**
     * Payload for the "Quarterly Teacher Prize Draw" card on the teacher
     * dashboard. Returns:
     *
     *   - quarters: one row per quarter that has either a real teacher
     *     PrizeDraw or is the current quarter (so we always at least show
     *     the current quarter as "not yet drawn"). Sorted newest first.
     *   - my_current_quarter_tickets: live count of the signed-in user's
     *     non-CANCELLED entries in the current quarter — climbs week by
     *     week as they enter more candidates, before any draw runs.
     *
     * Display rules for winner names follow ExamContact::displayName():
     *   school admin → school name; opted-in teacher → full name;
     *   otherwise → "First L".
     */
    private function buildTeacherPrizeDrawPayload(?ExamContact $contact): array
    {
        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $currentQuarter = (int) ceil($now->month / 3);

        // 1. All teacher draws ever run, newest first.
        $draws = PrizeDraw::query()
            ->where('type', 'teacher')
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        // 2. Build keyed map so we can interleave the "current quarter,
        // not yet drawn" placeholder cleanly.
        $rows = [];
        $seen = [];

        foreach ($draws as $draw) {
            $key = "{$draw->year}-{$draw->quarter}";
            $seen[$key] = true;

            $winnerContact = $draw->winner_name
                ? ExamContact::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($draw->winner_name))])
                    ->first()
                : null;

            $rows[] = [
                'quarter' => (int) $draw->quarter,
                'year' => (int) $draw->year,
                'label' => "Q{$draw->quarter} {$draw->year}",
                'drawn_at' => $draw->created_at?->format('d M Y'),
                'has_winner' => true,
                'winner_display_name' => $winnerContact
                    ? $winnerContact->displayName()
                    : $this->fallbackShortName((string) $draw->winner_name),
                'winner_entries' => (int) ($draw->winner_entries ?? 0),
                'total_tickets' => (int) ($draw->total_tickets ?? 0),
            ];
        }

        // 3. Always surface the current quarter even if no draw has
        // happened yet — gives signed-in teachers something live to
        // engage with ("not yet drawn — you have 4 tickets so far").
        $currentKey = "{$currentYear}-{$currentQuarter}";
        if (! isset($seen[$currentKey])) {
            array_unshift($rows, [
                'quarter' => $currentQuarter,
                'year' => $currentYear,
                'label' => "Q{$currentQuarter} {$currentYear}",
                'drawn_at' => null,
                'has_winner' => false,
                'winner_display_name' => null,
                'winner_entries' => 0,
                'total_tickets' => 0,
            ]);
        }

        // 4. Live ticket count for the signed-in user — only meaningful
        // before a draw is run for the current quarter.
        $myTickets = $this->countUserTicketsInCurrentQuarter(
            $contact,
            $currentYear,
            $currentQuarter,
        );

        return [
            'quarters' => $rows,
            'my_current_quarter_tickets' => $myTickets,
            'current_quarter_label' => "Q{$currentQuarter} {$currentYear}",
        ];
    }

    /**
     * Count the user's non-CANCELLED exam entries in the given quarter,
     * matched by `exam_entries.teacher_name = $contact->name` (the same
     * string-name pattern QuarterEndController uses to build draw tickets).
     */
    private function countUserTicketsInCurrentQuarter(
        ?ExamContact $contact,
        int $year,
        int $quarter,
    ): int {
        if (! $contact) {
            return 0;
        }

        $startMonth = ($quarter - 1) * 3 + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();

        return ExamEntry::query()
            ->with('order:id,requested_start_date')
            ->whereRaw('LOWER(TRIM(teacher_name)) = ?', [strtolower(trim((string) $contact->name))])
            ->whereResultPossible()
            ->get()
            ->filter(function (ExamEntry $e) use ($start, $end) {
                $date = $e->exam_date ?? $e->order?->requested_start_date;
                return $date && Carbon::parse($date)->between($start, $end);
            })
            ->count();
    }

    /**
     * "First L" fallback for the rare case the winner_name on a PrizeDraw
     * row doesn't match any current ExamContact (legacy or hand-typed
     * draws). Mirrors ExamContact::displayName()'s short-name branch.
     */
    private function fallbackShortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) < 2) {
            return $name;
        }
        $firstName = $parts[0];
        $surname = end($parts);
        $lastInitial = mb_strtoupper(mb_substr($surname, 0, 1));
        return "{$firstName} {$lastInitial}";
    }

    /**
     * Handle the "I might have used a different email on Trinity" form.
     * Creates an admin task on /admin/tasks rather than emailing — keeps the
     * action visible alongside Paul's other work.
     */
    public function linkRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alternative_email' => ['required', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $detail = "User {$user->name} (id {$user->id}, role {$user->role}) registered with "
            ."{$user->email} but says their Trinity application used "
            ."{$data['alternative_email']}.";

        if (! empty($data['note'])) {
            $detail .= "\n\nUser note:\n".$data['note'];
        }

        $detail .= "\n\nNext step: link the user to the matching exam_contacts row "
            ."(or create one) so their dashboard surfaces their exam entries.";

        $task = Task::create([
            'title' => "Link {$user->email} to Trinity email {$data['alternative_email']}",
            'detail' => $detail,
            'priority' => 'medium',
            'status' => 'pending',
            'category' => 'admin',
        ]);

        // Two emails — admin notification (Paul) + receipt to the user.
        // Failures are logged but never block the redirect: the Task row
        // still exists so the work won't be lost.
        try {
            Mail::to('musicexams@musicexams.help')->send(
                new LinkRequestSubmitted(
                    task: $task,
                    user: $user,
                    alternativeEmail: $data['alternative_email'],
                    note: $data['note'] ?? null,
                )
            );
        } catch (\Exception $e) {
            Log::error('LinkRequestSubmitted email failed: '.$e->getMessage());
        }

        try {
            Mail::to($user->email)->send(
                new LinkRequestConfirmed(
                    user: $user,
                    alternativeEmail: $data['alternative_email'],
                )
            );
        } catch (\Exception $e) {
            Log::error('LinkRequestConfirmed email failed: '.$e->getMessage());
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Thanks — we\'ve got it. We\'ll link your account and email you when it\'s done.');
    }

    /**
     * "Report a correction" form — teacher spotted a wrong spelling or DOB on
     * one of their candidate rows. We don't edit the row directly (that's
     * deferred to a proper CRUD next session) — instead we create an admin
     * task on /admin/tasks with the entry details + user's note so Paul can
     * action it manually.
     *
     * Authorization: an entry "belongs to" a user if it shares their email
     * via applicant_email OR via teacher_contact_id (looking up the contact's
     * email). Admins can report on any entry.
     */
    public function correctionRequest(Request $request, ExamEntry $entry): RedirectResponse
    {
        $user = $request->user();

        if (! $this->userOwnsEntry($user, $entry)) {
            throw new AuthorizationException(
                'You can only report corrections for your own candidates.'
            );
        }

        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $detail = "Correction reported by {$user->name} ({$user->email}, role {$user->role}).\n\n"
            ."Entry: #{$entry->id}\n"
            ."Candidate: {$entry->candidate_name} ({$entry->candidate_number})\n"
            ."Current DOB: ".($entry->date_of_birth?->format('d M Y') ?? '—')."\n"
            ."Grade / subject: {$entry->grade} / {$entry->subject_area}\n"
            ."Exam date: ".($entry->exam_date?->format('d M Y') ?? '—')."\n\n"
            ."User's note:\n{$data['note']}";

        $task = Task::create([
            'title' => "Correction request: {$entry->candidate_name} (#{$entry->id})",
            'detail' => $detail,
            'priority' => 'medium',
            'status' => 'pending',
            'category' => 'admin',
        ]);

        // Two emails — admin notification (Paul) + receipt to the user.
        // Failures are logged but never block the redirect: the Task row
        // still exists so the work won't be lost.
        try {
            Mail::to('musicexams@musicexams.help')->send(
                new CorrectionRequestSubmitted(
                    task: $task,
                    user: $user,
                    entry: $entry,
                )
            );
        } catch (\Exception $e) {
            Log::error('CorrectionRequestSubmitted email failed: '.$e->getMessage());
        }

        try {
            Mail::to($user->email)->send(
                new CorrectionRequestConfirmed(
                    user: $user,
                    entry: $entry,
                )
            );
        } catch (\Exception $e) {
            Log::error('CorrectionRequestConfirmed email failed: '.$e->getMessage());
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Thanks — we\'ve logged your correction and will action it shortly.');
    }

    private function userOwnsEntry(\App\Models\User $user, ExamEntry $entry): bool
    {
        // Admin can report on anyone's entry.
        if ($user->isAdmin()) {
            return true;
        }

        // Direct match via applicant_email captured at order time.
        if ($entry->applicant_email && strcasecmp($entry->applicant_email, $user->email) === 0) {
            return true;
        }

        // Indirect match via teacher_contact_id → exam_contacts row sharing
        // the user's email (canonical or via contact_emails).
        if ($entry->teacher_contact_id) {
            $hasMatchingContact = ExamContact::query()
                ->where('id', $entry->teacher_contact_id)
                ->where(function ($q) use ($user) {
                    $q->where('email', $user->email)
                        ->orWhereHas('emails', fn ($eq) => $eq->where('email', $user->email));
                })
                ->exists();

            if ($hasMatchingContact) {
                return true;
            }
        }

        return false;
    }
}
