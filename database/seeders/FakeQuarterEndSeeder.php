<?php

namespace Database\Seeders;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic Q1–Q3 2026 data so both the Quarter End page and the
 * Certificate Generator page have varied per-quarter content.
 *
 * Candidate-count plan per (teacher × quarter) — chosen to exercise every
 * badge tier at least once, and to include teachers who only appear in
 * some quarters:
 *
 *   Teacher                Q1   Q2   Q3    (tier per quarter)
 *   Christopher Callaway   42   18    8    (Top   / Bronze / —)
 *   Jenny Capstick         32   22    0    (Gold  / Silver / —)
 *   Jennifer Hynes         22    8    5    (Silver/ —      / —)
 *   Rachel Jones           15   12    0    (Bronze/ Bronze / —)
 *   Megan Price            12    0   11    (Bronze/ —      / Bronze)
 *   Helen Khoo             10    6    0    (Bronze/ —      / —)
 *   Alexandra Bibby         8   14    0    (—     / Bronze / —)
 *   Stephen Shotton         5    3   13    (—     / —      / Bronze)
 *   Tracey LEA              3    0   18    (—     / —      / Bronze)
 *
 * Safe to re-run — everything is tagged with `trinity_order_number`
 * starting `FAKEQE-` and cleaned up at the top of every run.
 *
 * Usage: sail artisan db:seed --class=FakeQuarterEndSeeder
 */
class FakeQuarterEndSeeder extends Seeder
{
    private const TAG = 'FAKEQE-';

    public function run(): void
    {
        // ────────────── SAFETY GUARD ──────────────
        if (app()->environment('production')) {
            $this->command->error('Refusing to run FakeQuarterEndSeeder in production.');
            return;
        }

        $this->cleanup();

        $instruments = Instrument::all();
        if ($instruments->isEmpty()) {
            $this->command->warn('No instruments seeded — run LookupSeeder first.');
            return;
        }

        // ────────────── TEACHERS + QUARTERLY TARGETS ──────────────
        // Each entry = [name, email, Q1 target, Q2 target, Q3 target].
        $teachersData = [
            ['Christopher Callaway', 'fakeqe.callaway@example.com', 42, 18,  8],
            ['Jenny Capstick',       'fakeqe.capstick@example.com', 32, 22,  0],
            ['Jennifer Hynes',       'fakeqe.hynes@example.com',    22,  8,  5],
            ['Rachel Jones',         'fakeqe.rjones@example.com',   15, 12,  0],
            ['Megan Price',          'fakeqe.price@example.com',    12,  0, 11],
            ['Helen Khoo',           'fakeqe.khoo@example.com',     10,  6,  0],
            ['Alexandra Bibby',      'fakeqe.bibby@example.com',     8, 14,  0],
            ['Stephen Shotton',      'fakeqe.shotton@example.com',   5,  3, 13],
            ['Tracey LEA',           'fakeqe.lea@example.com',       3,  0, 18],
        ];

        $teachers = collect();
        foreach ($teachersData as [$name, $email, $q1, $q2, $q3]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'role' => 'teacher',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $teachers->push(['user' => $user, 'targets' => [1 => $q1, 2 => $q2, 3 => $q3]]);
        }

        // ────────────── ORDERS + ENTRIES ──────────────
        $grades = ['Initial', '1', '2', '3', '4', '5', '6', '7', '8'];
        $firstNames = ['Oliver', 'Amelia', 'Harry', 'Isla', 'Jack', 'Ava', 'George', 'Mia', 'Noah', 'Isabella', 'Leo', 'Sophia', 'Arthur', 'Grace', 'Lily', 'Oscar', 'Freya', 'Charlie', 'Emily', 'Henry', 'Ella', 'Thomas', 'Rosie', 'Alfie', 'Florence', 'Poppy', 'Willow', 'Sienna', 'Phoebe', 'Evie'];
        $lastNames = ['Smith', 'Jones', 'Williams', 'Taylor', 'Brown', 'Davies', 'Evans', 'Wilson', 'Thomas', 'Roberts', 'Johnson', 'Lewis', 'Walker', 'Robinson', 'Wood', 'Thompson', 'White', 'Watson', 'Jackson', 'Wright'];

        $orderCounter = 1;
        $totalEntries = 0;

        foreach ($teachers as $row) {
            $teacher = $row['user'];

            foreach ($row['targets'] as $quarter => $targetCandidates) {
                if ($targetCandidates === 0) {
                    continue;
                }

                // Break target into orders of 3-5 candidates each
                $remaining = $targetCandidates;
                while ($remaining > 0) {
                    $candidates = min($remaining, rand(3, 5));
                    $remaining -= $candidates;

                    $isDigital = (bool) rand(0, 1);
                    $deliveryMethod = $isDigital ? 'Digital' : 'Default';
                    $commissionRate = $isDigital ? 20.00 : 28.00;

                    $startMonth = (($quarter - 1) * 3) + 1;
                    $month = $startMonth + rand(0, 2);
                    $day = rand(1, 28);
                    $examDate = Carbon::create(2026, $month, $day);

                    $order = Order::create([
                        'user_id' => $teacher->id,
                        'trinity_order_number' => self::TAG . str_pad($orderCounter++, 4, '0', STR_PAD_LEFT),
                        'delivery_method' => $deliveryMethod,
                        'subject_area' => 'Music',
                        'candidates' => $candidates,
                        'venue' => $isDigital ? 'Online (Digital)' : 'Birkenhead Studio',
                        'order_status' => 'Completed',
                        'requested_start_date' => $examDate->toDateString(),
                        'commission_rate' => $commissionRate,
                        'commission_amount' => round(40 * $candidates * ($commissionRate / 100), 2),
                        'applicant_name' => $teacher->name,
                        'applicant_email' => $teacher->email,
                    ]);

                    for ($c = 0; $c < $candidates; $c++) {
                        $firstName = $firstNames[array_rand($firstNames)];
                        $lastName = $lastNames[array_rand($lastNames)];
                        $instrument = $instruments->random();

                        // 70% scored, 25% pending, 5% cancelled
                        $roll = rand(1, 100);
                        $score = null;
                        $notes = null;

                        if ($roll <= 5) {
                            $notes = 'CANCELLED';
                        } elseif ($roll <= 30) {
                            $score = null;
                        } else {
                            $band = rand(1, 100);
                            if ($band <= 25) {
                                $score = rand(87, 96);   // Distinction
                            } elseif ($band <= 65) {
                                $score = rand(75, 86);   // Merit
                            } else {
                                $score = rand(60, 74);   // Pass
                            }
                        }

                        ExamEntry::create([
                            'order_id' => $order->id,
                            'instrument_id' => $instrument->id,
                            'candidate_name' => "{$firstName} {$lastName}",
                            'teacher_name' => $teacher->name,
                            'grade' => $grades[array_rand($grades)],
                            'subject_area' => 'Music',
                            'delivery_method' => $deliveryMethod,
                            'score' => $score,
                            'fee' => rand(35, 65),
                            'exam_date' => $examDate->toDateString(),
                            'notes' => $notes,
                            'show_on_thank_you' => true,
                        ]);

                        $totalEntries++;
                    }
                }
            }
        }

        // ────────────── PARENT + SELF BOOKINGS ──────────────
        // Mirrors the real Q1 shape. Parents book directly for their one
        // child; self-applicants (like adult learners or mature teenagers)
        // book their own exam. Each gets an ExamContact with the right role
        // so the admin distinguishes them.
        $parentBookings = [
            ['name' => 'Gillian Leslie',    'role' => 'parent', 'email' => 'fakeqe.gillian@example.com',  'child' => 'Jacob Leslie',        'instrument' => 'Guitar (Rock/Pop)',  'grade' => 'Initial', 'score' => 76],
            ['name' => 'Adrian O\'Malley',  'role' => 'parent', 'email' => 'fakeqe.adrian@example.com',   'child' => 'Jasper O\'Malley',    'instrument' => 'Guitar (Rock/Pop)',  'grade' => '8',       'score' => 75],
            ['name' => 'Claire Reed',       'role' => 'parent', 'email' => 'fakeqe.claire@example.com',   'child' => 'Jemima Reed',         'instrument' => 'Singing (Rock/Pop)', 'grade' => '5',       'score' => 70],
            // Self-applicant — candidate booked their own exam. Their name
            // is both the applicant AND the child in the entry.
            ['name' => 'Seth Barraclough',  'role' => 'self',   'email' => 'fakeqe.seth@example.com',     'child' => 'Seth Barraclough',    'instrument' => 'Trombone',           'grade' => '8',       'score' => 93],
        ];

        foreach ($parentBookings as $p) {
            // Phase D-3: exam_contacts.role gone — types now live on the
            // contact_types pivot. 'self' maps to 'candidate' on the unified
            // model; 'parent' stays as is.
            $type = $p['role'] === 'self' ? 'candidate' : $p['role'];
            $contact = ExamContact::updateOrCreate(
                ['name' => $p['name']],
                ['email' => $p['email']]
            );
            $contact->addType($type);

            $instrument = Instrument::where('name', $p['instrument'])->first();
            if (! $instrument) {
                continue; // LookupSeeder hasn't been re-run
            }

            $examDate = Carbon::create(2026, 3, rand(1, 28));

            $order = Order::create([
                'user_id' => $teachers->first()['user']->id, // any user; real prod sets Paul
                'trinity_order_number' => self::TAG . 'P' . str_pad($orderCounter++, 3, '0', STR_PAD_LEFT),
                'delivery_method' => 'Default',
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'venue' => 'Learn Music Ltd',
                'order_status' => 'Completed',
                'requested_start_date' => $examDate->toDateString(),
                'commission_rate' => 28.00,
                'commission_amount' => round(40 * 0.28, 2),
                'applicant_name' => $p['name'],
                'applicant_email' => $p['email'],
            ]);

            ExamEntry::create([
                'order_id' => $order->id,
                'instrument_id' => $instrument->id,
                'candidate_name' => $p['child'],
                'teacher_name' => $p['name'], // stamped as the applicant, just like Trinity does
                'grade' => $p['grade'],
                'subject_area' => 'Rock and Pop',
                'delivery_method' => 'Default',
                'score' => $p['score'],
                'fee' => 40,
                'exam_date' => $examDate->toDateString(),
                'show_on_thank_you' => true,
            ]);

            $totalEntries++;
        }

        // ────────────── EMAIL TRACKING (mark a few as sent) ──────────────
        // 2 teachers in Q1, 1 teacher in Q2 — so both states visible on both quarters.
        $emailSent = [
            ['Christopher Callaway', 1],
            ['Jenny Capstick', 1],
            ['Alexandra Bibby', 2],
        ];
        foreach ($emailSent as [$name, $quarter]) {
            DB::table('quarter_end_email_tracking')->updateOrInsert(
                ['teacher_name' => $name, 'quarter' => $quarter, 'year' => 2026],
                [
                    'email_sent' => true,
                    'sent_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info("Seeded {$teachers->count()} teachers, {$totalEntries} entries across Q1–Q3 2026.");
        $this->command->info('Try /admin/certificates and /admin/quarter-end with each of quarter=1, 2, 3.');
    }

    /**
     * Remove everything this seeder previously created, so reruns start clean.
     */
    private function cleanup(): void
    {
        $orderIds = Order::where('trinity_order_number', 'like', self::TAG . '%')->pluck('id');

        if ($orderIds->isNotEmpty()) {
            ExamEntry::whereIn('order_id', $orderIds)->delete();
            Order::whereIn('id', $orderIds)->forceDelete();
        }

        // User uses SoftDeletes — must force-delete (including any trashed
        // rows from prior runs) or the unique email constraint will collide
        // on the next updateOrCreate.
        User::withTrashed()
            ->where('email', 'like', 'fakeqe.%@example.com')
            ->forceDelete();

        // Fake parent ExamContacts (same suffix convention on email)
        ExamContact::withTrashed()
            ->where('email', 'like', 'fakeqe.%@example.com')
            ->forceDelete();

        DB::table('quarter_end_email_tracking')
            ->where('year', 2026)
            ->whereIn('quarter', [1, 2, 3])
            ->whereIn('teacher_name', [
                'Christopher Callaway', 'Jenny Capstick', 'Jennifer Hynes',
                'Rachel Jones', 'Megan Price', 'Helen Khoo', 'Alexandra Bibby',
                'Stephen Shotton', 'Tracey LEA',
            ])
            ->delete();
    }
}
