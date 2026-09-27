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
 *
 * Two ways to ask:
 *
 *   - nameFor() / submitterNames(): the PERSON an entry is credited to
 *     (teacher_name, else the submitter). The certificate batch uses this.
 *   - EntryCredit::for($entries) then name() / group(): the same, with a
 *     school admin's school bookings credited to the SCHOOL (Learn Music
 *     Ltd), which is how Quarter End's page, its prize draw and the
 *     Certificates weekly send group entries. Built 27 Sep 2026, when those
 *     three still had their own copies and the weekly send put every parent
 *     booking (Alexandra King, for Wilfred Morris) in one bucket with no
 *     email while Quarter End named her. A second class with this name was
 *     briefly created in App\Services the same day and folded back in here.
 */
class EntryCredit
{
    /** The group for entries credited to nobody; it has no one to email. */
    public const UNASSIGNED = 'Parent Bookings (no teacher assigned)';

    /**
     * @param  array<int, string>  $schoolNameByContactId
     * @param  array<string, array{name: string, email: ?string}>  $schoolMetaByNameLower
     * @param  array<int, string>  $submitterNameById
     */
    private function __construct(
        private readonly array $schoolNameByContactId = [],
        private readonly array $schoolMetaByNameLower = [],
        private readonly array $submitterNameById = [],
    ) {}

    /** Build for a set of entries: school rollup plus their submitters' names. */
    public static function for(Collection $entries): self
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

        return new self($byContactId, $metaByNameLower, self::submitterNames($entries));
    }

    /**
     * Who this entry is credited to, with school bookings going to the school.
     * Null when nobody is. The person part is nameFor(), so the teacher /
     * submitter rule is written once.
     */
    public function name(ExamEntry $e): ?string
    {
        if ($e->booking_role === 'school_admin'
            && $e->teacher_contact_id
            && isset($this->schoolNameByContactId[$e->teacher_contact_id])) {
            return $this->schoolNameByContactId[$e->teacher_contact_id];
        }

        $person = self::nameFor($e, $this->submitterNameById, '');

        return $person === '' ? null : $person;
    }

    /** The group an entry is listed under: its credited name, or UNASSIGNED. */
    public function group(ExamEntry $e): string
    {
        return $this->name($e) ?? self::UNASSIGNED;
    }

    /** @return array{name: string, email: ?string}|null When the name is a school's. */
    public function schoolMeta(?string $name): ?array
    {
        return $this->schoolMetaByNameLower[strtolower(trim((string) $name))] ?? null;
    }

    /** @return array<string, array{name: string, email: ?string}> Keyed by lower-cased school name. */
    public function schools(): array
    {
        return $this->schoolMetaByNameLower;
    }

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
