<?php
// app/Support/EntryCredit.php

namespace App\Support;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use Illuminate\Support\Collection;

/**
 * Who an exam entry is credited to, and whether it is still awaiting a result.
 *
 * Both questions have to be answered identically by /admin/quarter-end and the
 * certificate generator, or the two disagree about a teacher's candidates.
 *
 * The credit question exists because entries arrive in two shapes:
 *
 *   - The per-candidate results triple sets `teacher_name` from the role the
 *     human confirms at import. Those rows name their teacher directly.
 *   - The Section 1b enrolment-list import creates the candidate BEFORE any
 *     result exists, so it deliberately writes `teacher_name = null` — Trinity
 *     hasn't told us the teacher yet. The only link to a person is
 *     `submitter_contact_id`, whoever submitted the booking.
 *
 * Grouping on `teacher_name` alone therefore loses every not-yet-resulted
 * candidate. That is what hid Penelope Jane Mitchell from the Q2 2026
 * certificate report while Quarter End correctly counted her as pending.
 */
class EntryCredit
{
    /**
     * Contact names keyed by id, for every submitter referenced by the given
     * entries. Pass the result to nameFor() — one query instead of N.
     *
     * @param  Collection<int,ExamEntry>  $entries
     * @return array<int,string>
     */
    public static function submitterNames(Collection $entries): array
    {
        $ids = $entries->pluck('submitter_contact_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return ExamContact::whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * The person this entry is credited to.
     *
     * Uses trim() rather than a null check so a blank-string `teacher_name`
     * counts as absent — otherwise those rows group under '' and surface as a
     * phantom no-name bucket.
     *
     * @param  array<int,string>  $submitterNameById
     */
    public static function nameFor(ExamEntry $entry, array $submitterNameById, string $fallback = 'Unassigned'): string
    {
        $teacherName = trim((string) $entry->teacher_name);
        if ($teacherName !== '') {
            return $teacherName;
        }

        if ($entry->submitter_contact_id && isset($submitterNameById[$entry->submitter_contact_id])) {
            return $submitterNameById[$entry->submitter_contact_id];
        }

        return $fallback;
    }

    /**
     * Is this entry a result we are genuinely still waiting on?
     *
     * NO_SHOW and CANCELLED entries also have a null score, but Trinity will
     * never issue a result for either — listing them under "Awaiting Results"
     * tells a teacher to expect something that is never coming.
     */
    public static function isAwaitingResult(ExamEntry $entry): bool
    {
        return $entry->score === null
            && ! in_array($entry->notes, ExamEntry::NOTES_NO_RESULT, true);
    }
}
