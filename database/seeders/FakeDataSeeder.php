<?php

// database/seeders/FakeDataSeeder.php

namespace Database\Seeders;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\OrderContact;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    /**
     * Seed the database with realistic fake data for the admin panel.
     */
    public function run(): void
    {
        $instruments = Instrument::all();
        // Subject-area variety previously came from the SubjectArea model;
        // since the dropped teacher_subject_area pivot used a small fixed list,
        // we hard-code those names here for fake-order generation.
        $subjectAreaNames = ['Classical & Jazz', 'Rock & Pop'];

        // ──────────────────────────────────────────
        // Schools
        // ──────────────────────────────────────────
        $schoolsData = [
            ['name' => 'Birkenhead School', 'address' => '58 Beresford Road', 'city' => 'Birkenhead', 'postcode' => 'CH43 2JD'],
            ['name' => 'Calday Grange Grammar School', 'address' => 'Grammar School Lane', 'city' => 'West Kirby', 'postcode' => 'CH48 8GG'],
            ['name' => 'Wirral Grammar School for Boys', 'address' => 'Cross Lane', 'city' => 'Bebington', 'postcode' => 'CH63 3AQ'],
            ['name' => 'Upton Hall School', 'address' => 'Moreton Road', 'city' => 'Upton', 'postcode' => 'CH49 6LJ'],
            ['name' => 'West Kirby Residential School', 'address' => 'Meols Drive', 'city' => 'West Kirby', 'postcode' => 'CH48 5DH'],
            ['name' => 'St Anselm\'s College', 'address' => 'Manor Hill', 'city' => 'Birkenhead', 'postcode' => 'CH43 1UQ'],
            ['name' => 'Hilbre High School', 'address' => 'Frankby Road', 'city' => 'West Kirby', 'postcode' => 'CH48 6EQ'],
            ['name' => 'Prenton High School', 'address' => 'Christchurch Road', 'city' => 'Prenton', 'postcode' => 'CH43 5RE'],
        ];

        $schools = collect();
        foreach ($schoolsData as $data) {
            $schools->push(School::create($data));
        }

        // ──────────────────────────────────────────
        // Teachers (15 fake teachers)
        // ──────────────────────────────────────────
        $teachersData = [
            ['name' => 'Sarah Mitchell', 'email' => 'sarah.mitchell@example.com', 'phone' => '07700 100201', 'how_they_found_us' => 'Trinity website', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'James Cooper', 'email' => 'james.cooper@example.com', 'phone' => '07700 100202', 'how_they_found_us' => 'Word of mouth', 'met_face_to_face' => false, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Emma Richardson', 'email' => 'emma.r@example.com', 'phone' => '07700 100203', 'how_they_found_us' => 'Google search', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'David Chen', 'email' => 'david.chen@example.com', 'phone' => '07700 100204', 'how_they_found_us' => 'School referral', 'met_face_to_face' => false, 'spoken_on_phone' => false, 'contacted_by_email' => true],
            ['name' => 'Helen Wright', 'email' => 'helen.wright@example.com', 'phone' => '07700 100205', 'how_they_found_us' => 'Trinity event', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Tom Bradley', 'email' => 'tom.bradley@example.com', 'phone' => '07700 100206', 'how_they_found_us' => 'Tafelmusik', 'met_face_to_face' => true, 'spoken_on_phone' => false, 'contacted_by_email' => true],
            ['name' => 'Rachel Green', 'email' => 'rachel.green@example.com', 'phone' => '07700 100207', 'how_they_found_us' => 'Facebook', 'met_face_to_face' => false, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Mark Johnson', 'email' => 'mark.j@example.com', 'phone' => '07700 100208', 'how_they_found_us' => 'Word of mouth', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Lucy Appleton', 'email' => 'lucy.appleton@example.com', 'phone' => '07700 100209', 'how_they_found_us' => 'Trinity website', 'met_face_to_face' => false, 'spoken_on_phone' => false, 'contacted_by_email' => true],
            ['name' => 'Andrew Foster', 'email' => 'andrew.foster@example.com', 'phone' => '07700 100210', 'how_they_found_us' => 'Google search', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Karen Doyle', 'email' => 'karen.doyle@example.com', 'phone' => '07700 100211', 'how_they_found_us' => 'School referral', 'met_face_to_face' => false, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Peter Shaw', 'email' => 'peter.shaw@example.com', 'phone' => '07700 100212', 'how_they_found_us' => 'Existing teacher referral', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
            ['name' => 'Natalie Wong', 'email' => 'natalie.wong@example.com', 'phone' => '07700 100213', 'how_they_found_us' => 'Trinity event', 'met_face_to_face' => true, 'spoken_on_phone' => false, 'contacted_by_email' => true],
            ['name' => 'Chris Barlow', 'email' => 'chris.barlow@example.com', 'phone' => '07700 100214', 'how_they_found_us' => 'Instagram', 'met_face_to_face' => false, 'spoken_on_phone' => false, 'contacted_by_email' => true],
            ['name' => 'Fiona Kelly', 'email' => 'fiona.kelly@example.com', 'phone' => '07700 100215', 'how_they_found_us' => 'Word of mouth', 'met_face_to_face' => true, 'spoken_on_phone' => true, 'contacted_by_email' => true],
        ];

        $teachers = collect();
        foreach ($teachersData as $data) {
            $teacher = User::create(array_merge($data, [
                'role' => 'teacher',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]));
            $teachers->push($teacher);
        }

        // Mirror local data shape to prod: every teacher has a corresponding
        // ExamContact (the unified contacts model is the single source of
        // truth, so the Students Index, contact pages, prize draw, etc. all
        // resolve through ExamContact). FakeContactsSeeder runs first and
        // creates 8 teacher contacts; firstOrCreate finds those by name and
        // creates the remaining 7 fresh — net result is 15 teacher Users
        // each with a matching contact.
        $teacherContactByNameLower = [];
        foreach ($teachers as $teacherUser) {
            $contact = ExamContact::firstOrCreate(
                ['name' => $teacherUser->name],
                [
                    'email'  => $teacherUser->email,
                    'phone'  => $teacherUser->phone ?? null,
                    'source' => 'fake_seed',
                ],
            );
            if (! $contact->hasType('teacher')) {
                $contact->addType('teacher');
            }
            $teacherContactByNameLower[strtolower(trim($teacherUser->name))] = $contact;
        }

        // The legacy User-keyed pivots (teacher_school, teacher_instrument,
        // teacher_subject_area) have been dropped — the unified ExamContact
        // model owns those relations now (see FakeContactsSeeder for the
        // contact-side fixtures). For raw fake-data dev we just keep
        // teachers/students/orders without those joins.
        // Pre-pick a random instrument set per teacher purely for the
        // student/exam-entry generation below.
        $teacherInstrumentIdsByTeacher = [];
        foreach ($teachers as $teacher) {
            $teacherInstrumentIdsByTeacher[$teacher->id] = $instruments
                ->random(rand(1, 4))
                ->pluck('id')
                ->toArray();
        }

        // ──────────────────────────────────────────
        // Students (3-8 per teacher)
        // ──────────────────────────────────────────
        $firstNames = ['Oliver', 'Amelia', 'Harry', 'Isla', 'Jack', 'Ava', 'George', 'Mia', 'Noah', 'Isabella', 'Leo', 'Sophia', 'Arthur', 'Grace', 'Muhammad', 'Lily', 'Oscar', 'Freya', 'Charlie', 'Emily', 'Jacob', 'Ivy', 'Henry', 'Ella', 'Thomas', 'Rosie', 'Alfie', 'Florence', 'James', 'Poppy', 'William', 'Willow', 'Ethan', 'Sienna', 'Alexander', 'Charlotte', 'Lucas', 'Phoebe', 'Daniel', 'Evie'];
        $lastNames = ['Smith', 'Jones', 'Williams', 'Taylor', 'Brown', 'Davies', 'Evans', 'Wilson', 'Thomas', 'Roberts', 'Johnson', 'Lewis', 'Walker', 'Robinson', 'Wood', 'Thompson', 'White', 'Watson', 'Jackson', 'Wright'];

        $allStudents = collect();
        foreach ($teachers as $teacher) {
            $numStudents = rand(3, 8);
            $teacherInstrumentIds = $teacherInstrumentIdsByTeacher[$teacher->id];
            $teacherContact = $teacherContactByNameLower[strtolower(trim($teacher->name))] ?? null;

            for ($i = 0; $i < $numStudents; $i++) {
                $student = Student::create([
                    'user_id' => $teacher->id,
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name' => $lastNames[array_rand($lastNames)],
                    'email' => null,
                    // Mirror prod: students.teacher_contact_id is the canonical
                    // teacher link (was backfilled on prod 25 Apr).
                    'teacher_contact_id' => $teacherContact?->id,
                ]);
                $allStudents->push($student);
            }
        }

        // ──────────────────────────────────────────
        // Orders and Exam Entries
        // ──────────────────────────────────────────
        $grades = ['Initial', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'];
        $results = ['Pass', 'Merit', 'Distinction', null, null]; // null = not yet taken
        $orderStatuses = ['Submitted', 'Confirmed', 'Completed', 'Completed', 'Completed'];
        $venues = ['Birkenhead Studio', 'West Kirby Centre', 'Liverpool Hub', 'Online (Digital)'];
        $orderNumber = 120000;

        $schoolIds = $schools->pluck('id')->toArray();

        foreach ($teachers as $teacher) {
            $numOrders = rand(1, 4);
            // No more teacher_school pivot — just sprinkle each teacher's
            // orders across the available schools at random.
            $teacherSchoolIds = $schoolIds;
            $teacherStudents = $allStudents->where('user_id', $teacher->id);

            for ($o = 0; $o < $numOrders; $o++) {
                $orderNumber++;
                $isDigital = (bool) rand(0, 1);
                $deliveryMethod = $isDigital ? 'Digital' : 'Default';
                $commissionRate = $isDigital ? 20.00 : 28.00;
                $candidates = rand(2, 8);
                $subjectArea = $subjectAreaNames[array_rand($subjectAreaNames)];
                $status = $orderStatuses[array_rand($orderStatuses)];

                // Estimate commission: roughly £30-60 per candidate
                $feePerCandidate = rand(30, 60);
                $totalFees = $feePerCandidate * $candidates;
                $commissionAmount = round($totalFees * ($commissionRate / 100), 2);

                $teacherContact = $teacherContactByNameLower[strtolower(trim($teacher->name))] ?? null;
                $orderSchoolId = ! empty($teacherSchoolIds) ? $teacherSchoolIds[array_rand($teacherSchoolIds)] : null;
                $orderSchoolName = $orderSchoolId
                    ? optional($schools->firstWhere('id', $orderSchoolId))->name
                    : null;

                $order = Order::create([
                    'school_id' => $orderSchoolId,
                    // Canonical "who submitted this order" link (mirrors prod's
                    // unified contacts model — 25 Apr Phase B step 10 backfill).
                    'created_by_contact_id' => $teacherContact?->id,
                    'trinity_order_number' => 'TCL-' . $orderNumber,
                    'delivery_method' => $deliveryMethod,
                    'subject_area' => $subjectArea,
                    'candidates' => $candidates,
                    'venue' => $isDigital ? 'Online (Digital)' : $venues[array_rand(array_slice($venues, 0, 3))],
                    'order_status' => $status,
                    'requested_start_date' => now()->subDays(rand(0, 180))->format('Y-m-d'),
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                ]);

                // Mirror prod's order_contacts pivot — every order has a
                // teacher row with role_in_order='teacher'. The Students Index
                // teacher resolver no longer reads from this (uses the per-
                // entry FK instead) but plenty of other places still do.
                if ($teacherContact) {
                    OrderContact::create([
                        'order_id'        => $order->id,
                        'exam_contact_id' => $teacherContact->id,
                        'role_in_order'   => 'teacher',
                        'is_primary'      => true,
                    ]);
                }

                // Create exam entries for each candidate in this order. The
                // instrument is now per-entry (lives on exam_entries directly,
                // not on students), so we pick from the teacher's instrument
                // pool rather than from a single instrument on the student.
                // teacher_name + teacher_contact_id are stamped per-entry so
                // the Students Index Teacher column has something to render.
                $orderStudents = $teacherStudents->random(min($candidates, $teacherStudents->count()));
                foreach ($orderStudents as $student) {
                    ExamEntry::create([
                        'order_id' => $order->id,
                        'student_id' => $student->id,
                        // Trinity always provides a candidate_name string on
                        // the entry (separate from student_id). Mirror that on
                        // local so contact show pages have a value to display.
                        'candidate_name' => $student->full_name,
                        // Trinity-style candidate numbers (e.g. 1-15899370904).
                        'candidate_number' => '1-' . fake()->unique()->numerify('###########'),
                        'instrument_id' => $teacherInstrumentIdsByTeacher[$teacher->id][array_rand($teacherInstrumentIdsByTeacher[$teacher->id])],
                        'teacher_name' => $teacher->name,
                        'teacher_contact_id' => $teacherContact?->id,
                        // Stamp school_name so the SchoolController's derived
                        // teachers_count subquery has something to match on.
                        'school_name' => $orderSchoolName,
                        'grade' => $grades[array_rand($grades)],
                        'subject_area' => $subjectArea,
                        'delivery_method' => $deliveryMethod,
                        'result' => $status === 'Completed' ? ['Pass', 'Merit', 'Distinction'][array_rand(['Pass', 'Merit', 'Distinction'])] : null,
                        'score' => $status === 'Completed' ? rand(60, 96) : null,
                        'exam_date' => $status === 'Completed' ? now()->subDays(rand(1, 120))->format('Y-m-d') : null,
                    ]);
                }
            }
        }

        // Contact logs intentionally omitted in this seeder — they now live
        // on the unified contacts model (exam_contact_id), and FakeContactsSeeder
        // owns the contact-side fixtures.
    }
}
