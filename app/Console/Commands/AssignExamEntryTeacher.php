<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;

/**
 * Manually assign an exam entry's teacher_name to a specific name.
 *
 * Used when a candidate is parent-booked or self-booked and needs to be
 * regrouped under that applicant's name so the Quarter End workflow picks
 * them up correctly.
 *
 * Run `contacts:add` first to create the ExamContact (with role=parent or
 * self), then use this command to update the exam_entry's teacher_name to
 * match.
 *
 * Usage:
 *   entries:assign-teacher "Ravi Michael Steff" "Sofie Roberts"
 *   entries:assign-teacher "Solomon Elliot David Wetherall" "Solomon Wetherall"
 */
class AssignExamEntryTeacher extends Command
{
    protected $signature = 'entries:assign-teacher
                            {candidate : Full candidate_name on the exam entry (quoted)}
                            {teacher : Teacher / parent / self name to set as teacher_name (quoted)}';

    protected $description = 'Set exam_entries.teacher_name for a specific candidate';

    public function handle(): int
    {
        $candidate = trim($this->argument('candidate'));
        $teacher = trim($this->argument('teacher'));

        $entries = ExamEntry::where('candidate_name', $candidate)->get();

        if ($entries->isEmpty()) {
            $this->error("No exam_entries found for candidate '{$candidate}'.");
            return Command::FAILURE;
        }

        foreach ($entries as $entry) {
            $old = $entry->teacher_name ?? 'NULL';
            $entry->update(['teacher_name' => $teacher]);
            $this->line("  ✓ {$candidate} (entry #{$entry->id}) — teacher_name '{$old}' → '{$teacher}'");
        }

        $this->info("Updated {$entries->count()} entry/entries.");
        return Command::SUCCESS;
    }
}
