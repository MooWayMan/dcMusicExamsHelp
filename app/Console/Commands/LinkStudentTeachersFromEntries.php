<?php
// app/Console/Commands/LinkStudentTeachersFromEntries.php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkStudentTeachersFromEntries extends Command
{
    protected $signature = 'students:link-teachers-from-exam-entries
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Backfill students.teacher_contact_id by propagating the FK from exam_entries.teacher_contact_id (only when unambiguous).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $linked = 0;
        $alreadySet = 0;
        $noEntryTeacher = 0;
        $ambiguous = 0;

        $students = Student::query()
            ->select(['id', 'first_name', 'last_name', 'teacher_contact_id'])
            ->whereNull('teacher_contact_id')
            ->orderBy('id')
            ->get();

        $this->info("Considering {$students->count()} student(s) with NULL teacher_contact_id.");

        foreach ($students as $student) {
            // Distinct non-null teacher_contact_ids across this student's exam_entries
            $distinctTeachers = DB::table('exam_entries')
                ->where('student_id', $student->id)
                ->whereNotNull('teacher_contact_id')
                ->distinct()
                ->pluck('teacher_contact_id')
                ->all();

            if (count($distinctTeachers) === 0) {
                $noEntryTeacher++;
                continue;
            }

            if (count($distinctTeachers) > 1) {
                $ambiguous++;
                $this->warn(sprintf(
                    'Ambiguous: student #%d (%s %s) has entries under teacher_contact_ids: %s — skipping',
                    $student->id,
                    $student->first_name,
                    $student->last_name,
                    implode(', ', $distinctTeachers)
                ));
                continue;
            }

            $teacherId = (int) $distinctTeachers[0];

            if ($dryRun) {
                $this->line(sprintf(
                    'Would link: student #%d (%s %s) -> teacher_contact_id %d',
                    $student->id,
                    $student->first_name,
                    $student->last_name,
                    $teacherId
                ));
                $linked++;

                continue;
            }

            DB::transaction(function () use ($student, $teacherId, &$linked, &$alreadySet) {
                $fresh = Student::query()->find($student->id);

                if (! $fresh) {
                    return;
                }

                if ($fresh->teacher_contact_id !== null) {
                    $alreadySet++;

                    return;
                }

                $fresh->teacher_contact_id = $teacherId;
                $fresh->save();
                $linked++;
            });
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                [$dryRun ? 'Would link' : 'Linked', $linked],
                ['Already set (race / re-run)', $alreadySet],
                ['No teacher on any entry', $noEntryTeacher],
                ['Ambiguous (multi-teacher across entries)', $ambiguous],
            ]
        );

        $this->info($dryRun ? 'Dry run complete — no changes saved.' : 'Backfill complete.');

        return self::SUCCESS;
    }
}
