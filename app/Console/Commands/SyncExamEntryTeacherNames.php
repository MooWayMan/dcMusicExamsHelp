<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;

/**
 * Sync `exam_entries.teacher_name` (string) from the linked student's
 * teacher FK (`students.user_id` → `users.name`).
 *
 * Context: imports stamp `teacher_name` from Trinity's Applicant field, which
 * is often NULL for parent-booked candidates. But separately, students can be
 * linked to a teacher via `students.user_id`. The two are out of sync — the
 * Quarter End page groups by the string, so students with a real teacher FK
 * but no string end up in "Parent Bookings (no teacher assigned)".
 *
 * This command closes that gap: for every exam_entry where teacher_name is
 * NULL, if the linked student has a teacher user, copy that user's name into
 * teacher_name. Non-destructive — only updates NULLs.
 *
 * Usage:
 *   sync:exam-entry-teacher-names --dry-run
 *   sync:exam-entry-teacher-names
 */
class SyncExamEntryTeacherNames extends Command
{
    protected $signature = 'sync:exam-entry-teacher-names
                            {--dry-run : Show what would change without updating}
                            {--quarter= : Only sync entries in this quarter (1-4)}
                            {--year= : Only sync entries in this year}';

    protected $description = 'Copy the students.user_id → users.name into exam_entries.teacher_name where teacher_name is NULL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $quarter = $this->option('quarter') ? (int) $this->option('quarter') : null;
        $year = $this->option('year') ? (int) $this->option('year') : null;

        $query = ExamEntry::with(['student.user', 'order:id,requested_start_date'])
            ->whereNull('teacher_name')
            ->whereHas('student', fn ($q) => $q->whereNotNull('user_id'));

        // Optional quarter/year filter — applied in-memory since the date can
        // come from either exam_date or order.requested_start_date.
        $entries = $query->get();

        if ($quarter && $year) {
            $startMonth = (($quarter - 1) * 3) + 1;
            $start = \Carbon\Carbon::create($year, $startMonth, 1)->startOfDay();
            $end = $start->copy()->addMonths(3)->subDay()->endOfDay();
            $entries = $entries->filter(function ($e) use ($start, $end) {
                $date = $e->exam_date ?? $e->order?->requested_start_date;
                return $date && $date->between($start, $end);
            });
        }

        if ($entries->isEmpty()) {
            $this->info('No exam_entries with NULL teacher_name and a linked student-teacher — nothing to do.');
            return Command::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d exam_entries with a linked teacher available.',
            $dryRun ? 'Would update' : 'Updating',
            $entries->count()
        ));

        $updated = 0;
        $skipped = 0;
        $byTeacher = [];

        foreach ($entries as $entry) {
            $teacherName = $entry->student?->user?->name;

            if (! $teacherName) {
                $skipped++;
                continue;
            }

            $candidate = $entry->candidate_name ?? ($entry->student ? "{$entry->student->first_name} {$entry->student->last_name}" : 'Unknown');

            if ($dryRun) {
                $this->line("  (would set) {$candidate} → teacher_name = '{$teacherName}'");
            } else {
                $entry->update(['teacher_name' => $teacherName]);
                $this->line("  ✓ {$candidate} → teacher_name = '{$teacherName}'");
            }

            $byTeacher[$teacherName] = ($byTeacher[$teacherName] ?? 0) + 1;
            $updated++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d entries across %d teacher(s).',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            count($byTeacher)
        ));

        if (! empty($byTeacher)) {
            $this->newLine();
            $this->table(
                ['Teacher', 'Entries moved'],
                collect($byTeacher)->map(fn ($count, $name) => [$name, $count])->values()->toArray()
            );
        }

        if ($skipped > 0) {
            $this->warn("{$skipped} entries skipped (student had no valid linked user).");
        }

        return Command::SUCCESS;
    }
}
