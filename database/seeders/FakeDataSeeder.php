<?php

// database/seeders/FakeDataSeeder.php

namespace Database\Seeders;

use App\Models\ContactLog;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use App\Models\SubjectArea;
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
        $subjectAreas = SubjectArea::all();

        // ──────────────────────────────────────────
        // Schools
        // ──────────────────────────────────────────
        $schoolsData = [
            ['name' => 'Birkenhead School', 'address' => '58 Beresford Road', 'city' => 'Birkenhead', 'postcode' => 'CH43 2JD', 'phone' => '0151 652 4014', 'contact_name' => 'Mrs Thompson'],
            ['name' => 'Calday Grange Grammar School', 'address' => 'Grammar School Lane', 'city' => 'West Kirby', 'postcode' => 'CH48 8GG', 'phone' => '0151 625 2727', 'contact_name' => 'Mr Davies'],
            ['name' => 'Wirral Grammar School for Boys', 'address' => 'Cross Lane', 'city' => 'Bebington', 'postcode' => 'CH63 3AQ', 'phone' => '0151 644 0908', 'contact_name' => 'Ms Patel'],
            ['name' => 'Upton Hall School', 'address' => 'Moreton Road', 'city' => 'Upton', 'postcode' => 'CH49 6LJ', 'phone' => '0151 677 4015', 'contact_name' => 'Mrs O\'Brien'],
            ['name' => 'West Kirby Residential School', 'address' => 'Meols Drive', 'city' => 'West Kirby', 'postcode' => 'CH48 5DH', 'phone' => '0151 632 3201', 'contact_name' => 'Mr Singh'],
            ['name' => 'St Anselm\'s College', 'address' => 'Manor Hill', 'city' => 'Birkenhead', 'postcode' => 'CH43 1UQ', 'phone' => '0151 652 1408', 'contact_name' => 'Mrs Williams'],
            ['name' => 'Hilbre High School', 'address' => 'Frankby Road', 'city' => 'West Kirby', 'postcode' => 'CH48 6EQ', 'phone' => '0151 625 5566', 'contact_name' => 'Mr Hughes'],
            ['name' => 'Prenton High School', 'address' => 'Christchurch Road', 'city' => 'Prenton', 'postcode' => 'CH43 5RE', 'phone' => '0151 608 6414', 'contact_name' => 'Mrs Clark'],
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

        // Assign teachers to schools (1-3 schools each)
        foreach ($teachers as $teacher) {
            $teacherSchools = $schools->random(rand(1, 3));
            $teacher->schools()->attach($teacherSchools->pluck('id'));
        }

        // Assign instruments to teachers (1-4 instruments each)
        foreach ($teachers as $teacher) {
            $teacherInstruments = $instruments->random(rand(1, 4));
            $teacher->instruments()->attach($teacherInstruments->pluck('id'));
        }

        // Assign subject areas to teachers (1-2 subject areas each)
        foreach ($teachers as $teacher) {
            $teacherSubjectAreas = $subjectAreas->random(rand(1, 2));
            $teacher->subjectAreas()->attach($teacherSubjectAreas->pluck('id'));
        }

        // ──────────────────────────────────────────
        // Students (3-8 per teacher)
        // ──────────────────────────────────────────
        $firstNames = ['Oliver', 'Amelia', 'Harry', 'Isla', 'Jack', 'Ava', 'George', 'Mia', 'Noah', 'Isabella', 'Leo', 'Sophia', 'Arthur', 'Grace', 'Muhammad', 'Lily', 'Oscar', 'Freya', 'Charlie', 'Emily', 'Jacob', 'Ivy', 'Henry', 'Ella', 'Thomas', 'Rosie', 'Alfie', 'Florence', 'James', 'Poppy', 'William', 'Willow', 'Ethan', 'Sienna', 'Alexander', 'Charlotte', 'Lucas', 'Phoebe', 'Daniel', 'Evie'];
        $lastNames = ['Smith', 'Jones', 'Williams', 'Taylor', 'Brown', 'Davies', 'Evans', 'Wilson', 'Thomas', 'Roberts', 'Johnson', 'Lewis', 'Walker', 'Robinson', 'Wood', 'Thompson', 'White', 'Watson', 'Jackson', 'Wright'];

        $allStudents = collect();
        foreach ($teachers as $teacher) {
            $numStudents = rand(3, 8);
            $teacherInstrumentIds = $teacher->instruments->pluck('id')->toArray();

            for ($i = 0; $i < $numStudents; $i++) {
                $student = Student::create([
                    'user_id' => $teacher->id,
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name' => $lastNames[array_rand($lastNames)],
                    'email' => null,
                    'instrument_id' => $teacherInstrumentIds[array_rand($teacherInstrumentIds)],
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

        foreach ($teachers as $teacher) {
            $numOrders = rand(1, 4);
            $teacherSchoolIds = $teacher->schools->pluck('id')->toArray();
            $teacherStudents = $allStudents->where('user_id', $teacher->id);

            for ($o = 0; $o < $numOrders; $o++) {
                $orderNumber++;
                $isDigital = (bool) rand(0, 1);
                $deliveryMethod = $isDigital ? 'Digital' : 'Default';
                $commissionRate = $isDigital ? 20.00 : 28.00;
                $candidates = rand(2, 8);
                $subjectArea = $subjectAreas->random()->name;
                $status = $orderStatuses[array_rand($orderStatuses)];

                // Estimate commission: roughly £30-60 per candidate
                $feePerCandidate = rand(30, 60);
                $totalFees = $feePerCandidate * $candidates;
                $commissionAmount = round($totalFees * ($commissionRate / 100), 2);

                $order = Order::create([
                    'user_id' => $teacher->id,
                    'school_id' => !empty($teacherSchoolIds) ? $teacherSchoolIds[array_rand($teacherSchoolIds)] : null,
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

                // Create exam entries for each candidate in this order
                $orderStudents = $teacherStudents->random(min($candidates, $teacherStudents->count()));
                foreach ($orderStudents as $student) {
                    ExamEntry::create([
                        'order_id' => $order->id,
                        'student_id' => $student->id,
                        'instrument_id' => $student->instrument_id,
                        'grade' => $grades[array_rand($grades)],
                        'subject_area' => $subjectArea,
                        'delivery_method' => $deliveryMethod,
                        'result' => $status === 'Completed' ? ['Pass', 'Merit', 'Distinction'][array_rand(['Pass', 'Merit', 'Distinction'])] : null,
                        'exam_date' => $status === 'Completed' ? now()->subDays(rand(1, 120))->format('Y-m-d') : null,
                    ]);
                }
            }
        }

        // ──────────────────────────────────────────
        // Contact Logs
        // ──────────────────────────────────────────
        $contactSubjects = [
            'email' => ['Welcome email sent', 'Follow-up on exam registration', 'Commission payment query', 'Digital exam setup help', 'Venue booking confirmation', 'Exam results discussion'],
            'phone' => ['Introductory call', 'Exam deadline reminder', 'Discussed switching to digital', 'Commission rate query', 'Venue availability check'],
            'face_to_face' => ['Met at Trinity event', 'School visit', 'Studio meeting', 'Exam day chat', 'Training session'],
        ];

        foreach ($teachers as $teacher) {
            $numLogs = rand(1, 5);

            for ($l = 0; $l < $numLogs; $l++) {
                $contactType = ['email', 'phone', 'face_to_face'][array_rand(['email', 'phone', 'face_to_face'])];
                $subjects = $contactSubjects[$contactType];
                $subject = $subjects[array_rand($subjects)];

                ContactLog::create([
                    'user_id' => $teacher->id,
                    'contact_type' => $contactType,
                    'direction' => ['outbound', 'inbound'][array_rand(['outbound', 'inbound'])],
                    'subject' => $subject,
                    'summary' => "Contacted {$teacher->name} regarding: {$subject}.",
                    'contacted_at' => now()->subDays(rand(1, 200))->format('Y-m-d'),
                ]);
            }
        }
    }
}
