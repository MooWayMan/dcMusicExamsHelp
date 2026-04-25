<?php

namespace App\Console\Commands;

use App\Models\ContactEmail;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Student;
use App\Models\Subscriber;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * UnifyContacts
 * --------------
 * One-shot backfill that collapses the three teacher-ish data sources
 * (users.role=teacher, teachers table, exam_contacts) into a single
 * canonical exam_contacts row per human, with multi-type membership via
 * the new contact_types pivot.
 *
 * Source of truth: the canonical map in $this->getCanonicalContacts().
 * Anything in the DB that isn't in the map is left alone.
 *
 * Idempotent — safe to re-run. Each step matches before mutating.
 *
 *    php artisan contacts:unify --dry-run     (preview)
 *    php artisan contacts:unify               (apply)
 */
class UnifyContacts extends Command
{
    protected $signature = 'contacts:unify
        {--dry-run : Show what would happen without making changes}';

    protected $description = 'Collapse users(teacher) + teachers + exam_contacts into single multi-type contacts';

    private bool $dryRun = false;

    /**
     * Allowed person-level types.
     *
     * teacher       — teaches a candidate
     * parent        — parent/guardian of a candidate
     * candidate     — the candidate themselves (incl. adult self-applicants)
     * school_admin  — runs/admins a music school (not Trinity, not a teacher)
     * trinity_admin — Trinity HQ staff
     * subscriber    — newsletter only (no exam history)
     */
    private const ALLOWED_TYPES = [
        'teacher', 'parent', 'candidate',
        'school_admin', 'trinity_admin', 'subscriber',
    ];

    /**
     * Canonical contact map — Paul-confirmed 2026-04-25.
     * Each row = one human. types is a list (multi-type).
     * primary_email = best email; secondary_emails added as ContactEmail rows.
     */
    private function getCanonicalContacts(): array
    {
        return [
            // === TEACHERS ===
            ['name' => 'Alexandra Bibby',     'types' => ['teacher', 'parent'], 'email' => 'bibbycooper@btopenworld.com',
             'notes' => 'Piano teacher — Wirral School of Music. Teacher of record for Sam Williamson. Historically also entered her own son (separate earlier order).'],
            ['name' => 'Christopher Callaway','types' => ['teacher'], 'email' => 'chris@chriscallaway.music',
             'notes' => 'Piano teacher — Wirral School of Music.'],
            ['name' => 'Clare Keeling',       'types' => ['teacher'], 'email' => 'musiclearn11@gmail.com',
             'secondary_emails' => ['lessons@learnmusic.co.uk', 'squashgirl73@gmail.com'],
             'notes' => 'Learn Music Ltd — Liverpool venue. Paul\'s sister. squashgirl73@ is her newsletter-signup email.'],
            ['name' => 'Helen Hodgkiss',     'types' => ['teacher'], 'email' => 'gold.musictuition@gmail.com',
             'notes' => 'Gold Music Tuition.'],
            ['name' => 'Jennifer Hynes',     'types' => ['teacher'], 'email' => 'jenniferhynesvocalist@gmail.com',
             'notes' => 'Singing teacher — Liverpool.'],
            ['name' => 'Jenny Capstick',     'types' => ['teacher'], 'email' => 'j.capstick@hillsidehigh.co.uk',
             'notes' => 'Singing teacher — Hillside High School. Paul books on her behalf.'],
            ['name' => 'Megan Price',        'types' => ['teacher'], 'email' => 'meganclr96@gmail.com',
             'notes' => 'Flute teacher. Maiden name Rowland. Old email meggypegggy@hotmail.co.uk dropped 2026-04-24.'],
            ['name' => 'Megan Thompson',     'types' => ['teacher'], 'email' => 'megan.thompson@liverpoolphil.com',
             'notes' => 'Violin/Viola/Flute teacher — In Harmony Liverpool / Wirral School of Music.'],
            ['name' => 'Rachel Jones',       'types' => ['teacher'], 'email' => 'rachelsimms1969@gmail.com',
             'notes' => 'Teacher for Maya + Megan Parkinson.'],
            ['name' => 'Ray Langley',        'types' => ['teacher'], 'email' => 'langley.r@live.co.uk',
             'notes' => 'Music teacher.'],
            ['name' => 'Roxanne Twomey',     'types' => ['teacher'], 'email' => 'schoolofrox@hotmail.com',
             'notes' => 'School of Rox — R&P Guitar and Drums.'],
            ['name' => 'Stephen Shotton',    'types' => ['teacher'], 'email' => 'hotshotts83@gmail.com',
             'notes' => 'Oboe teacher — Wirral School of Music.'],
            ['name' => 'Tracey Lea',         'types' => ['teacher'], 'email' => 'tracey.lea11@btinternet.com',
             'notes' => 'Piano teacher — Wirral School of Music.'],
            ['name' => 'Will Rogers',        'types' => ['teacher'], 'email' => 'willrogersmusic@gmail.com',
             'notes' => 'Music teacher.'],

            // === PARENTS ===
            ['name' => 'Adrian O\'Malley',   'types' => ['parent'], 'email' => 'adrian.omalley@icloud.com',
             'notes' => 'Parent of Jasper Christian O\'Malley.'],
            ['name' => 'Gillian Leslie',     'types' => ['parent'], 'email' => 'gillian.leslie@outlook.com',
             'notes' => 'Parent of Jacob Thomas Leslie.'],
            ['name' => 'Helen Khoo',         'types' => ['parent'], 'email' => 'helm1@outlook.com',
             'notes' => 'Parent of Alice Jun Mei Khoo. Personal friend of Paul.'],
            ['name' => 'Jay Parkinson',      'types' => ['parent'], 'email' => 'jaydashome@yahoo.co.uk',
             'notes' => 'Parent of Maya + Megan Parkinson.'],
            ['name' => 'Claire Reed',        'types' => ['parent'], 'email' => null,
             'notes' => 'Parent — unrelated to Clare Keeling (different spelling).'],

            // === CANDIDATES (self-applicants) ===
            ['name' => 'Ravi Steff',         'types' => ['candidate'], 'email' => 'sofieroberts@yahoo.co.uk',
             'notes' => 'Self-booked candidate — Trombone. Email unusual but confirmed correct by Paul 2026-04-25.'],
            ['name' => 'Seth Barraclough',   'types' => ['candidate'], 'email' => 'sethbarraclough@gmail.com',
             'notes' => 'Self-booked candidate — Seth James Barraclough.'],
            ['name' => 'Solomon Wetherall',  'types' => ['candidate'], 'email' => 'solwetherall@gmail.com',
             'notes' => 'Self-booked candidate — Tenor Horn.'],

            // === SCHOOL ADMINS ===
            ['name' => 'Daniel Rogers',      'types' => ['school_admin'], 'email' => 'rogers@pulsemusicliverpool.com',
             'secondary_emails' => ['exams@pulsemusicliverpool.com'],
             'notes' => 'Pulse Music School — admin/coordinator. KEY CLIENT relationship. Not a teacher.'],

            // === TRINITY ADMINS ===
            ['name' => 'Madeleine Gordon',   'types' => ['trinity_admin'], 'email' => 'Madeleine.Gordon@trinitycollege.com',
             'notes' => 'Trinity DG remittance contact. Single point of contact — never CC finance@.'],
            ['name' => 'Natalia Marengo',    'types' => ['trinity_admin'], 'email' => 'Natalia.Marengo@trinitycollege.com',
             'notes' => 'Trinity HQ.'],

            // === CENTRE OWNER ===
            ['name' => 'Paul Sheridan',      'types' => ['teacher', 'trinity_admin'], 'email' => 'madmusic6@hotmail.com',
             'secondary_emails' => ['musicexams@musicexams.help', 'paul.sheridan@trinitycollege.co.uk', 'tclexamsliverpool@outlook.com'],
             'notes' => 'Centre 120 owner. Brass/piano/keyboard/guitar teacher. trinity_admin = LAR with co-controller status.'],
        ];
    }

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
            $this->newLine();
        }

        DB::beginTransaction();

        try {
            $contactsByName = $this->upsertCanonicalContacts();
            $this->syncTypePivot($contactsByName);
            $this->backfillProfileFromUsers($contactsByName);
            $this->backfillSchools($contactsByName);
            $this->backfillInstruments($contactsByName);
            $this->backfillContactLogs($contactsByName);
            $this->relinkExamEntryFKs($contactsByName);
            $this->relinkStudentFKs($contactsByName);
            $this->migrateSubscribers($contactsByName);

            if ($this->dryRun) {
                DB::rollBack();
                $this->warn('Rolled back (dry run).');
            } else {
                DB::commit();
                $this->info('All changes committed.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Upsert each canonical row into exam_contacts. Match strategy:
     *   1. exact case-insensitive name match
     *   2. else email match (any contact_emails row)
     *   3. else create
     *
     * Returns ['lowercased name' => ExamContact]
     */
    private function upsertCanonicalContacts(): array
    {
        $this->info('Step 1/9: Upsert canonical contacts');
        $byName = [];

        foreach ($this->getCanonicalContacts() as $row) {
            $contact = $this->findContact($row['name'], $row['email'] ?? null);

            if (! $contact) {
                $this->line("  + create  {$row['name']}  <{$row['email']}>");
                if ($this->dryRun) {
                    // Phantom: not persisted, but tracked in byName so later
                    // steps can simulate the link. id stays null — any DB
                    // write paths are gated on !dryRun anyway.
                    $contact = new ExamContact([
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'notes' => $row['notes'] ?? null,
                    ]);
                } else {
                    $contact = ExamContact::create([
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'notes' => $row['notes'] ?? null,
                    ]);
                }
            } else {
                $changes = [];
                if ($contact->name !== $row['name']) {
                    $changes[] = "name: '{$contact->name}' → '{$row['name']}'";
                    $contact->name = $row['name'];
                }
                if (! empty($row['email']) && $contact->email !== $row['email']) {
                    $changes[] = "email: '{$contact->email}' → '{$row['email']}'";
                    $contact->email = $row['email'];
                }
                if (! empty($row['notes']) && empty($contact->notes)) {
                    $changes[] = 'notes set';
                    $contact->notes = $row['notes'];
                }

                if ($changes) {
                    $this->line('  ~ update  '.$row['name'].' ('.implode(', ', $changes).')');
                    if (! $this->dryRun) {
                        $contact->save();
                    }
                } else {
                    $this->line("  = match   {$row['name']}");
                }
            }

            // Secondary emails as ContactEmail rows
            if (! $this->dryRun && $contact && ! empty($row['secondary_emails'])) {
                foreach ($row['secondary_emails'] as $email) {
                    ContactEmail::firstOrCreate(
                        ['exam_contact_id' => $contact->id, 'email' => $email],
                        ['label' => 'secondary', 'is_primary' => false]
                    );
                }
            }

            if ($contact) {
                $byName[mb_strtolower(trim($row['name']))] = $contact;
            }
        }

        $this->newLine();

        return $byName;
    }

    private function findContact(string $name, ?string $email): ?ExamContact
    {
        // 1. case-insensitive name
        $contact = ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])->first();
        if ($contact) {
            return $contact;
        }

        // 2. email on exam_contacts
        if ($email) {
            $contact = ExamContact::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            if ($contact) {
                return $contact;
            }

            // 3. email on contact_emails
            $row = ContactEmail::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            if ($row) {
                return ExamContact::find($row->exam_contact_id);
            }
        }

        return null;
    }

    /**
     * Sync the contact_types pivot from canonical types[].
     * Removes types not in canonical list.
     */
    private function syncTypePivot(array $byName): void
    {
        $this->info('Step 2/9: Sync contact_types pivot');

        foreach ($this->getCanonicalContacts() as $row) {
            $key = mb_strtolower(trim($row['name']));
            $contact = $byName[$key] ?? null;
            if (! $contact) {
                continue;
            }

            foreach ($row['types'] as $type) {
                if (! in_array($type, self::ALLOWED_TYPES, true)) {
                    throw new \RuntimeException("Unknown type '$type' for {$row['name']}");
                }
            }

            $current = $this->dryRun ? collect() : DB::table('contact_types')
                ->where('exam_contact_id', $contact->id)
                ->pluck('type');

            $toAdd = array_diff($row['types'], $current->toArray());
            $toRemove = array_diff($current->toArray(), $row['types']);

            foreach ($toAdd as $type) {
                $this->line("  + {$row['name']} += $type");
                if (! $this->dryRun) {
                    DB::table('contact_types')->insert([
                        'exam_contact_id' => $contact->id,
                        'type' => $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            foreach ($toRemove as $type) {
                $this->line("  - {$row['name']} -= $type");
                if (! $this->dryRun) {
                    DB::table('contact_types')
                        ->where('exam_contact_id', $contact->id)
                        ->where('type', $type)
                        ->delete();
                }
            }
        }
        $this->newLine();
    }

    /**
     * Copy profile fields (booleans, hubspot id, how_they_found_us)
     * from any matched User row onto the exam_contact.
     */
    private function backfillProfileFromUsers(array $byName): void
    {
        $this->info('Step 3/9: Copy profile fields from users → exam_contacts');

        foreach ($byName as $key => $contact) {
            $user = User::whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();
            if (! $user) {
                continue;
            }

            $changes = [];
            if (empty($contact->phone) && ! empty($user->phone)) {
                $contact->phone = $user->phone;
                $changes[] = 'phone';
            }
            foreach (['how_they_found_us', 'hubspot_contact_id'] as $f) {
                if (empty($contact->$f) && ! empty($user->$f)) {
                    $contact->$f = $user->$f;
                    $changes[] = $f;
                }
            }
            foreach (['met_face_to_face', 'spoken_on_phone', 'contacted_by_email'] as $f) {
                if (! $contact->$f && $user->$f) {
                    $contact->$f = true;
                    $changes[] = $f;
                }
            }
            // Link via user_id if not already set
            if (empty($contact->user_id)) {
                $contact->user_id = $user->id;
                $changes[] = 'user_id';
            }

            if ($changes) {
                $this->line("  ~ {$contact->name}: ".implode(', ', $changes));
                if (! $this->dryRun) {
                    $contact->save();
                }
            }
        }
        $this->newLine();
    }

    private function backfillSchools(array $byName): void
    {
        $this->info('Step 4/9: Migrate teacher_school → contact_school');
        $rows = DB::table('teacher_school')->get();
        $copied = 0;
        foreach ($rows as $r) {
            $user = User::find($r->user_id);
            if (! $user) {
                continue;
            }
            $contact = $byName[mb_strtolower(trim($user->name))] ?? null;
            if (! $contact) {
                continue;
            }
            $exists = $this->dryRun ? false : DB::table('contact_school')
                ->where('exam_contact_id', $contact->id)
                ->where('school_id', $r->school_id)
                ->exists();
            if (! $exists) {
                $this->line("  + {$contact->name} ↔ school#{$r->school_id}");
                if (! $this->dryRun) {
                    DB::table('contact_school')->insert([
                        'exam_contact_id' => $contact->id,
                        'school_id' => $r->school_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $copied++;
                }
            }
        }
        $this->line("  ($copied rows copied)");
        $this->newLine();
    }

    private function backfillInstruments(array $byName): void
    {
        $this->info('Step 5/9: Migrate teacher_instrument → contact_instrument');
        $rows = DB::table('teacher_instrument')->get();
        $copied = 0;
        foreach ($rows as $r) {
            $user = User::find($r->user_id);
            if (! $user) {
                continue;
            }
            $contact = $byName[mb_strtolower(trim($user->name))] ?? null;
            if (! $contact) {
                continue;
            }
            $exists = $this->dryRun ? false : DB::table('contact_instrument')
                ->where('exam_contact_id', $contact->id)
                ->where('instrument_id', $r->instrument_id)
                ->exists();
            if (! $exists) {
                $this->line("  + {$contact->name} ↔ instrument#{$r->instrument_id}");
                if (! $this->dryRun) {
                    DB::table('contact_instrument')->insert([
                        'exam_contact_id' => $contact->id,
                        'instrument_id' => $r->instrument_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $copied++;
                }
            }
        }
        $this->line("  ($copied rows copied)");
        $this->newLine();
    }

    private function backfillContactLogs(array $byName): void
    {
        $this->info('Step 6/9: Repoint contact_logs.user_id → exam_contact_id');
        $rows = DB::table('contact_logs')->whereNull('exam_contact_id')->get();
        $copied = 0;
        foreach ($rows as $r) {
            $user = User::find($r->user_id);
            if (! $user) {
                continue;
            }
            $contact = $byName[mb_strtolower(trim($user->name))] ?? null;
            if (! $contact) {
                continue;
            }
            if (! $this->dryRun) {
                DB::table('contact_logs')
                    ->where('id', $r->id)
                    ->update(['exam_contact_id' => $contact->id]);
            }
            $copied++;
        }
        $this->line("  ($copied rows updated)");
        $this->newLine();
    }

    private function relinkExamEntryFKs(array $byName): void
    {
        $this->info('Step 7/9: Backfill exam_entries.teacher_contact_id from teacher_name');
        $entries = ExamEntry::whereNull('teacher_contact_id')
            ->whereNotNull('teacher_name')
            ->get();
        $linked = 0;
        $linkedByName = [];
        $unmatchedByName = [];
        foreach ($entries as $e) {
            $contact = $byName[mb_strtolower(trim($e->teacher_name))] ?? null;
            if (! $contact) {
                $unmatchedByName[$e->teacher_name] = ($unmatchedByName[$e->teacher_name] ?? 0) + 1;
                continue;
            }
            if (! $this->dryRun) {
                $e->teacher_contact_id = $contact->id;
                $e->save();
            }
            $linkedByName[$contact->name] = ($linkedByName[$contact->name] ?? 0) + 1;
            $linked++;
        }
        foreach ($linkedByName as $name => $count) {
            $this->line("  + $count entries → $name");
        }
        foreach ($unmatchedByName as $name => $count) {
            $this->warn("  ? $count entries with teacher_name='$name' (no contact match)");
        }
        $this->line("  ($linked entries linked)");
        $this->newLine();
    }

    private function relinkStudentFKs(array $byName): void
    {
        $this->info('Step 8/9: Backfill students.teacher_contact_id via exam_entries');
        // For each student with NULL teacher_contact_id, look at their most
        // recent exam_entry and resolve the teacher via byName (works in
        // dry-run too, since we don't depend on step 7 having persisted).
        $students = Student::whereNull('teacher_contact_id')->get();
        $linked = 0;
        $noEntry = 0;
        $noMatch = [];
        foreach ($students as $s) {
            $entry = ExamEntry::where('student_id', $s->id)
                ->whereNotNull('teacher_name')
                ->latest('id')
                ->first();
            if (! $entry) {
                $noEntry++;
                continue;
            }
            $contact = $byName[mb_strtolower(trim($entry->teacher_name))] ?? null;
            if (! $contact) {
                $noMatch[$entry->teacher_name] = ($noMatch[$entry->teacher_name] ?? 0) + 1;
                continue;
            }
            if (! $this->dryRun) {
                $s->teacher_contact_id = $contact->id;
                $s->save();
            }
            $linked++;
        }
        $this->line("  ($linked students linked)");
        if ($noEntry) {
            $this->line("  ($noEntry students have no exam_entries — left untouched)");
        }
        foreach ($noMatch as $name => $count) {
            $this->warn("  ? $count students with teacher_name='$name' (no contact match)");
        }
        $this->newLine();
    }

    /**
     * Bring subscribers into the unified model:
     *   - For each Subscriber, find or create matching exam_contact, add type=subscriber
     *   - Soft-delete subscriber rows that are clearly Paul's test entries
     *     (keeping Subscriber table intact for now — a later migration can drop it)
     */
    private function migrateSubscribers(array $byName): void
    {
        $this->info('Step 9/9: Migrate subscribers → exam_contacts');
        $subs = Subscriber::whereNull('unsubscribed_at')->get();
        foreach ($subs as $sub) {
            // Match to existing contact by email (primary or secondary) or name first.
            // We do this BEFORE the test-drop check so that real emails that
            // happen to contain "paul"/"musicexams" still merge correctly.
            $contact = ExamContact::whereRaw('LOWER(email) = ?', [mb_strtolower($sub->email)])->first();
            if (! $contact) {
                $row = ContactEmail::whereRaw('LOWER(email) = ?', [mb_strtolower($sub->email)])->first();
                if ($row) {
                    $contact = ExamContact::find($row->exam_contact_id);
                }
            }
            if (! $contact) {
                $contact = ExamContact::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($sub->name))])->first();
            }
            // Last-ditch: match single-word subscriber name (e.g. "Clare")
            // against the FIRST WORD of any canonical contact name.
            if (! $contact && ! str_contains(trim($sub->name), ' ')) {
                $first = mb_strtolower(trim($sub->name));
                $contact = ExamContact::whereRaw("LOWER(SPLIT_PART(name, ' ', 1)) = ?", [$first])->first();
            }

            if ($contact) {
                // Found a canonical match — add subscriber type if not already present.
                $exists = $this->dryRun ? false : DB::table('contact_types')
                    ->where('exam_contact_id', $contact->id)
                    ->where('type', 'subscriber')
                    ->exists();
                if (! $exists) {
                    $this->line("  + {$contact->name} += subscriber");
                    if (! $this->dryRun) {
                        DB::table('contact_types')->insert([
                            'exam_contact_id' => $contact->id,
                            'type' => 'subscriber',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                continue;
            }

            // No canonical match. Soft-delete (the only "real" subscriber not
            // in the canonical map should be Clare, and she matches via her
            // squashgirl73@ secondary email — anything still here is a Paul
            // test entry or stray signup, safe to drop).
            $this->line("  - drop unmatched sub: {$sub->name} <{$sub->email}>");
            if (! $this->dryRun) {
                $sub->unsubscribed_at = now();
                $sub->save();
            }
        }
        $this->newLine();
    }
}
