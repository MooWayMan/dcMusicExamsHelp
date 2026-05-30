<?php
// app/Console/Commands/RepairExamEntryTeacherLinks.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot repair for exam_entries imported BEFORE 30 May 2026, when the
 * TrinityCsvImporter hardcoded the submitter as 'parent' and the entry's
 * teacher_contact_id FK was never populated. The Maria Nielsen / Lily Jago
 * case (5 May 2026, Grade 4 Singing) was the trigger.
 *
 * Two passes:
 *
 *   PASS 1 — Reclassify booking_role from 'parent' to 'teacher' on entries
 *            where the shape matches the Maria pattern: submitter ==
 *            applicant != candidate AND the submitter_contact_id is set
 *            AND no Summary teacher_name has been recorded. Also tags
 *            those submitter contacts as 'teacher'.
 *
 *   PASS 2 — Populate teacher_contact_id from submitter_contact_id on any
 *            entry where booking_role='teacher' AND teacher_contact_id is
 *            null AND submitter_contact_id is set. Syncs teacher_name from
 *            the contact too so search keeps working.
 *
 * Idempotent. --dry-run reports what would change without writing.
 */
class RepairExamEntryTeacherLinks extends Command
{
    protected $signature = 'exam-entries:repair-teacher-links
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Backfill exam_entries.teacher_contact_id from submitter_contact_id for teacher-shaped rows. Reclassifies historic blanket-parent rows. Idempotent.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $banner = $dryRun ? 'DRY RUN' : 'LIVE';
        $this->info("Exam-entry teacher-link repair — {$banner}");

        $pass1Reclassified = 0;
        $pass1ContactsRetagged = 0;
        $pass2Linked = 0;
        $pass2NameSynced = 0;

        // ──────────────────────────────────────────────────────────────
        // PASS 1 — Reclassify Maria-shaped 'parent' rows as 'teacher'
        // ──────────────────────────────────────────────────────────────
        // Loaded via Eloquent so we can reach the linked Order for
        // applicant_name (the only place that field lives — exam_entries
        // doesn't carry it). We join orders to be able to filter by it.

        $candidates = ExamEntry::query()
            ->from('exam_entries')
            ->join('orders', 'exam_entries.order_id', '=', 'orders.id')
            ->whereNull('exam_entries.teacher_contact_id')
            ->whereNotNull('exam_entries.submitter_contact_id')
            ->where('exam_entries.booking_role', 'parent')
            ->whereRaw("COALESCE(exam_entries.teacher_name, '') = ''")
            ->whereNotNull('orders.applicant_name')
            ->select([
                'exam_entries.id',
                'exam_entries.candidate_name',
                'exam_entries.submitter_contact_id',
                'orders.applicant_name as applicant_name',
            ])
            ->get();

        foreach ($candidates as $row) {
            // Shape check: applicant != candidate. (We already know
            // submitter_contact_id is set, and that submitter == applicant
            // in the 99% case for Trinity imports — but verify against the
            // submitter contact name as a belt-and-braces check.)
            $submitter = ExamContact::find($row->submitter_contact_id);
            if (! $submitter) {
                continue;
            }
            $applicantName = (string) $row->applicant_name;
            $candidateName = (string) $row->candidate_name;
            $submitterName = (string) $submitter->name;

            if ($this->namesMatch($applicantName, $candidateName)) {
                continue; // self-applicant, leave alone
            }
            if (! $this->namesMatch($submitterName, $applicantName)) {
                continue; // submitter ≠ applicant, ambiguous, skip
            }

            $this->line("  • Reclassify entry #{$row->id} '{$candidateName}' booking_role parent → teacher (submitter: {$submitterName})");

            if (! $dryRun) {
                DB::transaction(function () use ($row, $submitter, &$pass1Reclassified, &$pass1ContactsRetagged) {
                    DB::table('exam_entries')
                        ->where('id', $row->id)
                        ->update(['booking_role' => 'teacher', 'updated_at' => now()]);
                    $pass1Reclassified++;

                    if (! $submitter->hasType('teacher')) {
                        $submitter->addType('teacher');
                        $pass1ContactsRetagged++;
                    }
                });
            } else {
                $pass1Reclassified++;
                if (! $submitter->hasType('teacher')) {
                    $pass1ContactsRetagged++;
                }
            }
        }

        // ──────────────────────────────────────────────────────────────
        // PASS 2 — Link teacher_contact_id from submitter_contact_id
        // ──────────────────────────────────────────────────────────────
        // After Pass 1 has flipped the role, AND for any pre-existing
        // teacher-shaped row whose FK was never set, fill it in.

        $needsLinking = ExamEntry::query()
            ->whereNull('teacher_contact_id')
            ->whereNotNull('submitter_contact_id')
            ->where('booking_role', 'teacher')
            ->select(['id', 'submitter_contact_id', 'teacher_name'])
            ->get();

        foreach ($needsLinking as $entry) {
            $submitter = ExamContact::find($entry->submitter_contact_id);
            if (! $submitter) {
                continue;
            }

            $this->line("  • Link entry #{$entry->id} teacher_contact_id → contact #{$submitter->id} ({$submitter->name})");

            if (! $dryRun) {
                $updates = ['teacher_contact_id' => $submitter->id, 'updated_at' => now()];
                $currentTeacherName = trim((string) ($entry->teacher_name ?? ''));
                if ($currentTeacherName === '' || strtolower($currentTeacherName) !== strtolower((string) $submitter->name)) {
                    $updates['teacher_name'] = $submitter->name;
                    $pass2NameSynced++;
                }
                DB::table('exam_entries')
                    ->where('id', $entry->id)
                    ->update($updates);
                $pass2Linked++;
            } else {
                $pass2Linked++;
                $pass2NameSynced++;
            }
        }

        $this->newLine();
        $this->table(['Action', 'Count'], [
            ['Pass 1 — reclassified parent → teacher', $pass1Reclassified],
            ['Pass 1 — submitter contacts tagged teacher', $pass1ContactsRetagged],
            ['Pass 2 — teacher_contact_id linked', $pass2Linked],
            ['Pass 2 — teacher_name re-synced from contact', $pass2NameSynced],
        ]);

        if ($dryRun) {
            $this->warn('DRY RUN — nothing was written. Re-run without --dry-run to apply.');
        } else {
            $this->info('Repair complete.');
        }

        return self::SUCCESS;
    }

    /**
     * Same case-insensitive whitespace-collapsed comparison the importer
     * uses. Local copy so this command stays self-contained.
     */
    private function namesMatch(string $a, string $b): bool
    {
        $norm = fn (string $s) => strtolower(trim((string) preg_replace('/\s+/', ' ', $s)));
        return $norm($a) !== '' && $norm($a) === $norm($b);
    }
}
