<?php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Link F2F exam entries to their teacher contacts from a Trinity candidate
 * list. The F2F results-scan import writes score/result but no applicant, so
 * entries land with teacher_contact_id = null and never reach the teacher's
 * dashboard. This reads a CSV (candidate_number, teacher_name, teacher_email,
 * school_name), resolves/creates each teacher contact by EMAIL (so existing
 * contacts are reused, not duplicated), tags them as a teacher, and stamps
 * the link + school onto the matching entry.
 *
 * Reusable: point it at a new CSV per order. Always dry-run first.
 */
class LinkF2FTeachers extends Command
{
    protected $signature = 'f2f:link-teachers {path : Path to the teacher-links CSV} {--dry-run}';

    protected $description = 'Create/match teacher contacts by email and link them to F2F exam entries from a candidate-list CSV';

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("CSV not found: {$path}");

            return self::FAILURE;
        }

        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map('trim', array_shift($rows));
        $required = ['candidate_number', 'teacher_name', 'teacher_email', 'school_name'];
        if (array_diff($required, $header)) {
            $this->error('CSV header must contain: '.implode(', ', $required));

            return self::FAILURE;
        }
        $idx = array_flip($header);

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        $contactsCreated = 0;
        $contactsMatched = 0;
        $entriesLinked = 0;
        $entriesMissing = [];
        $seenContact = [];

        DB::beginTransaction();

        foreach ($rows as $row) {
            $candidate = trim($row[$idx['candidate_number']] ?? '');
            $name = trim($row[$idx['teacher_name']] ?? '');
            $email = trim($row[$idx['teacher_email']] ?? '');
            $school = trim($row[$idx['school_name']] ?? '');

            if ($candidate === '' || $email === '') {
                continue;
            }

            // Resolve the teacher contact by email first (reuses existing
            // records, incl. secondary emails); only create when unknown.
            $contact = ExamContact::findByEmail($email);
            $isNew = false;
            if (! $contact) {
                $isNew = true;
                if (! $dryRun) {
                    $contact = ExamContact::create([
                        'name' => $name,
                        'email' => $email,
                        'source' => 'f2f-candidate-list',
                    ]);
                    $contact->addType('teacher');
                }
            } elseif (! $dryRun && ! $contact->isTeacher()) {
                $contact->addType('teacher');
            }

            $contactKey = mb_strtolower($email);
            if (! isset($seenContact[$contactKey])) {
                $seenContact[$contactKey] = true;
                $isNew ? $contactsCreated++ : $contactsMatched++;
            }

            $entry = ExamEntry::where('candidate_number', $candidate)->first();
            if (! $entry) {
                $entriesMissing[] = "{$candidate} ({$name})";

                continue;
            }

            if (! $dryRun) {
                $entry->teacher_contact_id = $contact->id;
                $entry->teacher_name = $name;
                if ($school !== '') {
                    $entry->school_name = $school;
                }
                $entry->save();
            }
            $entriesLinked++;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        $this->newLine();
        $this->info('Teacher contacts created:  '.$contactsCreated);
        $this->info('Teacher contacts matched:  '.$contactsMatched);
        $this->info('Exam entries linked:       '.$entriesLinked);

        if ($entriesMissing) {
            $this->newLine();
            $this->warn('No exam entry found for '.count($entriesMissing).' candidate(s):');
            foreach ($entriesMissing as $m) {
                $this->line('  - '.$m);
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN complete — re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
