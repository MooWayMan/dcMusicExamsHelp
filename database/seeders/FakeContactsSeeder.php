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

        // Each row is [attrs, type-list]. Types are applied via addType() so
        // they go into the contact_types pivot (single source of truth since
        // exam_contacts.role was dropped in Phase D-3, Step 6).
        // Note: 'self' / 'applicant' / 'unknown' / 'admin' weren't first-class
        // types on the unified model — they map to 'candidate' / [] / [] / [].
        $contacts = [
            // ── Teachers ─────────────────────────────
            [['name' => 'Sarah Mitchell',   'email' => 'sarah.mitchell@example.com', 'phone' => '07700 200101', 'notes' => 'Piano, Grade 1-5. Regular submissions.'], ['teacher']],
            [['name' => 'James Cooper',     'email' => 'james.cooper@example.com',   'phone' => '07700 200102', 'notes' => 'Brass specialist.'],                       ['teacher']],
            [['name' => 'Emma Richardson',  'email' => 'emma.r@example.com',         'phone' => '07700 200103', 'notes' => null],                                       ['teacher']],
            [['name' => 'David Chen',       'email' => 'david.chen@example.com',     'phone' => '07700 200104', 'notes' => 'Guitar Rock & Pop.'],                       ['teacher']],
            [['name' => 'Helen Wright',     'email' => 'helen.wright@example.com',   'phone' => '07700 200105', 'notes' => null],                                       ['teacher']],
            [['name' => 'Tom Bradley',      'email' => 'tom.bradley@example.com',    'phone' => '07700 200106', 'notes' => 'Drums.'],                                   ['teacher']],
            [['name' => 'Rachel Green',     'email' => 'rachel.green@example.com',   'phone' => null,           'notes' => null],                                       ['teacher']],
            [['name' => 'Mark Johnson',     'email' => 'mark.j@example.com',         'phone' => '07700 200108', 'notes' => null],                                       ['teacher']],

            // ── Parents (the ones we want to EXCLUDE from teacher prize draw) ──
            [['name' => 'Seth Barraclough', 'email' => 'seth.barr@example.com',      'phone' => '07700 300201', 'notes' => 'Parent/self — Seth James Barraclough (ambiguous — flagged for review).'], ['parent']],
            [['name' => 'Helen Khoo',       'email' => 'h.khoo@example.com',         'phone' => '07700 300202', 'notes' => 'Mum of Evie Khoo (Grade 2 piano).'],                                          ['parent']],
            [['name' => 'Claire Reed',      'email' => 'claire.reed@example.com',    'phone' => '07700 300203', 'notes' => 'Parent of two candidates.'],                                                  ['parent']],
            [['name' => 'Martin Evans',     'email' => 'martin.evans@example.com',   'phone' => null,           'notes' => null],                                                                          ['parent']],
            [['name' => 'Priya Sharma',     'email' => 'priya.sharma@example.com',   'phone' => '07700 300205', 'notes' => null],                                                                          ['parent']],

            // ── Self-entry adult candidates ──────────
            [['name' => 'Olivia Ashworth',  'email' => 'olivia.ash@example.com',     'phone' => '07700 400301', 'notes' => 'Adult learner — piano Grade 5.'],     ['candidate']],
            [['name' => 'Daniel Park',      'email' => 'daniel.park@example.com',    'phone' => null,           'notes' => 'Self-entry, guitar Initial.'],         ['candidate']],

            // ── Applicants (submitter — left untyped until classified) ─
            [['name' => 'Jo Taylor',        'email' => 'jo.taylor@example.com',      'phone' => '07700 500401', 'notes' => 'Submitted Hillside order — needs classification.'], []],
            [['name' => 'Chris Brown',      'email' => 'chris.brown@example.com',    'phone' => null,           'notes' => null],                                                  []],

            // ── Unknown / unclassified (no type) ─────
            [['name' => 'P Singh',          'email' => null,                         'phone' => '07700 600501', 'notes' => 'Only phone captured at event.'],            []],
            [['name' => 'Alex Yu',          'email' => 'alex.yu@example.com',        'phone' => null,           'notes' => 'From Trinity event signup — type TBC.'],    []],

            // ── A site admin contact (kept untyped — admin role no longer exists) ─
            [['name' => 'Paul Sheridan (LAR)', 'email' => 'musicexams@musicexams.help', 'phone' => '07700 700601', 'notes' => 'LAR/centre admin.'], []],
        ];

        foreach ($contacts as [$row, $types]) {
            $contact = ExamContact::create(array_merge($row, ['source' => 'fake_seed']));
            foreach ($types as $type) {
                $contact->addType($type);
            }
        }

        // ── Legacy-import edge case (mimics the Roxanne pattern) ──
        // A contact whose email lives only in the contact_emails relation,
        // not in the direct exam_contacts.email column. Used to verify the
        // Contacts index falls back through primary_email correctly.
        $legacy = ExamContact::create([
            'name'   => 'Jane Legacy (email-via-relation)',
            'email'  => null,
            'phone'  => '07700 999999',
            'source' => 'fake_seed',
            'notes'  => 'Edge case: email only in contact_emails table, like Roxanne.',
        ]);
        $legacy->addType('teacher');

        ContactEmail::create([
            'exam_contact_id' => $legacy->id,
            'email'           => 'jane.legacy@example.com',
            'label'           => 'primary',
            'is_primary'      => true,
        ]);

        $this->command?->info('Seeded ' . (count($contacts) + 1) . ' fake contacts (source=fake_seed).');
    }
}
