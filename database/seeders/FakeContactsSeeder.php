<?php

// database/seeders/FakeContactsSeeder.php

namespace Database\Seeders;

use App\Models\ContactEmail;
use App\Models\ExamContact;
use Illuminate\Database\Seeder;

/**
 * Purely additive local-dev seeder for ExamContacts.
 *
 * Safe to re-run: deletes only contacts previously created by this
 * seeder (source = 'fake_seed') before inserting afresh. Does not
 * touch any production-imported contacts.
 *
 * Usage:
 *   sail artisan db:seed --class=FakeContactsSeeder
 */
class FakeContactsSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up prior runs — only our own fake rows.
        ExamContact::where('source', 'fake_seed')->forceDelete();

        $contacts = [
            // ── Teachers ─────────────────────────────
            ['name' => 'Sarah Mitchell',   'email' => 'sarah.mitchell@example.com', 'phone' => '07700 200101', 'role' => 'teacher', 'notes' => 'Piano, Grade 1-5. Regular submissions.'],
            ['name' => 'James Cooper',     'email' => 'james.cooper@example.com',   'phone' => '07700 200102', 'role' => 'teacher', 'notes' => 'Brass specialist.'],
            ['name' => 'Emma Richardson',  'email' => 'emma.r@example.com',         'phone' => '07700 200103', 'role' => 'teacher', 'notes' => null],
            ['name' => 'David Chen',       'email' => 'david.chen@example.com',     'phone' => '07700 200104', 'role' => 'teacher', 'notes' => 'Guitar Rock & Pop.'],
            ['name' => 'Helen Wright',     'email' => 'helen.wright@example.com',   'phone' => '07700 200105', 'role' => 'teacher', 'notes' => null],
            ['name' => 'Tom Bradley',      'email' => 'tom.bradley@example.com',    'phone' => '07700 200106', 'role' => 'teacher', 'notes' => 'Drums.'],
            ['name' => 'Rachel Green',     'email' => 'rachel.green@example.com',   'phone' => null,           'role' => 'teacher', 'notes' => null],
            ['name' => 'Mark Johnson',     'email' => 'mark.j@example.com',         'phone' => '07700 200108', 'role' => 'teacher', 'notes' => null],

            // ── Parents (the ones we want to EXCLUDE from teacher prize draw) ──
            ['name' => 'Seth Barraclough', 'email' => 'seth.barr@example.com',      'phone' => '07700 300201', 'role' => 'parent',  'notes' => 'Parent/self — Seth James Barraclough (ambiguous — flagged for review).'],
            ['name' => 'Helen Khoo',       'email' => 'h.khoo@example.com',         'phone' => '07700 300202', 'role' => 'parent',  'notes' => 'Mum of Evie Khoo (Grade 2 piano).'],
            ['name' => 'Claire Reed',      'email' => 'claire.reed@example.com',    'phone' => '07700 300203', 'role' => 'parent',  'notes' => 'Parent of two candidates.'],
            ['name' => 'Martin Evans',     'email' => 'martin.evans@example.com',   'phone' => null,           'role' => 'parent',  'notes' => null],
            ['name' => 'Priya Sharma',     'email' => 'priya.sharma@example.com',   'phone' => '07700 300205', 'role' => 'parent',  'notes' => null],

            // ── Self-entry adult candidates ──────────
            ['name' => 'Olivia Ashworth',  'email' => 'olivia.ash@example.com',     'phone' => '07700 400301', 'role' => 'self',    'notes' => 'Adult learner — piano Grade 5.'],
            ['name' => 'Daniel Park',      'email' => 'daniel.park@example.com',    'phone' => null,           'role' => 'self',    'notes' => 'Self-entry, guitar Initial.'],

            // ── Applicants (submitter role, not classified yet) ─
            ['name' => 'Jo Taylor',        'email' => 'jo.taylor@example.com',      'phone' => '07700 500401', 'role' => 'applicant', 'notes' => 'Submitted Hillside order — needs classification.'],
            ['name' => 'Chris Brown',      'email' => 'chris.brown@example.com',    'phone' => null,           'role' => 'applicant', 'notes' => null],

            // ── Unknown / unclassified ────────────────
            ['name' => 'P Singh',          'email' => null,                         'phone' => '07700 600501', 'role' => 'unknown', 'notes' => 'Only phone captured at event.'],
            ['name' => 'Alex Yu',          'email' => 'alex.yu@example.com',        'phone' => null,           'role' => 'unknown', 'notes' => 'From Trinity event signup — role TBC.'],

            // ── A site admin contact (for sidebar-filter testing) ─
            ['name' => 'Paul Sheridan (LAR)', 'email' => 'musicexams@musicexams.help', 'phone' => '07700 700601', 'role' => 'admin', 'notes' => 'LAR/centre admin.'],
        ];

        foreach ($contacts as $row) {
            ExamContact::create(array_merge($row, ['source' => 'fake_seed']));
        }

        // ── Legacy-import edge case (mimics the Roxanne pattern) ──
        // A contact whose email lives only in the contact_emails relation,
        // not in the direct exam_contacts.email column. Used to verify the
        // Contacts index falls back through primary_email correctly.
        $legacy = ExamContact::create([
            'name'   => 'Jane Legacy (email-via-relation)',
            'email'  => null,
            'phone'  => '07700 999999',
            'role'   => 'teacher',
            'source' => 'fake_seed',
            'notes'  => 'Edge case: email only in contact_emails table, like Roxanne.',
        ]);

        ContactEmail::create([
            'exam_contact_id' => $legacy->id,
            'email'           => 'jane.legacy@example.com',
            'label'           => 'primary',
            'is_primary'      => true,
        ]);

        $this->command?->info('Seeded ' . (count($contacts) + 1) . ' fake contacts (source=fake_seed).');
    }
}
