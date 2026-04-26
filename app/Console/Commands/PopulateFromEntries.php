<?php

namespace App\Console\Commands;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\School;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PopulateFromEntries extends Command
{
    protected $signature = 'data:populate-from-entries {--dry-run : Show what would be created without saving}';
    protected $description = 'Create teachers, students and schools from exam_entries data';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — nothing will be saved.');
        }

        // ─── TEACHERS ───
        // Get unique teacher names from exam_entries (excluding nulls)
        $teacherNames = ExamEntry::whereNotNull('teacher_name')
            ->where('teacher_name', '!=', '')
            ->distinct()
            ->pluck('teacher_name');

        $teachersCreated = 0;
        $teachersExisted = 0;
        $teacherMap = []; // name (lowercase) => ExamContact model

        foreach ($teacherNames as $name) {
            $trimmed = trim($name);

            $existing = ExamContact::withType('teacher')
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmed)])
                ->first();

            if ($existing) {
                $teacherMap[strtolower($trimmed)] = $existing;
                $teachersExisted++;
                $this->line("  Teacher exists: {$name} (ID {$existing->id})");
            } else {
                if ($dryRun) {
                    $this->line("  Would create teacher: {$name}");
                    $teachersCreated++;
                } else {
                    $contact = ExamContact::create([
                        'name' => $trimmed,
                        'email' => Str::slug($trimmed) . '@placeholder.musicexams.help',
                    ]);
                    $contact->addType('teacher');
                    $teacherMap[strtolower($trimmed)] = $contact;
                    $teachersCreated++;
                    $this->line("  Created teacher: {$name} (ID {$contact->id})");
                }
            }
        }

        $this->info("Teachers: {$teachersCreated} created, {$teachersExisted} already existed.");

        // ─── SCHOOLS ───
        $schoolNames = ExamEntry::whereNotNull('school_name')
            ->where('school_name', '!=', '')
            ->distinct()
            ->pluck('school_name');

        $schoolsCreated = 0;
        $schoolsExisted = 0;
        $schoolMap = []; // name (lowercase) => School model

        foreach ($schoolNames as $name) {
            $existing = School::whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();

            if ($existing) {
                $schoolMap[strtolower(trim($name))] = $existing;
                $schoolsExisted++;
                $this->line("  School exists: {$name} (ID {$existing->id})");
            } else {
                if ($dryRun) {
                    $this->line("  Would create school: {$name}");
                    $schoolsCreated++;
                } else {
                    $school = School::create(['name' => trim($name)]);
                    $schoolMap[strtolower(trim($name))] = $school;
                    $schoolsCreated++;
                    $this->line("  Created school: {$name} (ID {$school->id})");
                }
            }
        }

        $this->info("Schools: {$schoolsCreated} created, {$schoolsExisted} already existed.");

        // ─── LINK TEACHERS TO SCHOOLS ───
        if (! $dryRun) {
            $teacherSchoolPairs = ExamEntry::whereNotNull('teacher_name')
                ->whereNotNull('school_name')
                ->where('teacher_name', '!=', '')
                ->where('school_name', '!=', '')
                ->select('teacher_name', 'school_name')
                ->distinct()
                ->get();

            $linksCreated = 0;
            foreach ($teacherSchoolPairs as $pair) {
                $teacher = $teacherMap[strtolower(trim($pair->teacher_name))] ?? null;
                $school = $schoolMap[strtolower(trim($pair->school_name))] ?? null;

                if ($teacher && $school) {
                    // syncWithoutDetaching won't duplicate existing pivots
                    $teacher->schools()->syncWithoutDetaching([$school->id]);
                    $linksCreated++;
                }
            }
            $this->info("Teacher–school links: {$linksCreated} checked/created.");
        }

        // ─── STUDENTS ───
        // Each unique candidate_name gets a Student record, linked to their teacher
        $entries = ExamEntry::whereNotNull('candidate_name')
            ->where('candidate_name', '!=', '')
            ->get();

        // Group by candidate name (lowercase) to avoid duplicates
        $uniqueStudents = $entries->groupBy(fn ($e) => strtolower(trim($e->candidate_name)));

        $studentsCreated = 0;
        $studentsExisted = 0;
        $studentMap = []; // candidate_name (lowercase) => Student model

        foreach ($uniqueStudents as $nameKey => $studentEntries) {
            $firstEntry = $studentEntries->first();
            $fullName = trim($firstEntry->candidate_name);
            $parts = preg_split('/\s+/', $fullName);
            $firstName = $parts[0];
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

            // Find teacher for this student (use first entry with a teacher)
            $teacherEntry = $studentEntries->first(fn ($e) => $e->teacher_name);
            $teacherContact = $teacherEntry
                ? ($teacherMap[strtolower(trim($teacherEntry->teacher_name))] ?? null)
                : null;

            // Check if student already exists (by name match)
            $existing = Student::whereRaw('LOWER(CONCAT(first_name, \' \', last_name)) = ?', [$nameKey])->first();

            if ($existing) {
                $studentMap[$nameKey] = $existing;
                $studentsExisted++;
            } else {
                if ($dryRun) {
                    $teacherDisplay = $teacherContact?->name;
                    $this->line("  Would create student: {$fullName}" . ($teacherDisplay ? " (teacher: {$teacherDisplay})" : ''));
                    $studentsCreated++;
                } else {
                    // Note: instrument is no longer stored on students (column
                    // dropped 2026-04-26 — students take multiple instruments
                    // over time). Per-exam instrument lives on exam_entries.
                    $student = Student::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'teacher_contact_id' => $teacherContact?->id,
                    ]);
                    $studentMap[$nameKey] = $student;
                    $studentsCreated++;
                }
            }
        }

        $this->info("Students: {$studentsCreated} created, {$studentsExisted} already existed.");

        // ─── LINK EXAM ENTRIES TO STUDENTS ───
        if (! $dryRun) {
            $linked = 0;
            foreach ($entries as $entry) {
                if ($entry->student_id) {
                    continue; // already linked
                }

                $key = strtolower(trim($entry->candidate_name));
                $student = $studentMap[$key] ?? null;

                if ($student) {
                    $entry->update(['student_id' => $student->id]);
                    $linked++;
                }
            }
            $this->info("Exam entries linked to students: {$linked}");
        }

        // ─── LINK ORDERS TO TEACHERS AND SCHOOLS ───
        if (! $dryRun) {
            $ordersLinked = 0;

            // Get orders that have entries with teacher/school info
            $orderIds = ExamEntry::whereNotNull('order_id')->distinct()->pluck('order_id');

            foreach ($orderIds as $orderId) {
                $order = \App\Models\Order::find($orderId);
                if (! $order) continue;

                // Find teacher from first entry with a teacher_name; attach via order_contacts pivot
                $alreadyLinked = \DB::table('order_contacts')
                    ->where('order_id', $order->id)
                    ->exists();

                if (! $alreadyLinked) {
                    $teacherEntry = ExamEntry::where('order_id', $orderId)
                        ->whereNotNull('teacher_name')
                        ->first();

                    if ($teacherEntry) {
                        $teacher = $teacherMap[strtolower(trim($teacherEntry->teacher_name))] ?? null;
                        if ($teacher) {
                            \DB::table('order_contacts')->insertOrIgnore([
                                'order_id' => $order->id,
                                'exam_contact_id' => $teacher->id,
                                'role_in_order' => 'teacher',
                                'is_primary' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Find school from first entry with a school_name
                if (! $order->school_id) {
                    $schoolEntry = ExamEntry::where('order_id', $orderId)
                        ->whereNotNull('school_name')
                        ->first();

                    if ($schoolEntry) {
                        $school = $schoolMap[strtolower(trim($schoolEntry->school_name))] ?? null;
                        if ($school) {
                            $order->update(['school_id' => $school->id]);
                        }
                    }
                }

                $ordersLinked++;
            }
            $this->info("Orders checked/linked: {$ordersLinked}");
        }

        // ─── SUMMARY ───
        $this->newLine();
        $this->table(
            ['Type', 'Created', 'Already Existed'],
            [
                ['Teachers', $teachersCreated, $teachersExisted],
                ['Schools', $schoolsCreated, $schoolsExisted],
                ['Students', $studentsCreated, $studentsExisted],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to save.');
        } else {
            $this->info('All done. Teachers, students and schools populated from exam entries.');
        }

        return Command::SUCCESS;
    }
}
