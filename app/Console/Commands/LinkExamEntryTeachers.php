<?php
// app/Console/Commands/LinkExamEntryTeachers.php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LinkExamEntryTeachers extends Command
{
    protected $signature = 'exam-entries:link-teachers
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Safely link exam entry teacher names to exam contacts using exact unique matches';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $hasTeacherContactId = Schema::hasColumn('exam_entries', 'teacher_contact_id');

        $createdOrderLinks = 0;
        $updatedEntries = 0;
        $alreadyLinked = 0;
        $blankTeacherNames = 0;
        $noMatch = 0;
        $ambiguous = 0;

        $entries = ExamEntry::query()
            ->select(['id', 'order_id', 'teacher_name'])
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $rawTeacherName = trim((string) ($entry->teacher_name ?? ''));

            if ($rawTeacherName === '') {
                $blankTeacherNames++;
                continue;
            }

            $normalizedTeacherName = $this->normalizeName($rawTeacherName);

            $candidates = ExamContact::query()
                ->select(['id', 'name', 'role'])
                ->get()
                ->filter(function (ExamContact $contact) use ($normalizedTeacherName) {
                    return $this->normalizeName((string) $contact->name) === $normalizedTeacherName;
                })
                ->values();

            if ($candidates->isEmpty()) {
                $noMatch++;
                $this->line("No match: {$rawTeacherName}");
                continue;
            }

            $teacherCandidates = $candidates
                ->filter(fn (ExamContact $contact) => $contact->role === 'teacher')
                ->values();

            $chosen = null;

            if ($teacherCandidates->count() === 1) {
                $chosen = $teacherCandidates->first();
            } elseif ($teacherCandidates->count() > 1) {
                $ambiguous++;
                $this->warn("Ambiguous teacher match: {$rawTeacherName}");
                continue;
            } elseif ($candidates->count() === 1) {
                $chosen = $candidates->first();
            } else {
                $ambiguous++;
                $this->warn("Ambiguous non-teacher match: {$rawTeacherName}");
                continue;
            }

            if (! $chosen instanceof ExamContact) {
                $ambiguous++;
                continue;
            }

            if ($dryRun) {
                $this->line("Would link teacher '{$rawTeacherName}' -> contact #{$chosen->id} ({$chosen->name}) on exam entry #{$entry->id}");
            } else {
                DB::transaction(function () use (
                    $entry,
                    $chosen,
                    $hasTeacherContactId,
                    &$updatedEntries,
                    &$alreadyLinked,
                    &$createdOrderLinks
                ) {
                    if ($hasTeacherContactId) {
                        $freshEntry = ExamEntry::query()->find($entry->id);

                        if ($freshEntry && (int) ($freshEntry->teacher_contact_id ?? 0) !== (int) $chosen->id) {
                            $freshEntry->teacher_contact_id = $chosen->id;
                            $freshEntry->save();
                            $updatedEntries++;
                        } else {
                            $alreadyLinked++;
                        }
                    }

                    $order = $entry->order;

                    if ($order) {
                        $alreadyExists = DB::table('order_contacts')
                            ->where('order_id', $order->id)
                            ->where('exam_contact_id', $chosen->id)
                            ->where('role_in_order', 'teacher')
                            ->exists();

                        if (! $alreadyExists) {
                            DB::table('order_contacts')->insert([
                                'order_id' => $order->id,
                                'exam_contact_id' => $chosen->id,
                                'role_in_order' => 'teacher',
                                'is_primary' => false,
                                'notes' => 'Linked from exam_entries.teacher_name exact match',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $createdOrderLinks++;
                        }
                    }
                });
            }

            if ($dryRun) {
                $updatedEntries += $hasTeacherContactId ? 1 : 0;
                $createdOrderLinks++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Exam entries updated', $updatedEntries],
                ['Order teacher links created', $createdOrderLinks],
                ['Already linked / unchanged', $alreadyLinked],
                ['Blank teacher names', $blankTeacherNames],
                ['No match', $noMatch],
                ['Ambiguous', $ambiguous],
            ]
        );

        $this->info('Teacher linking complete.');

        return self::SUCCESS;
    }

    private function normalizeName(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }
}