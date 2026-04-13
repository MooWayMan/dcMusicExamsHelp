<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Teacher;
use App\Models\TeacherEmail;
use Illuminate\Console\Command;

class PopulateTeachers extends Command
{
    protected $signature = 'teachers:populate {--fresh : Delete all existing teachers first} {--dry-run : Show what would happen without making changes}';

    protected $description = 'Populate the teachers table from known teacher data and link to exam entries';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($this->option('fresh') && ! $dryRun) {
            if (! $this->confirm('This will delete ALL existing teacher records. Continue?')) {
                return self::FAILURE;
            }
            TeacherEmail::truncate();
            Teacher::truncate();
            ExamEntry::query()->update(['teacher_id' => null]);
            $this->info('Cleared existing teacher data.');
        }

        // Known teachers and their emails from Trinity CSVs + Paul's research
        $teacherData = [
            // === TEACHERS ===
            [
                'name' => 'Clare Keeling',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'lessons@learnmusic.co.uk', 'label' => 'Learn Music Ltd', 'is_primary' => true],
                ],
                'notes' => 'Learn Music Ltd — Liverpool venue. Paul\'s sister.',
            ],
            [
                'name' => 'Daniel Rogers',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'exams@pulsemusicliverpool.com', 'label' => 'Pulse Music School', 'is_primary' => true],
                ],
                'notes' => 'Pulse Music School — KEY CLIENT. High retention priority. Lots of digital + F2F entries.',
            ],
            [
                'name' => 'Jennifer Hynes',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'jenniferhynesvocalist@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Singing teacher — Liverpool.',
            ],
            [
                'name' => 'Megan Price',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'meggypegggy@hotmail.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Flute teacher.',
            ],
            [
                'name' => 'Christopher Callaway',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'chris@chriscallaway.music', 'label' => 'professional', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Alexandra Bibby',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'bibbycooper@btopenworld.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music. Teacher for Sam Williamson.',
            ],
            [
                'name' => 'Stephen Shotton',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'hotshotts83@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Oboe teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Tracey LEA',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'tracey.lea11@btinternet.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Megan Thompson',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'megan.thompson@liverpoolphil.com', 'label' => 'Liverpool Philharmonic', 'is_primary' => true],
                ],
                'notes' => 'Violin/Viola/Flute teacher — In Harmony Liverpool / Wirral School of Music.',
            ],
            [
                'name' => 'Roxanne Twomey',
                'type' => 'teacher',
                'emails' => [
                    ['email' => 'schoolofrox@hotmail.com', 'label' => 'School of Rox', 'is_primary' => true],
                ],
                'notes' => 'School of Rox — R&P Guitar and Drums.',
            ],
            [
                'name' => 'Jenny Capstick',
                'type' => 'teacher',
                'emails' => [],
                'notes' => 'Singing teacher — Hillside High School. Paul books on her behalf.',
            ],
            [
                'name' => 'Rachel Jones',
                'type' => 'teacher',
                'emails' => [],
                'notes' => 'Teacher for Maya + Megan Parkinson.',
            ],

            // === PARENTS / SELF-BOOKED ===
            [
                'name' => 'Helen Khoo',
                'type' => 'parent',
                'emails' => [
                    ['email' => 'helm1@outlook.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent of Alice Jun Mei Khoo.',
            ],
            [
                'name' => 'Jay Parkinson',
                'type' => 'parent',
                'emails' => [
                    ['email' => 'jaydashome@yahoo.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent of Maya + Megan Parkinson.',
            ],
            [
                'name' => 'Seth Barraclough',
                'type' => 'parent',
                'emails' => [
                    ['email' => 'sethbarraclough@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent/self — Seth James Barraclough.',
            ],
            [
                'name' => 'Solomon Wetherall',
                'type' => 'parent',
                'emails' => [
                    ['email' => 'solwetherall@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Self-booked candidate — Tenor Horn.',
            ],
            [
                'name' => 'Ravi Steff',
                'type' => 'parent',
                'emails' => [
                    ['email' => 'sofieroberts@yahoo.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Self-booked candidate — Trombone.',
            ],
            [
                'name' => 'Adrian O\'Malley',
                'type' => 'parent',
                'emails' => [],
                'notes' => 'Parent of Jasper Christian O\'Malley.',
            ],
            [
                'name' => 'Gillian Leslie',
                'type' => 'parent',
                'emails' => [],
                'notes' => 'Parent of Jacob Thomas Leslie.',
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($teacherData as $data) {
            $existing = Teacher::where('name', $data['name'])->first();

            if ($existing && ! $this->option('fresh')) {
                $this->line("  Skipped (exists): {$data['name']}");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $emailList = collect($data['emails'])->pluck('email')->join(', ') ?: 'no email';
                $this->line("  Would create: {$data['name']} ({$data['type']}) — {$emailList}");
                $created++;
                continue;
            }

            $teacher = Teacher::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['emails'] as $emailData) {
                TeacherEmail::create([
                    'teacher_id' => $teacher->id,
                    'email' => $emailData['email'],
                    'label' => $emailData['label'] ?? null,
                    'is_primary' => $emailData['is_primary'] ?? false,
                ]);
            }

            $this->info("  Created: {$data['name']} ({$data['type']}) with " . count($data['emails']) . ' email(s)');
            $created++;
        }

        // Now link exam_entries to teachers by matching teacher_name
        if (! $dryRun) {
            $linked = 0;
            $teachers = Teacher::all()->keyBy(fn ($t) => strtolower(trim($t->name)));

            ExamEntry::whereNotNull('teacher_name')
                ->whereNull('teacher_id')
                ->get()
                ->each(function ($entry) use ($teachers, &$linked) {
                    $key = strtolower(trim($entry->teacher_name));
                    if ($teachers->has($key)) {
                        $entry->update(['teacher_id' => $teachers->get($key)->id]);
                        $linked++;
                    }
                });

            $this->info("\nLinked {$linked} exam entries to teachers.");
        }

        $this->newLine();
        $this->info("Done! Created: {$created}, Skipped: {$skipped}");

        // Show any unlinked teacher_name values
        $unlinked = ExamEntry::whereNotNull('teacher_name')
            ->whereNull('teacher_id')
            ->distinct()
            ->pluck('teacher_name');

        if ($unlinked->isNotEmpty()) {
            $this->warn("\nUnlinked teacher names (no matching teacher record):");
            $unlinked->each(fn ($name) => $this->line("  - {$name}"));
        }

        return self::SUCCESS;
    }
}
