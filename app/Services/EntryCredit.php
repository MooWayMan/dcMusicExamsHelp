<?php

// app/Services/EntryCredit.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use Illuminate\Support\Collection;

/**
 * Who an exam entry is credited to: the name its certificate email, badge
 * and prize-draw ticket go under. The ONE place this is decided; Quarter End
 * (its page and its draw) and the Certificates page's weekly send all ask
 * here. There used to be three copies and they drifted: the draw once
 * disagreed with its own page, and on 27 Sep 2026 the weekly send still put
 * every parent booking in one bucket with no email while Quarter End named
 * the parent (Alexandra King, Wilfred Morris's booking).
 *
 * In order:
 *   1. A school_admin booking linked to a school → the SCHOOL (Learn Music
 *      Ltd), so a school's entries pool under its name. The same person's
 *      own-pupil entries stay theirs (Emily Bates).
 *   2. The entry's teacher_name, when set.
 *   3. A parent/self booking with no teacher but a linked submitter → that
 *      submitter (the parent), so they get their own named group and email.
 *   4. Otherwise nobody → the UNASSIGNED group, which has no recipient.
 */
final class EntryCredit
{
    public const UNASSIGNED = 'Parent Bookings (no teacher assigned)';

    /**
     * @param  array<int, string>  $schoolNameByContactId
     * @param  array<string, array{name: string, email: ?string}>  $schoolMetaByNameLower
     * @param  array<int, string>  $submitterNameById
     */
    private function __construct(
        private readonly array $schoolNameByContactId,
        private readonly array $schoolMetaByNameLower,
        private readonly array $submitterNameById,
    ) {}

    /** Build for a set of entries; submitter names are looked up for these entries only. */
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

        $submitterNameById = ExamContact::whereIn(
            'id',
            $entries->pluck('submitter_contact_id')->filter()->unique()->values()
        )->pluck('name', 'id')->all();

        return new self($byContactId, $metaByNameLower, $submitterNameById);
    }

    /** The credited name, or null when nobody is. */
    public function name(ExamEntry $e): ?string
    {
        if ($e->booking_role === 'school_admin'
            && $e->teacher_contact_id
            && isset($this->schoolNameByContactId[$e->teacher_contact_id])) {
            return $this->schoolNameByContactId[$e->teacher_contact_id];
        }

        if (trim((string) $e->teacher_name) === ''
            && $e->submitter_contact_id
            && isset($this->submitterNameById[$e->submitter_contact_id])) {
            return $this->submitterNameById[$e->submitter_contact_id];
        }

        return $e->teacher_name;
    }

    /** The group an entry is listed under: its credited name, or UNASSIGNED. */
    public function group(ExamEntry $e): string
    {
        $name = trim((string) ($this->name($e) ?? ''));

        return $name === '' ? self::UNASSIGNED : (string) $this->name($e);
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
}
