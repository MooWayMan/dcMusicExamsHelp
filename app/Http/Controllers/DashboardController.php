<?php

// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $entriesQuery = ExamEntry::query()
            ->select([
                'id', 'candidate_number', 'candidate_name', 'date_of_birth',
                'grade', 'subject_area', 'delivery_method',
                'result', 'score', 'exam_date',
            ])
            ->where(function ($q) use ($user, $contact) {
                $q->where('applicant_email', $user->email);
                if ($contact) {
                    $q->orWhere('teacher_contact_id', $contact->id);
                }
            })
            ->orderBy('candidate_name')
            ->orderByDesc('exam_date');

        $entries = $entriesQuery->get()->map(fn (ExamEntry $e) => [
            'id' => $e->id,
            'candidate_number' => $e->candidate_number,
            'candidate_name' => $e->candidate_name,
            'date_of_birth' => $e->date_of_birth?->format('d M Y'),
            'grade' => $e->grade,
            'subject_area' => $e->subject_area,
            'delivery_method' => $e->delivery_method,
            'result' => $e->result,
            'score' => $e->score,
            'exam_date' => $e->exam_date?->format('d M Y'),
        ]);

        return Inertia::render('Dashboard', [
            'examEntries' => $entries,
            'hasLinkedContact' => $contact !== null || $entries->isNotEmpty(),
        ]);
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

        Task::create([
            'title' => "Link {$user->email} to Trinity email {$data['alternative_email']}",
            'detail' => $detail,
            'priority' => 'medium',
            'status' => 'pending',
            'category' => 'admin',
        ]);

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

        Task::create([
            'title' => "Correction request: {$entry->candidate_name} (#{$entry->id})",
            'detail' => $detail,
            'priority' => 'medium',
            'status' => 'pending',
            'category' => 'admin',
        ]);

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
