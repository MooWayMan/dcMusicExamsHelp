<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\PageMaintenance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedProductionData extends Command
{
    protected $signature = 'data:seed-production';

    protected $description = 'Rebuild local database with all production data. Run AFTER sail artisan migrate:fresh — no TablePlus needed.';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('  Seeding local database with production data');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        // Step 1: Admin user + Instruments + Subject Areas (via DatabaseSeeder)
        $this->info('Step 1/7: Seeding admin user, instruments & subject areas...');
        $this->call('db:seed');
        $this->newLine();

        // Step 2: Teachers
        $this->info('Step 2/7: Creating teacher accounts...');
        $this->seedTeachers();
        $this->newLine();

        // Step 3: Schools + teacher-school links
        $this->info('Step 3/7: Creating schools and linking to teachers...');
        $this->seedSchools();
        $this->newLine();

        // Step 4: Students
        $this->info('Step 4/7: Creating students...');
        $this->seedStudents();
        $this->newLine();

        // Step 5: Import Q1 F2F results (39 entries + 4 orders) + fix teacher names
        $this->info('Step 5/7: Importing Q1 F2F results and fixing teacher names...');
        $this->call('exam:import-q1', ['--fresh' => true]);
        $this->call('exam:fix-f2f-teachers');
        $this->newLine();

        // Step 6: Import Q1 digital entries (34 entries + 17 orders)
        $this->info('Step 6/7: Importing Q1 digital entries...');
        $this->call('exam:import-q1-digital', ['--fresh' => true]);
        $this->newLine();

        // Step 7: Seed page maintenance rows
        $this->info('Step 7/7: Seeding page maintenance settings...');
        PageMaintenance::seed();
        $this->info('Page maintenance rows created.');
        $this->newLine();

        // Summary
        $this->info('═══════════════════════════════════════════');
        $this->info('  All done! Local database matches production.');
        $this->info('═══════════════════════════════════════════');

        $this->table(
            ['Table', 'Count'],
            [
                ['Users', User::count()],
                ['Schools', DB::table('schools')->count()],
                ['Students', Student::count()],
                ['Orders', Order::count()],
                ['Exam Entries', ExamEntry::count()],
            ]
        );

        return Command::SUCCESS;
    }

    private function seedTeachers(): void
    {
        $teachers = [
            ['id' => 18, 'name' => 'Paul Sheridan', 'email' => 'paul-sheridan@placeholder.musicexams.help'],
            ['id' => 19, 'name' => 'Clare Keeling', 'email' => 'clare-keeling@placeholder.musicexams.help'],
            ['id' => 20, 'name' => 'Jenny Capstick', 'email' => 'jenny-capstick@placeholder.musicexams.help'],
            ['id' => 21, 'name' => 'Gillian Leslie', 'email' => 'gillian-leslie@placeholder.musicexams.help'],
            ['id' => 22, 'name' => "Adrian O'Malley", 'email' => 'adrian-omalley@placeholder.musicexams.help'],
            ['id' => 23, 'name' => 'Daniel Rogers', 'email' => 'daniel-rogers@placeholder.musicexams.help'],
            ['id' => 24, 'name' => 'Roxanne Twomey', 'email' => 'roxanne-twomey@placeholder.musicexams.help'],
            ['id' => 25, 'name' => 'Megan Thompson', 'email' => 'megan-thompson@placeholder.musicexams.help'],
            ['id' => 26, 'name' => 'Tracey Lea', 'email' => 'tracey-lea@placeholder.musicexams.help'],
            ['id' => 27, 'name' => 'Christopher Callaway', 'email' => 'christopher-callaway@placeholder.musicexams.help'],
            ['id' => 28, 'name' => 'Stephen Shotton', 'email' => 'stephen-shotton@placeholder.musicexams.help'],
            ['id' => 29, 'name' => 'Rachel Jones', 'email' => 'rachel-jones@placeholder.musicexams.help'],
            ['id' => 30, 'name' => 'Alexandra Bibby', 'email' => 'alexandra-bibby@placeholder.musicexams.help'],
            ['id' => 31, 'name' => 'Megan Price', 'email' => 'megan-price@placeholder.musicexams.help'],
            ['id' => 32, 'name' => 'Jennifer Hynes', 'email' => 'jennifer-hynes@placeholder.musicexams.help'],
        ];

        foreach ($teachers as $t) {
            DB::table('users')->insert([
                'id' => $t['id'],
                'name' => $t['name'],
                'email' => $t['email'],
                'password' => bcrypt('password'),
                'role' => 'teacher',
                'met_face_to_face' => false,
                'spoken_on_phone' => false,
                'contacted_by_email' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Reset sequence
        DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");
        $this->info(count($teachers) . ' teachers created.');
    }

    private function seedSchools(): void
    {
        $schools = [
            ['id' => 1, 'name' => 'Birkenhead School', 'address' => '58 Beresford Road', 'city' => 'Birkenhead', 'postcode' => 'CH43 2JD'],
            ['id' => 2, 'name' => 'Calday Grange Grammar School', 'address' => 'Grammar School Lane', 'city' => 'West Kirby', 'postcode' => 'CH48 8GG'],
            ['id' => 3, 'name' => 'Wirral Grammar School for Boys', 'address' => 'Cross Lane', 'city' => 'Bebington', 'postcode' => 'CH63 3AQ'],
            ['id' => 4, 'name' => 'Upton Hall School', 'address' => 'Moreton Road', 'city' => 'Upton', 'postcode' => 'CH49 6LJ'],
            ['id' => 5, 'name' => 'West Kirby Residential School', 'address' => 'Meols Drive', 'city' => 'West Kirby', 'postcode' => 'CH48 5DH'],
            ['id' => 6, 'name' => "St Anselm's College", 'address' => 'Manor Hill', 'city' => 'Birkenhead', 'postcode' => 'CH43 1UQ'],
            ['id' => 7, 'name' => 'Hilbre High School', 'address' => 'Frankby Road', 'city' => 'West Kirby', 'postcode' => 'CH48 6EQ'],
            ['id' => 8, 'name' => 'Prenton High School', 'address' => 'Christchurch Road', 'city' => 'Prenton', 'postcode' => 'CH43 5RE'],
            ['id' => 9, 'name' => 'Hillside High School'],
            ['id' => 10, 'name' => 'Pulse Music School'],
            ['id' => 11, 'name' => 'School of Rox'],
            ['id' => 12, 'name' => 'Wirral School of Music'],
            ['id' => 13, 'name' => 'Learn Music Ltd'],
        ];

        foreach ($schools as $s) {
            DB::table('schools')->insert([
                'id' => $s['id'],
                'name' => $s['name'],
                'address' => $s['address'] ?? null,
                'city' => $s['city'] ?? null,
                'postcode' => $s['postcode'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Teacher ↔ School links
        $links = [
            ['user_id' => 24, 'school_id' => 11], // Roxanne → School of Rox
            ['user_id' => 19, 'school_id' => 13], // Clare → Learn Music
            ['user_id' => 23, 'school_id' => 10], // Daniel → Pulse Music
            ['user_id' => 20, 'school_id' => 9],  // Jenny → Hillside High
            ['user_id' => 26, 'school_id' => 12], // Tracey → Wirral School of Music
            ['user_id' => 28, 'school_id' => 12], // Stephen → Wirral School of Music
            ['user_id' => 25, 'school_id' => 12], // Megan T → Wirral School of Music
            ['user_id' => 27, 'school_id' => 12], // Christopher → Wirral School of Music
        ];

        foreach ($links as $link) {
            DB::table('teacher_school')->insert([
                'user_id' => $link['user_id'],
                'school_id' => $link['school_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement("SELECT setval('schools_id_seq', (SELECT MAX(id) FROM schools))");
        DB::statement("SELECT setval('teacher_school_id_seq', (SELECT MAX(id) FROM teacher_school))");

        $this->info(count($schools) . ' schools + ' . count($links) . ' teacher links created.');
    }

    private function seedStudents(): void
    {
        $students = [
            // Order 155 — Clare Keeling / Learn Music (F2F)
            ['id' => 82, 'user_id' => 19, 'first_name' => 'Aria', 'last_name' => 'Maddison Chambers', 'instrument_id' => 27],
            ['id' => 83, 'user_id' => 19, 'first_name' => 'Ravi', 'last_name' => 'Michael Steff', 'instrument_id' => 18],
            ['id' => 84, 'user_id' => 19, 'first_name' => 'Solomon', 'last_name' => 'Elliot David Wetherall', 'instrument_id' => 19],
            ['id' => 85, 'user_id' => 19, 'first_name' => 'Primrose', 'last_name' => 'Nancy Gannon', 'instrument_id' => 27],
            ['id' => 86, 'user_id' => 19, 'first_name' => 'Maya', 'last_name' => 'Ghali', 'instrument_id' => 1],
            ['id' => 87, 'user_id' => 19, 'first_name' => 'Elise', 'last_name' => 'Florence Scott', 'instrument_id' => 21],
            ['id' => 88, 'user_id' => 19, 'first_name' => 'Dean', 'last_name' => 'Gwyther', 'instrument_id' => 23],
            ['id' => 89, 'user_id' => 19, 'first_name' => 'Imogen', 'last_name' => 'Mayes', 'instrument_id' => 9],
            ['id' => 90, 'user_id' => 19, 'first_name' => 'Niamh', 'last_name' => 'Keyna Anakin', 'instrument_id' => 23],
            ['id' => 91, 'user_id' => 19, 'first_name' => 'Isaac', 'last_name' => 'Pover', 'instrument_id' => 1],
            ['id' => 92, 'user_id' => 19, 'first_name' => 'Farrah', 'last_name' => 'Harper Fennell', 'instrument_id' => 1],
            ['id' => 93, 'user_id' => 19, 'first_name' => 'Kate', 'last_name' => 'Leyland', 'instrument_id' => 9],

            // Order 156 — Wirral School of Music (F2F, no teacher user_ids)
            ['id' => 95, 'user_id' => null, 'first_name' => 'Seth', 'last_name' => 'James Barraclough', 'instrument_id' => 18],
            ['id' => 96, 'user_id' => null, 'first_name' => 'Anna', 'last_name' => 'Martin', 'instrument_id' => 1],
            ['id' => 97, 'user_id' => null, 'first_name' => 'Julia', 'last_name' => 'Zamirska', 'instrument_id' => 22],
            ['id' => 98, 'user_id' => null, 'first_name' => 'Sam', 'last_name' => 'Williamson', 'instrument_id' => 1],
            ['id' => 99, 'user_id' => null, 'first_name' => 'Maya', 'last_name' => 'Parkinson', 'instrument_id' => 1],
            ['id' => 100, 'user_id' => null, 'first_name' => 'Imogen', 'last_name' => 'Hughes', 'instrument_id' => 1],
            ['id' => 101, 'user_id' => null, 'first_name' => 'Krystian', 'last_name' => 'Debek', 'instrument_id' => 4],
            ['id' => 102, 'user_id' => null, 'first_name' => 'Florence', 'last_name' => 'Cookson', 'instrument_id' => 1],
            ['id' => 103, 'user_id' => null, 'first_name' => 'Alice', 'last_name' => 'Jun Mei Khoo', 'instrument_id' => 15],
            ['id' => 104, 'user_id' => null, 'first_name' => 'Henry', 'last_name' => 'Rodway', 'instrument_id' => 1],
            ['id' => 105, 'user_id' => null, 'first_name' => 'Megan', 'last_name' => 'Parkinson', 'instrument_id' => 1],
            ['id' => 106, 'user_id' => null, 'first_name' => 'Lucas', 'last_name' => 'Hassall', 'instrument_id' => 1],

            // Order 157 — Pulse Music + Hillside High (F2F)
            ['id' => 107, 'user_id' => 23, 'first_name' => 'Amy', 'last_name' => 'Norcott', 'instrument_id' => 29],
            ['id' => 108, 'user_id' => 20, 'first_name' => 'Mia', 'last_name' => 'Mason', 'instrument_id' => 29],
            ['id' => 109, 'user_id' => 23, 'first_name' => 'Pearl', 'last_name' => 'Fay', 'instrument_id' => 29],
            ['id' => 110, 'user_id' => 20, 'first_name' => 'Charlotte', 'last_name' => 'McVey', 'instrument_id' => 29],
            ['id' => 111, 'user_id' => 23, 'first_name' => 'Zachary', 'last_name' => 'Beswick', 'instrument_id' => 11],
            ['id' => 112, 'user_id' => 23, 'first_name' => 'Zach', 'last_name' => 'Hughes', 'instrument_id' => 11],
            ['id' => 113, 'user_id' => 20, 'first_name' => 'Lilly-Mae', 'last_name' => 'Dibbert', 'instrument_id' => 29],

            // Order 158 — School of Rox + parent bookings (F2F)
            ['id' => 114, 'user_id' => 24, 'first_name' => 'Thomas', 'last_name' => 'Gander', 'instrument_id' => 11],
            ['id' => 115, 'user_id' => 24, 'first_name' => 'Alfie', 'last_name' => 'Coburn', 'instrument_id' => 11],
            ['id' => 116, 'user_id' => 24, 'first_name' => 'Francesca', 'last_name' => 'Lee', 'instrument_id' => 11],
            ['id' => 117, 'user_id' => 21, 'first_name' => 'Jacob', 'last_name' => 'Thomas Leslie', 'instrument_id' => 11],
            ['id' => 118, 'user_id' => 22, 'first_name' => 'Jasper', 'last_name' => "Christian O'Malley", 'instrument_id' => 11],
            ['id' => 119, 'user_id' => 19, 'first_name' => 'Jemima', 'last_name' => 'Claire Reed', 'instrument_id' => 29],
            ['id' => 120, 'user_id' => 24, 'first_name' => 'Daniel', 'last_name' => 'Carty', 'instrument_id' => 31],
            ['id' => 121, 'user_id' => 19, 'first_name' => 'Philip', 'last_name' => 'Martin Gazdecki', 'instrument_id' => 11],

            // Digital entries — Pulse Music (Daniel Rogers)
            ['id' => 122, 'user_id' => 23, 'first_name' => 'Thomas', 'last_name' => 'Escribano', 'instrument_id' => 31],
            ['id' => 123, 'user_id' => 23, 'first_name' => 'Olivia', 'last_name' => 'Ashcroft', 'instrument_id' => 31],
            ['id' => 124, 'user_id' => 23, 'first_name' => 'Clayton', 'last_name' => 'Lo', 'instrument_id' => 31],
            ['id' => 125, 'user_id' => 23, 'first_name' => 'George', 'last_name' => 'Higham', 'instrument_id' => 31],
            ['id' => 126, 'user_id' => 23, 'first_name' => 'Andrew', 'last_name' => 'Davies', 'instrument_id' => 31],
            ['id' => 127, 'user_id' => 23, 'first_name' => 'Evie', 'last_name' => 'Crawford', 'instrument_id' => 31],
            ['id' => 128, 'user_id' => 23, 'first_name' => 'Joe', 'last_name' => 'Gallagher', 'instrument_id' => 11],
            ['id' => 129, 'user_id' => 23, 'first_name' => 'Milo', 'last_name' => 'Hugh', 'instrument_id' => 11],
            ['id' => 130, 'user_id' => 23, 'first_name' => 'Alexander', 'last_name' => 'Campbell', 'instrument_id' => 11],
            ['id' => 131, 'user_id' => 23, 'first_name' => 'Sam', 'last_name' => 'Brooks', 'instrument_id' => 11],

            // Digital entries — Clare Keeling / Learn Music
            ['id' => 132, 'user_id' => 19, 'first_name' => 'Naomi', 'last_name' => 'Ruth Maher', 'instrument_id' => 1],
            ['id' => 133, 'user_id' => 19, 'first_name' => 'Anugrahchandra', 'last_name' => 'Nidhin', 'instrument_id' => 1],
            ['id' => 134, 'user_id' => 19, 'first_name' => 'Yuling', 'last_name' => 'Huang', 'instrument_id' => 21],
            ['id' => 135, 'user_id' => 19, 'first_name' => 'Tilly', 'last_name' => 'Lamb', 'instrument_id' => 23],
            ['id' => 143, 'user_id' => 19, 'first_name' => 'Mira', 'last_name' => 'Ghali', 'instrument_id' => 1],
            ['id' => 144, 'user_id' => 19, 'first_name' => 'George', 'last_name' => 'John Canning Yates', 'instrument_id' => 1],
            ['id' => 145, 'user_id' => 19, 'first_name' => 'Harrison', 'last_name' => 'John Burslem', 'instrument_id' => 1],
            ['id' => 146, 'user_id' => 19, 'first_name' => 'George', 'last_name' => 'Ghali', 'instrument_id' => 31],

            // Digital entries — parent bookings (no teacher)
            ['id' => 136, 'user_id' => null, 'first_name' => 'Alfie', 'last_name' => 'John Clapson', 'instrument_id' => 3],
            ['id' => 141, 'user_id' => null, 'first_name' => 'Peter', 'last_name' => 'Mylechreest', 'instrument_id' => 25],
            ['id' => 142, 'user_id' => null, 'first_name' => 'Isaac', 'last_name' => 'Pennington', 'instrument_id' => 23],
            ['id' => 147, 'user_id' => null, 'first_name' => 'Jess', 'last_name' => 'Iris Wykes', 'instrument_id' => 21],
            ['id' => 150, 'user_id' => null, 'first_name' => 'Marie', 'last_name' => 'Lewis Follett', 'instrument_id' => 1],
            ['id' => 151, 'user_id' => null, 'first_name' => 'Delfina', 'last_name' => 'Yelich Battisacchi', 'instrument_id' => 27],

            // Digital entries — Paul Sheridan's own students
            ['id' => 138, 'user_id' => 18, 'first_name' => 'Aneirin', 'last_name' => 'Dennis', 'instrument_id' => 11],
            ['id' => 139, 'user_id' => 18, 'first_name' => 'Sajeevan', 'last_name' => 'Arudseelan', 'instrument_id' => 1],
            ['id' => 140, 'user_id' => 18, 'first_name' => 'Keerthanaa', 'last_name' => 'Arudseelan', 'instrument_id' => 1],

            // Digital entries — more Pulse Music
            ['id' => 137, 'user_id' => 23, 'first_name' => 'Flynn', 'last_name' => 'Munro', 'instrument_id' => 31],
            ['id' => 148, 'user_id' => 23, 'first_name' => 'Teddy', 'last_name' => 'Thompson-Davies', 'instrument_id' => 11],
            ['id' => 149, 'user_id' => 23, 'first_name' => 'Isaac', 'last_name' => 'Richman', 'instrument_id' => 31],
            ['id' => 152, 'user_id' => 23, 'first_name' => 'Otis', 'last_name' => 'Frieze', 'instrument_id' => 31],
            ['id' => 153, 'user_id' => 23, 'first_name' => 'James', 'last_name' => 'Preston', 'instrument_id' => 31],
            ['id' => 154, 'user_id' => 23, 'first_name' => 'Oscar', 'last_name' => 'Cain', 'instrument_id' => 31],
            ['id' => 155, 'user_id' => 23, 'first_name' => 'Charlotte', 'last_name' => 'Sutton', 'instrument_id' => 31],
        ];

        foreach ($students as $s) {
            DB::table('students')->insert([
                'id' => $s['id'],
                'user_id' => $s['user_id'],
                'first_name' => $s['first_name'],
                'last_name' => $s['last_name'],
                'instrument_id' => $s['instrument_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement("SELECT setval('students_id_seq', (SELECT MAX(id) FROM students))");
        $this->info(count($students) . ' students created.');
    }
}
