<?php

// app/Console/Commands/LinkContactSchool.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * LinkContactSchool
 * -----------------
 * Make a contact a school_admin for a school so their exam entries roll up to
 * that SCHOOL in the teacher prize draw + volume badges (Phase-2 rollup).
 *
 * Does three things, all idempotent:
 *   1. Adds the `school_admin` type (keeps any existing types — Emily is both
 *      teacher and school_admin, so we never strip `teacher`).
 *   2. Links the contact to the school via contact_school.
 *   3. Re-tags the contact's entries to booking_role='school_admin' and stamps
 *      teacher_contact_id, matching by teacher_contact_id OR (when that's null)
 *      an exact teacher_name match. This is what makes QuarterEnd credit the
 *      school instead of the person.
 *
 *   sail artisan contacts:link-school "David Keeling" "Learn Music Ltd" --dry-run
 *   sail artisan contacts:link-school "David Keeling" "Learn Music Ltd"
 *
 * Only re-tag a pure manager this way — someone who ALSO teaches privately
 * would have their own entries wrongly credited to the school. Check the
 * --dry-run entry list first.
 */
class LinkContactSchool extends Command
{
    protected $signature = 'contacts:link-school
        {contact : Contact id or exact name}
        {school : School id or exact name}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Link a contact to a school as school_admin so their entries roll up to the school in the draw';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        $contact = $this->resolveContact($this->argument('contact'));
        if (! $contact) {
            $this->error('Contact not found: '.$this->argument('contact'));

            return self::FAILURE;
        }

        $school = $this->resolveSchool($this->argument('school'));
        if (! $school) {
            $this->error('School not found: '.$this->argument('school'));

            return self::FAILURE;
        }

        $this->line("Contact: {$contact->name} (ID {$contact->id})");
        $this->line("School:  {$school->name} (ID {$school->id})");
        $this->newLine();

        // 1. school_admin type (additive)
        if (! $contact->hasType('school_admin')) {
            $this->line('  + add type school_admin');
            if (! $dry) {
                $contact->addType('school_admin');
            }
        } else {
            $this->line('  = already school_admin');
        }

        // 2. contact_school link
        $alreadyLinked = $contact->schools()->whereKey($school->id)->exists();
        if (! $alreadyLinked) {
            $this->line("  + link to school {$school->name}");
            if (! $dry) {
                $contact->schools()->syncWithoutDetaching([$school->id]);
            }
        } else {
            $this->line('  = already linked to school');
        }

        // 3. re-tag entries — by FK, or by exact name where the FK is null
        $entries = ExamEntry::query()
            ->where('teacher_contact_id', $contact->id)
            ->orWhere(function ($q) use ($contact): void {
                $q->whereNull('teacher_contact_id')
                    ->whereRaw('LOWER(TRIM(teacher_name)) = ?', [mb_strtolower(trim($contact->name))]);
            })
            ->get();

        $retagged = 0;
        foreach ($entries as $entry) {
            $needsFk = $entry->teacher_contact_id !== $contact->id;
            $needsRole = $entry->booking_role !== 'school_admin';
            if (! $needsFk && ! $needsRole) {
                continue;
            }

            $candidate = $entry->candidate_name ?? "entry #{$entry->id}";
            $this->line("  ~ {$candidate}: booking_role → school_admin".($needsFk ? ', teacher_contact_id set' : ''));

            if (! $dry) {
                $entry->teacher_contact_id = $contact->id;
                $entry->booking_role = 'school_admin';
                $entry->save();
            }
            $retagged++;
        }

        $this->newLine();
        $this->line("  {$entries->count()} matching entries, {$retagged} to re-tag.");
        $this->info($dry ? 'Dry run complete.' : 'Done.');

        return self::SUCCESS;
    }

    private function resolveContact(string $id): ?ExamContact
    {
        return ctype_digit($id)
            ? ExamContact::find((int) $id)
            : ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($id))])->first();
    }

    private function resolveSchool(string $id): ?School
    {
        return ctype_digit($id)
            ? School::find((int) $id)
            : School::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($id))])->first();
    }
}
