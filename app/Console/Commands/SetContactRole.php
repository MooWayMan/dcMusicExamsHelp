<?php

namespace App\Console\Commands;

use App\Models\ExamContact;
use Illuminate\Console\Command;

/**
 * Reclassify contacts that were wrongly tagged `teacher` by the old importer
 * (the rule-4 shape default minted parents/self-applicants as teachers, which
 * polluted the prize draw — Mark Vincent-Smith, Helen Khoo, etc.). Removes the
 * `teacher` type and sets the correct role.
 *
 *   php artisan contacts:set-role parent "Mark Vincent-Smith" "Helen Khoo" --dry-run
 *   php artisan contacts:set-role candidate "Seth Barraclough"
 *   php artisan contacts:set-role parent 38            (also accepts ids)
 *
 * Run this on PROD after the fixed importer is live, so a re-import can't
 * re-pollute what you just cleaned.
 */
class SetContactRole extends Command
{
    protected $signature = 'contacts:set-role
        {role : Target role (parent, candidate, teacher, school_admin)}
        {names* : Contact ids or exact names}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Reclassify contacts: drop the teacher type and set the given role (prize-draw cleanup).';

    public function handle(): int
    {
        $role = strtolower(trim($this->argument('role')));

        if (! in_array($role, ExamContact::TYPES, true)) {
            $this->error("Unknown role '{$role}'. Allowed: " . implode(', ', ExamContact::TYPES));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        $changed = 0;

        foreach ($this->argument('names') as $identifier) {
            $identifier = trim((string) $identifier);

            $contact = ctype_digit($identifier)
                ? ExamContact::find((int) $identifier)
                : ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($identifier)])->first();

            if (! $contact) {
                $this->warn("  Not found: {$identifier}");

                continue;
            }

            $before = $contact->types;
            $removeTeacher = $role !== 'teacher' && in_array('teacher', $before, true);
            $addRole = ! in_array($role, $before, true);

            $after = array_values(array_unique(array_merge(
                array_filter($before, fn ($t) => ! ($removeTeacher && $t === 'teacher')),
                [$role],
            )));

            $this->line("  {$contact->name} (ID {$contact->id}): [" . implode(', ', $before) . '] → [' . implode(', ', $after) . ']');

            if (! $dry) {
                if ($removeTeacher) {
                    $contact->removeType('teacher');
                }
                if ($addRole) {
                    $contact->addType($role);
                }
                $changed++;
            }
        }

        $this->info($dry ? 'Dry run complete.' : "Done — {$changed} contact(s) updated.");

        return self::SUCCESS;
    }
}
