<?php

// app/Services/TeacherEntries.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Which exam entries belong to a signed-in person: the dashboard list, its
 * CSV and PDF exports, the admin "preview as contact" view, and the Piece
 * tracker's candidate list all read them from here, so they cannot disagree.
 *
 * A person is matched to an exam_contacts row by email (canonical or one of
 * the contact's extra emails). With a contact, an entry is theirs when they
 * are the named teacher, the submitter (candidates still awaiting a result
 * are linked ONLY that way — the enrolment-list import leaves teacher and
 * applicant empty), or the applicant under any of the contact's emails.
 * Without one, only entries applied for under their own email.
 */
final class TeacherEntries
{
    /**
     * Earliest date the dashboard offers. Centre 120's exam history in this
     * system starts in 2026 — the Quarter End email tells teachers they can
     * see everything "from January 2026", so the two must not drift apart.
     */
    public const HISTORY_START = '2026-01-01';

    public function contactFor(User $user): ?ExamContact
    {
        return ExamContact::query()
            ->where('email', $user->email)
            ->orWhereHas('emails', fn ($q) => $q->where('email', $user->email))
            ->first();
    }

    /**
     * @return array{0: ?ExamContact, 1: Collection<int, ExamEntry>}
     */
    public function forUser(User $user, Carbon $from, Carbon $to): array
    {
        $contact = $this->contactFor($user);

        if ($contact) {
            return [$contact, $this->forContact($contact, $from, $to)->get()];
        }

        $entries = $this->withinRange(ExamEntry::query(), $from, $to)
            ->select(self::COLUMNS)
            ->with('instrument:id,name')
            ->where('exam_entries.applicant_email', $user->email)
            ->orderBy('exam_entries.candidate_name')
            ->orderByDesc('exam_entries.exam_date')
            ->get();

        return [null, $entries];
    }

    public function forContact(ExamContact $contact, Carbon $from, Carbon $to): Builder
    {
        $emails = collect([$contact->email])
            ->merge($contact->emails->pluck('email'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->withinRange(ExamEntry::query(), $from, $to)
            ->select(self::COLUMNS)
            ->with('instrument:id,name')
            ->where(function ($q) use ($contact, $emails) {
                $q->where('exam_entries.teacher_contact_id', $contact->id)
                    ->orWhere('exam_entries.submitter_contact_id', $contact->id);
                if (! empty($emails)) {
                    $q->orWhereIn('exam_entries.applicant_email', $emails);
                }
            })
            ->orderBy('exam_entries.candidate_name')
            ->orderByDesc('exam_entries.exam_date');
    }

    /**
     * Columns every view of a person's entries needs, qualified because
     * withinRange() joins `orders` (which has overlapping column names).
     */
    private const COLUMNS = [
        'exam_entries.id',
        'exam_entries.student_id',
        'exam_entries.instrument_id',
        'exam_entries.candidate_number',
        'exam_entries.candidate_name',
        'exam_entries.date_of_birth',
        'exam_entries.grade',
        'exam_entries.subject_area',
        'exam_entries.delivery_method',
        'exam_entries.result',
        'exam_entries.score',
        'exam_entries.exam_date',
        'exam_entries.notes',
        'exam_entries.report',
    ];

    /**
     * Uses the order's requested start date when the entry has no exam_date
     * of its own. That is the normal state for a candidate whose result hasn't
     * come back yet — filtering on exam_date alone would silently drop exactly
     * the pending rows the dashboard is meant to surface.
     */
    private function withinRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query
            ->leftJoin('orders', 'exam_entries.order_id', '=', 'orders.id')
            ->whereRaw(
                'COALESCE(exam_entries.exam_date, orders.requested_start_date) BETWEEN ? AND ?',
                [$from->toDateString(), $to->toDateString()]
            );
    }
}
