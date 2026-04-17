<?php

namespace App\Console\Commands;

use App\Models\ContactEmail;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToExamContacts extends Command
{
    protected $signature = 'contacts:migrate-from-teachers
        {--dry-run : Show what would happen without making changes}
        {--fresh : Clear exam_contacts and contact_emails first (CAREFUL)}';

    protected $description = 'Migrate Teacher records into ExamContact, copy emails, and re-link exam entries + students';

    /**
     * Known teacher/parent data — the single source of truth.
     * Sourced from Paul's verified research + Trinity CSVs + PopulateTeachers.
     */
    private function getKnownContacts(): array
    {
        return [
            // === TEACHERS ===
            [
                'name' => 'Clare Keeling',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'lessons@learnmusic.co.uk', 'label' => 'Learn Music Ltd', 'is_primary' => true],
                ],
                'notes' => 'Learn Music Ltd — Liverpool venue. Paul\'s sister. NOTE: venue contact does NOT mean teacher for F2F exams held there.',
            ],
            [
                'name' => 'Daniel Rogers',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'exams@pulsemusicliverpool.com', 'label' => 'Pulse Music School', 'is_primary' => true],
                ],
                'notes' => 'Pulse Music School — KEY CLIENT. High retention priority.',
            ],
            [
                'name' => 'Jennifer Hynes',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'jenniferhynesvocalist@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Singing teacher — Liverpool.',
            ],
            [
                'name' => 'Megan Price',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'meggypegggy@hotmail.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Flute teacher. Maiden name Rowland.',
            ],
            [
                'name' => 'Christopher Callaway',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'chris@chriscallaway.music', 'label' => 'professional', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Alexandra Bibby',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'bibbycooper@btopenworld.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music. Teacher for Sam Williamson.',
            ],
            [
                'name' => 'Stephen Shotton',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'hotshotts83@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Oboe teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Tracey LEA',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'tracey.lea11@btinternet.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Piano teacher — Wirral School of Music.',
            ],
            [
                'name' => 'Megan Thompson',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'megan.thompson@liverpoolphil.com', 'label' => 'Liverpool Philharmonic', 'is_primary' => true],
                ],
                'notes' => 'Violin/Viola/Flute teacher — In Harmony Liverpool / Wirral School of Music.',
            ],
            [
                'name' => 'Roxanne Twomey',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'schoolofrox@hotmail.com', 'label' => 'School of Rox', 'is_primary' => true],
                ],
                'notes' => 'School of Rox — R&P Guitar and Drums.',
            ],
            [
                'name' => 'Jenny Capstick',
                'role' => 'teacher',
                'emails' => [],
                'notes' => 'Singing teacher — Hillside High School. Paul books on her behalf.',
            ],
            [
                'name' => 'Rachel Jones',
                'role' => 'teacher',
                'emails' => [],
                'notes' => 'Teacher for Maya + Megan Parkinson.',
            ],
            [
                'name' => 'Paul Sheridan',
                'role' => 'teacher',
                'emails' => [
                    ['email' => 'musicexams@musicexams.help', 'label' => 'musicExams.help', 'is_primary' => true],
                    ['email' => 'madmusic6@hotmail.com', 'label' => 'personal', 'is_primary' => false],
                ],
                'notes' => 'Centre 120 owner. Also teaches brass, piano/keyboard, guitar.',
            ],

            // === PARENTS / SELF-BOOKED ===
            [
                'name' => 'Helen Khoo',
                'role' => 'parent',
                'emails' => [
                    ['email' => 'helm1@outlook.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent of Alice Jun Mei Khoo.',
            ],
            [
                'name' => 'Jay Parkinson',
                'role' => 'parent',
                'emails' => [
                    ['email' => 'jaydashome@yahoo.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent of Maya + Megan Parkinson.',
            ],
            [
                'name' => 'Seth Barraclough',
                'role' => 'parent',
                'emails' => [
                    ['email' => 'sethbarraclough@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Parent/self — Seth James Barraclough.',
            ],
            [
                'name' => 'Solomon Wetherall',
                'role' => 'self',
                'emails' => [
                    ['email' => 'solwetherall@gmail.com', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Self-booked candidate — Tenor Horn.',
            ],
            [
                'name' => 'Ravi Steff',
                'role' => 'parent',
                'emails' => [
                    ['email' => 'sofieroberts@yahoo.co.uk', 'label' => 'personal', 'is_primary' => true],
                ],
                'notes' => 'Self-booked candidate — Trombone.',
            ],
            [
                'name' => 'Adrian O\'Malley',
                'role' => 'parent',
                'emails' => [],
                'notes' => 'Parent of Jasper Christian O\'Malley.',
            ],
            [
                'name' => 'Gillian Leslie',
                'role' => 'parent',
                'emails' => [],
                'notes' => 'Parent of Jacob Thomas Leslie.',
            ],
        ];
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — no changes will be made.');
            $this->newLine();
        }

        if ($this->option('fresh') && ! $dryRun) {
            if (! $this->confirm('This will clear exam_contacts, contact_emails, and all teacher_contact_id links. Continue?')) {
                return self::FAILURE;
            }

            ExamEntry::query()->update(['teacher_contact_id' => null, 'teacher_credit_status' => 'unknown']);
            Student::query()->update(['teacher_contact_id' => null, 'teacher_credit_status' => 'unknown']);
            DB::table('order_contacts')->truncate();
            ContactEmail::truncate();
            ExamContact::query()->forceDelete();

            $this->info('Cleared existing data.');
            $this->newLine();
        }

        // ──────────────────────────────────────────
        // Step 1: Ensure all known contacts exist as ExamContacts
        // ──────────────────────────────────────────
        $this->info('Step 1: Ensuring known contacts exist as ExamContacts...');

        $teachers = Teacher::with('emails')->get();
        $knownContacts = collect($this->getKnownContacts());
        $contactsByName = []; // lowercase name → exam_contact_id
        $created = 0;
        $updated = 0;
        $skipped = 0;

        // First: migrate any Teacher model records (if they exist)
        foreach ($teachers as $teacher) {
            $key = strtolower(trim($teacher->name));
            $existing = ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();

            if ($existing) {
                $contactsByName[$key] = $existing->id;
                $skipped++;
                continue;
            }

            $role = match ($teacher->type) {
                'teacher' => 'teacher',
                'parent' => 'parent',
                'self' => 'self',
                default => 'unknown',
            };

            if ($dryRun) {
                $this->line("  Would create (from Teacher): {$teacher->name} ({$role})");
                $created++;
                continue;
            }

            $contact = ExamContact::create([
                'name' => $teacher->name,
                'email' => $teacher->primary_email,
                'phone' => $teacher->phone,
                'role' => $role,
                'source' => 'migrated_from_teachers',
                'notes' => $teacher->notes,
                'user_id' => $teacher->user_id,
            ]);

            $contactsByName[$key] = $contact->id;
            $created++;
            $this->info("  Created (from Teacher): {$teacher->name} → #{$contact->id}");
        }

        // Second: seed from known contacts list (fills gaps if Teacher table was empty)
        foreach ($knownContacts as $data) {
            $key = strtolower(trim($data['name']));

            if (isset($contactsByName[$key])) {
                continue; // already handled from Teacher table
            }

            $existing = ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();

            if ($existing) {
                // Update role/notes if the existing record was from an applicant import
                if (! $dryRun && ($existing->role === 'applicant' || $existing->role === 'unknown' || $existing->role === null)) {
                    $existing->update([
                        'role' => $data['role'],
                        'notes' => $data['notes'] ?? $existing->notes,
                        'source' => $existing->source ? $existing->source . ', known_contacts' : 'known_contacts',
                    ]);
                    $this->line("  Updated role: {$data['name']} → {$data['role']}");
                    $updated++;
                }
                $contactsByName[$key] = $existing->id;
                continue;
            }

            $primaryEmail = collect($data['emails'])->firstWhere('is_primary', true)['email'] ?? null;

            if ($dryRun) {
                $this->line("  Would create (known): {$data['name']} ({$data['role']})");
                $created++;
                continue;
            }

            $contact = ExamContact::create([
                'name' => $data['name'],
                'email' => $primaryEmail,
                'role' => $data['role'],
                'source' => 'known_contacts',
                'notes' => $data['notes'] ?? null,
            ]);

            $contactsByName[$key] = $contact->id;
            $created++;
            $this->info("  Created (known): {$data['name']} → #{$contact->id} ({$data['role']})");
        }

        $this->newLine();
        $this->info("Step 1 done: {$created} created, {$updated} updated, {$skipped} unchanged.");
        $this->newLine();

        // ──────────────────────────────────────────
        // Step 2: Copy emails to contact_emails
        // ──────────────────────────────────────────
        $this->info('Step 2: Copying emails to contact_emails...');

        $emailsCopied = 0;
        $emailsSkipped = 0;

        // From Teacher model emails
        foreach ($teachers as $teacher) {
            $key = strtolower(trim($teacher->name));
            $contactId = $contactsByName[$key] ?? null;

            if (! $contactId) {
                continue;
            }

            foreach ($teacher->emails as $teacherEmail) {
                if ($this->createEmailIfMissing($contactId, $teacherEmail->email, $teacherEmail->label, $teacherEmail->is_primary, $dryRun)) {
                    $emailsCopied++;
                } else {
                    $emailsSkipped++;
                }
            }
        }

        // From known contacts list
        foreach ($knownContacts as $data) {
            $key = strtolower(trim($data['name']));
            $contactId = $contactsByName[$key] ?? null;

            if (! $contactId) {
                continue;
            }

            foreach ($data['emails'] as $emailData) {
                if ($this->createEmailIfMissing($contactId, $emailData['email'], $emailData['label'] ?? null, $emailData['is_primary'] ?? false, $dryRun)) {
                    $emailsCopied++;
                } else {
                    $emailsSkipped++;
                }
            }
        }

        $this->info("Emails: {$emailsCopied} created, {$emailsSkipped} already existed.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN complete — linking steps skipped.');
            $this->showSummary();

            return self::SUCCESS;
        }

        // Rebuild the name lookup with ALL ExamContacts (including pre-existing ones)
        $allContactsByName = ExamContact::all()
            ->keyBy(fn ($c) => strtolower(trim($c->name)));

        // ──────────────────────────────────────────
        // Step 3: Link exam_entries.teacher_contact_id
        // ──────────────────────────────────────────
        $this->info('Step 3: Linking exam entries to ExamContacts...');

        $entriesLinked = 0;
        $entriesAlreadyLinked = 0;
        $entriesUnlinked = 0;

        ExamEntry::whereNotNull('teacher_name')
            ->chunk(100, function ($entries) use ($allContactsByName, &$entriesLinked, &$entriesAlreadyLinked, &$entriesUnlinked) {
                foreach ($entries as $entry) {
                    if ($entry->teacher_contact_id) {
                        $entriesAlreadyLinked++;
                        continue;
                    }

                    $key = strtolower(trim($entry->teacher_name));
                    $contact = $allContactsByName->get($key);

                    if ($contact) {
                        $entry->update([
                            'teacher_contact_id' => $contact->id,
                            'teacher_credit_status' => 'confirmed',
                        ]);
                        $entriesLinked++;
                    } else {
                        $entriesUnlinked++;
                    }
                }
            });

        $this->info("Exam entries: {$entriesLinked} linked, {$entriesAlreadyLinked} already linked, {$entriesUnlinked} unlinked.");
        $this->newLine();

        // ──────────────────────────────────────────
        // Step 4: Link students.teacher_contact_id
        // ──────────────────────────────────────────
        $this->info('Step 4: Linking students to ExamContacts...');

        $studentsLinked = 0;
        $studentsUnlinked = 0;

        Student::with('examEntries')->chunk(100, function ($students) use (&$studentsLinked, &$studentsUnlinked) {
            foreach ($students as $student) {
                if ($student->teacher_contact_id) {
                    continue;
                }

                $teacherContactId = $student->examEntries
                    ->whereNotNull('teacher_contact_id')
                    ->groupBy('teacher_contact_id')
                    ->sortByDesc(fn ($group) => $group->count())
                    ->keys()
                    ->first();

                if ($teacherContactId) {
                    $student->update([
                        'teacher_contact_id' => $teacherContactId,
                        'teacher_credit_status' => 'confirmed',
                    ]);
                    $studentsLinked++;
                } else {
                    $studentsUnlinked++;
                }
            }
        });

        $this->info("Students: {$studentsLinked} linked, {$studentsUnlinked} unlinked.");
        $this->newLine();

        // ──────────────────────────────────────────
        // Step 5: Ensure order_contacts exist for teacher roles
        // ──────────────────────────────────────────
        $this->info('Step 5: Ensuring teacher order_contacts exist...');

        $orderContactsCreated = 0;

        ExamEntry::whereNotNull('teacher_contact_id')
            ->get()
            ->groupBy('order_id')
            ->each(function ($entries, $orderId) use (&$orderContactsCreated) {
                $teacherContactIds = $entries->pluck('teacher_contact_id')->unique();

                foreach ($teacherContactIds as $contactId) {
                    $exists = DB::table('order_contacts')
                        ->where('order_id', $orderId)
                        ->where('exam_contact_id', $contactId)
                        ->where('role_in_order', 'teacher')
                        ->exists();

                    if (! $exists) {
                        DB::table('order_contacts')->insert([
                            'order_id' => $orderId,
                            'exam_contact_id' => $contactId,
                            'role_in_order' => 'teacher',
                            'is_primary' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $orderContactsCreated++;
                    }
                }
            });

        $this->info("Order contacts: {$orderContactsCreated} teacher links created.");
        $this->newLine();

        $this->showSummary();
        $this->newLine();
        $this->info('✅ Migration complete.');

        return self::SUCCESS;
    }

    private function createEmailIfMissing(int $contactId, string $email, ?string $label, bool $isPrimary, bool $dryRun): bool
    {
        $exists = ContactEmail::where('exam_contact_id', $contactId)
            ->where('email', $email)
            ->exists();

        if ($exists) {
            return false;
        }

        if ($dryRun) {
            $this->line("  Would copy: {$email} → ExamContact #{$contactId}");

            return true;
        }

        ContactEmail::create([
            'exam_contact_id' => $contactId,
            'email' => $email,
            'label' => $label,
            'is_primary' => $isPrimary,
        ]);

        return true;
    }

    private function showSummary(): void
    {
        $this->info('── Summary ──');
        $this->line('ExamContacts: ' . ExamContact::count());
        $this->line('ContactEmails: ' . ContactEmail::count());
        $this->line('Exam entries with teacher_contact_id: ' . ExamEntry::whereNotNull('teacher_contact_id')->count());
        $this->line('Exam entries WITHOUT teacher_contact_id: ' . ExamEntry::whereNull('teacher_contact_id')->count());
        $this->line('Students with teacher_contact_id: ' . Student::whereNotNull('teacher_contact_id')->count());
        $this->line('Students WITHOUT teacher_contact_id: ' . Student::whereNull('teacher_contact_id')->count());

        $unlinked = ExamEntry::whereNotNull('teacher_name')
            ->whereNull('teacher_contact_id')
            ->distinct()
            ->pluck('teacher_name');

        if ($unlinked->isNotEmpty()) {
            $this->newLine();
            $this->warn('Unlinked teacher names (no matching ExamContact):');
            $unlinked->each(fn ($name) => $this->line("  - {$name}"));
        }

        $noTeacher = ExamEntry::whereNull('teacher_name')
            ->whereNull('teacher_contact_id')
            ->count();

        if ($noTeacher > 0) {
            $this->line("\n{$noTeacher} entries have no teacher name (digital orders — assign manually in admin).");
        }
    }
}
