<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;

class CheckTeacherEntries extends Command
{
    protected $signature = 'data:check-teachers';
    protected $description = 'Show all entries grouped by teacher_name with scores';

    public function handle(): int
    {
        $entries = ExamEntry::whereNotNull('teacher_name')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->orderBy('teacher_name')
            ->get();

        $grouped = $entries->groupBy('teacher_name');

        foreach ($grouped as $teacher => $teacherEntries) {
            $withScores = $teacherEntries->whereNotNull('score')->count();
            $total = $teacherEntries->count();
            $this->info("\n{$teacher} — {$withScores}/{$total} have results");

            foreach ($teacherEntries as $e) {
                $score = $e->score ?? 'PENDING';
                $this->line("  {$e->candidate_name} | Grade {$e->grade} | Score: {$score}");
            }
        }

        // Also show unassigned
        $unassigned = ExamEntry::whereNull('teacher_name')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get();

        if ($unassigned->count()) {
            $this->warn("\nUnassigned (no teacher) — {$unassigned->count()} entries");
            foreach ($unassigned as $e) {
                $score = $e->score ?? 'PENDING';
                $this->line("  {$e->candidate_name} | Grade {$e->grade} | Score: {$score}");
            }
        }

        return Command::SUCCESS;
    }
}
