<?php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\School;
use Illuminate\Console\Command;

/**
 * Persist instruments onto teacher / school-admin contacts (and their
 * schools) from existing exam entries, so the instrument profile survives
 * deletion of the entries it was derived from. Idempotent —
 * syncWithoutDetaching never duplicates a link.
 *
 *   php artisan instruments:backfill --dry-run
 *   php artisan instruments:backfill
 */
class BackfillContactInstruments extends Command
{
    protected $signature = 'instruments:backfill {--dry-run : Show what would be linked without saving}';

    protected $description = 'Persist instruments onto teacher/school-admin contacts and their schools from existing exam entries.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        $entries = ExamEntry::whereNotNull('teacher_contact_id')
            ->whereNotNull('instrument_id')
            ->get(['id', 'teacher_contact_id', 'instrument_id', 'booking_role']);

        // school_admin contact id => [school ids]
        $schoolsByContact = ExamContact::withType('school_admin')
            ->with('schools:id')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->schools->pluck('id')->all()]);

        $contactMap = []; // contact id => [instrument id => true]
        $schoolMap = [];  // school id  => [instrument id => true]

        foreach ($entries as $e) {
            $contactMap[$e->teacher_contact_id][$e->instrument_id] = true;

            if ($e->booking_role === 'school_admin' && isset($schoolsByContact[$e->teacher_contact_id])) {
                foreach ($schoolsByContact[$e->teacher_contact_id] as $sid) {
                    $schoolMap[$sid][$e->instrument_id] = true;
                }
            }
        }

        $contactLinks = 0;
        foreach ($contactMap as $contactId => $instrumentIds) {
            $ids = array_keys($instrumentIds);
            $this->line("  Contact {$contactId} → instruments " . implode(', ', $ids));
            if (! $dry) {
                ExamContact::find($contactId)?->instruments()->syncWithoutDetaching($ids);
                $contactLinks += count($ids);
            }
        }

        $schoolLinks = 0;
        foreach ($schoolMap as $schoolId => $instrumentIds) {
            $ids = array_keys($instrumentIds);
            $this->line("  School {$schoolId} → instruments " . implode(', ', $ids));
            if (! $dry) {
                School::find($schoolId)?->instruments()->syncWithoutDetaching($ids);
                $schoolLinks += count($ids);
            }
        }

        $this->info($dry
            ? 'Dry run complete.'
            : "Done — {$contactLinks} contact-instrument links, {$schoolLinks} school-instrument links.");

        return self::SUCCESS;
    }
}
