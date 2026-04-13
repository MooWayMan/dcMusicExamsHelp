<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use Illuminate\Console\Command;

class FixF2FTeacherNames extends Command
{
    protected $signature = 'exam:fix-f2f-teachers';
    protected $description = 'Fix teacher_name and student_id on F2F exam entries (Q1 2026)';

    public function handle(): int
    {
        $fixes = $this->getFixes();
        $updated = 0;

        foreach ($fixes as $fix) {
            $entry = ExamEntry::where('candidate_name', $fix['candidate_name'])
                ->where('delivery_method', 'Default')
                ->first();

            if (! $entry) {
                $this->warn("Entry not found: {$fix['candidate_name']}");
                continue;
            }

            $changes = [];

            if (($entry->teacher_name ?? '') !== ($fix['teacher_name'] ?? '')) {
                $changes['teacher_name'] = $fix['teacher_name'];
            }

            if ($entry->student_id !== $fix['student_id']) {
                $changes['student_id'] = $fix['student_id'];
            }

            if (! empty($changes)) {
                $entry->update($changes);
                $old = $entry->getOriginal('teacher_name') ?? 'NULL';
                $new = $fix['teacher_name'] ?? 'NULL';
                $this->line("  {$fix['candidate_name']}: teacher '{$old}' → '{$new}', student_id → {$fix['student_id']}");
                $updated++;
            }
        }

        $this->info("Fixed {$updated} entries.");

        // Verify
        $this->newLine();
        $this->info('=== Verification ===');

        $f2f = ExamEntry::where('delivery_method', 'Default')->get();
        $withTeacher = $f2f->whereNotNull('teacher_name')->count();
        $withoutTeacher = $f2f->whereNull('teacher_name')->count();
        $withStudent = $f2f->whereNotNull('student_id')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total F2F entries', $f2f->count()],
                ['With teacher_name', $withTeacher],
                ['Without teacher_name (parent/unknown)', $withoutTeacher],
                ['With student_id linked', $withStudent],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Correct teacher names and student_ids from restored backup (13 Apr 09:00 UTC).
     */
    private function getFixes(): array
    {
        return [
            // ── Order 155 — Learn Music Ltd, 5 Mar ──
            ['candidate_name' => 'Aria Maddison Chambers', 'teacher_name' => 'Jennifer Hynes', 'student_id' => 82],
            ['candidate_name' => 'Ravi Michael Steff', 'teacher_name' => null, 'student_id' => 83],
            ['candidate_name' => 'Solomon Elliot David Wetherall', 'teacher_name' => null, 'student_id' => 84],
            ['candidate_name' => 'Primrose Nancy Gannon', 'teacher_name' => 'Jennifer Hynes', 'student_id' => 85],
            ['candidate_name' => 'Maya Ghali', 'teacher_name' => 'Clare Keeling', 'student_id' => 86],
            ['candidate_name' => 'Elise Florence Scott', 'teacher_name' => 'Megan Price', 'student_id' => 87],
            ['candidate_name' => 'Dean Gwyther', 'teacher_name' => 'Clare Keeling', 'student_id' => 88],
            ['candidate_name' => 'Imogen Mayes', 'teacher_name' => 'Daniel Rogers', 'student_id' => 89],
            ['candidate_name' => 'Niamh Keyna Anakin', 'teacher_name' => 'Clare Keeling', 'student_id' => 90],
            ['candidate_name' => 'Isaac Pover', 'teacher_name' => 'Clare Keeling', 'student_id' => 91],
            ['candidate_name' => 'Farrah Harper Fennell', 'teacher_name' => 'Clare Keeling', 'student_id' => 92],
            ['candidate_name' => 'Kate Leyland', 'teacher_name' => 'Daniel Rogers', 'student_id' => 93],

            // ── Order 156 — Wirral School of Music, 6 Mar ──
            ['candidate_name' => 'Seth James Barraclough', 'teacher_name' => null, 'student_id' => 95],
            ['candidate_name' => 'Anna Martin', 'teacher_name' => 'Christopher Callaway', 'student_id' => 96],
            ['candidate_name' => 'Julia Zamirska', 'teacher_name' => 'Stephen Shotton', 'student_id' => 97],
            ['candidate_name' => 'Sam Williamson', 'teacher_name' => null, 'student_id' => 98],
            ['candidate_name' => 'Maya Parkinson', 'teacher_name' => 'Rachel Jones', 'student_id' => 99],
            ['candidate_name' => 'Imogen Hughes', 'teacher_name' => 'Christopher Callaway', 'student_id' => 100],
            ['candidate_name' => 'Krystian Debek', 'teacher_name' => 'Megan Thompson', 'student_id' => 101],
            ['candidate_name' => 'Florence Cookson', 'teacher_name' => 'Christopher Callaway', 'student_id' => 102],
            ['candidate_name' => 'Alice Jun Mei Khoo', 'teacher_name' => null, 'student_id' => 103],
            ['candidate_name' => 'Henry Rodway', 'teacher_name' => 'Christopher Callaway', 'student_id' => 104],
            ['candidate_name' => 'Megan Parkinson', 'teacher_name' => 'Rachel Jones', 'student_id' => 105],
            ['candidate_name' => 'Lucas Hassall', 'teacher_name' => 'Tracey LEA', 'student_id' => 106],

            // ── Order 157 — R&P, Learn Music Ltd, 7 Mar (already correct, adding student_id) ──
            ['candidate_name' => 'Amy Norcott', 'teacher_name' => 'Daniel Rogers', 'student_id' => 107],
            ['candidate_name' => 'Mia Mason', 'teacher_name' => 'Jenny Capstick', 'student_id' => 108],
            ['candidate_name' => 'Pearl Fay', 'teacher_name' => 'Daniel Rogers', 'student_id' => 109],
            ['candidate_name' => 'Charlotte McVey', 'teacher_name' => 'Jenny Capstick', 'student_id' => 110],
            ['candidate_name' => 'Zachary Beswick', 'teacher_name' => 'Daniel Rogers', 'student_id' => 111],
            ['candidate_name' => 'Zach Hughes', 'teacher_name' => 'Daniel Rogers', 'student_id' => 112],
            ['candidate_name' => 'Lilly-Mae Dibbert', 'teacher_name' => 'Jenny Capstick', 'student_id' => 113],

            // ── Order 158 — R&P, Learn Music Ltd, 7 Mar ──
            ['candidate_name' => 'Thomas Gander', 'teacher_name' => 'Roxanne Twomey', 'student_id' => 114],
            ['candidate_name' => 'Alfie Coburn', 'teacher_name' => 'Roxanne Twomey', 'student_id' => 115],
            ['candidate_name' => 'Francesca Lee', 'teacher_name' => 'Roxanne Twomey', 'student_id' => 116],
            ['candidate_name' => 'Jacob Thomas Leslie', 'teacher_name' => null, 'student_id' => 117],
            ['candidate_name' => 'Jasper Christian O\'Malley', 'teacher_name' => null, 'student_id' => 118],
            ['candidate_name' => 'Jemima Claire Reed', 'teacher_name' => null, 'student_id' => 119],
            ['candidate_name' => 'Daniel Carty', 'teacher_name' => 'Roxanne Twomey', 'student_id' => 120],
            ['candidate_name' => 'Philip Martin Gazdecki', 'teacher_name' => 'Clare Keeling', 'student_id' => 121],
        ];
    }
}
