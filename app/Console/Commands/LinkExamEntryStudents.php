<?php
// app/Console/Commands/LinkExamEntryStudents.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkExamEntryStudents extends Command
{
    protected $signature = 'exam-entries:link-students
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Safely link exam entries to students using exact candidate name matches, creating students when needed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $linkedExisting = 0;
        $createdStudents = 0;
        $updatedEntries = 0;
        $alreadyLinked = 0;
        $blankNames = 0;
        $ambiguous = 0;

        $entries = ExamEntry::query()
            ->select(['id', 'student_id', 'candidate_name'])
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $candidateName = trim((string) ($entry->candidate_name ?? ''));

            if ($candidateName === '') {
                $blankNames++;
                continue;
            }

            if ($entry->student_id) {
                $alreadyLinked++;
                continue;
            }

            [$firstName, $lastName] = $this->splitName($candidateName);

            if ($firstName === '' || $lastName === '') {
                $blankNames++;
                continue;
            }

            $matches = Student::query()
                ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($firstName)])
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($lastName)])
                ->get();

            if ($matches->count() > 1) {
                $ambiguous++;
                $this->warn("Ambiguous student match: {$candidateName}");
                continue;
            }

            if ($dryRun) {
                if ($matches->count() === 1) {
                    $this->line("Would link existing student '{$candidateName}' -> exam entry #{$entry->id}");
                    $linkedExisting++;
                    $updatedEntries++;
                } else {
                    $this->line("Would create student '{$candidateName}' and link to exam entry #{$entry->id}");
                    $createdStudents++;
                    $updatedEntries++;
                }

                continue;
            }

            DB::transaction(function () use (
                $entry,
                $matches,
                $firstName,
                $lastName,
                $candidateName,
                &$linkedExisting,
                &$createdStudents,
                &$updatedEntries
            ) {
                if ($matches->count() === 1) {
                    $student = $matches->first();
                    $linkedExisting++;
                } else {
                    $student = Student::create([
                        'user_id' => null,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => null,
                        'instrument_id' => null,
                        'notes' => 'Created from exam_entries candidate linking: '.$candidateName,
                    ]);

                    $createdStudents++;
                }

                $freshEntry = ExamEntry::query()->find($entry->id);

                if ($freshEntry && (int) ($freshEntry->student_id ?? 0) !== (int) $student->id) {
                    $freshEntry->student_id = $student->id;
                    $freshEntry->save();
                    $updatedEntries++;
                }
            });
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Linked to existing students', $linkedExisting],
                ['Created students', $createdStudents],
                ['Exam entries updated', $updatedEntries],
                ['Already linked', $alreadyLinked],
                ['Blank / unsplittable names', $blankNames],
                ['Ambiguous matches', $ambiguous],
            ]
        );

        $this->info('Student linking complete.');

        return self::SUCCESS;
    }

    /**
     * Split a full name into first_name + last_name.
     * We keep it simple and safe:
     * - first word => first_name
     * - everything else => last_name
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);

        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName, 2);

        if (count($parts) < 2) {
            return [$parts[0] ?? '', ''];
        }

        return [
            trim($parts[0]),
            trim($parts[1]),
        ];
    }
}