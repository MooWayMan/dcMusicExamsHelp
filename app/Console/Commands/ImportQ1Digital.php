<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Illuminate\Console\Command;

class ImportQ1Digital extends Command
{
    protected $signature = 'exam:import-q1-digital {--fresh : Delete existing Q1 2026 digital entries before importing}';
    protected $description = 'Import Q1 2026 (Jan-Mar) digital exam entries into the database';

    private function instrumentMap(): array
    {
        return [
            'Drums' => 'Drum Kit',
            'Guitar' => 'Guitar (Rock/Pop)',
            'Keyboards' => 'Electronic Keyboard',
            'Piano' => 'Piano',
            'Flute' => 'Flute',
            'Clarinet' => 'Clarinet',
            'Jazz Clarinet' => 'Clarinet',
            'Saxophone' => 'Saxophone',
            'Singing' => 'Singing (Classical)',
        ];
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $orderNumbers = array_keys($this->getOrders());

            // Force delete to bypass soft deletes — soft-deleted rows still block unique constraints
            $orderIds = Order::withTrashed()->whereIn('trinity_order_number', $orderNumbers)->pluck('id');

            $deleted = ExamEntry::whereIn('order_id', $orderIds)->forceDelete();
            $deletedOrders = Order::withTrashed()->whereIn('trinity_order_number', $orderNumbers)->forceDelete();

            $this->info("Deleted {$deleted} entries and {$deletedOrders} orders (force).");
        }

        $orders = $this->getOrders();
        $entries = $this->getEntries();

        $instrumentCache = [];
        $orderCache = [];
        $created = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $orderKey = $entry['order_number'];

            if (! isset($orderCache[$orderKey])) {
                $orderData = $orders[$orderKey] ?? null;
                if (! $orderData) {
                    $this->warn("Order {$orderKey} not found — skipping.");
                    $skipped++;
                    continue;
                }

                $orderCache[$orderKey] = Order::updateOrCreate(
                    ['trinity_order_number' => $orderKey],
                    [
                        'delivery_method' => 'Digital',
                        'subject_area' => $orderData['subject_area'],
                        'candidates' => $orderData['candidates'],
                        'order_status' => 'Processed',
                        'requested_start_date' => $orderData['requested_start_date'],
                        'commission_rate' => 20.00,
                        'applicant_name' => $orderData['applicant_name'],
                        'applicant_email' => $orderData['applicant_email'],
                    ]
                );
            }

            $order = $orderCache[$orderKey];

            // Resolve instrument
            $trinityInstrument = $entry['instrument'];
            if (! isset($instrumentCache[$trinityInstrument])) {
                $mappedName = $this->instrumentMap()[$trinityInstrument] ?? null;
                $instrumentCache[$trinityInstrument] = $mappedName
                    ? Instrument::where('name', $mappedName)->first()
                    : null;

                if (! $instrumentCache[$trinityInstrument]) {
                    $this->warn("Instrument '{$trinityInstrument}' not mapped — storing name only.");
                }
            }

            $instrument = $instrumentCache[$trinityInstrument];

            ExamEntry::updateOrCreate(
                ['candidate_number' => $entry['candidate_number']],
                [
                    'order_id' => $order->id,
                    'instrument_id' => $instrument?->id,
                    'candidate_name' => $entry['candidate_name'],
                    'teacher_name' => $entry['teacher_name'],
                    'school_name' => $entry['school_name'],
                    'grade' => $entry['grade'],
                    'subject_area' => $entry['subject_area'],
                    'delivery_method' => 'Digital',
                    'fee' => $entry['fee'],
                    'score' => $entry['score'] ?? null,
                    'result' => $entry['result'] ?? null,
                    'notes' => $entry['notes'] ?? null,
                ]
            );

            $created++;
        }

        $this->info("Import complete: {$created} entries created, {$skipped} skipped.");

        // Summary by teacher
        $this->newLine();
        $this->info('=== Q1 2026 Digital Summary ===');

        $totalFees = collect($entries)->where('notes', '!=', 'CANCELLED')->sum('fee');
        $commission = $totalFees * 0.20;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total candidates', $created],
                ['Total fees', '£' . number_format($totalFees, 2)],
                ['Commission (20%)', '£' . number_format($commission, 2)],
            ]
        );

        return Command::SUCCESS;
    }

    private function getOrders(): array
    {
        return [
            '1-15280573474' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 10,
                'requested_start_date' => '2026-03-10',
                'applicant_name' => 'Daniel Rogers',
                'applicant_email' => 'exams@pulsemusicliverpool.com',
            ],
            '1-15279275724' => [
                'subject_area' => 'Music',
                'candidates' => 4,
                'requested_start_date' => '2026-03-10',
                'applicant_name' => 'Clare Keeling',
                'applicant_email' => 'musiclearn11@gmail.com',
            ],
            '1-15279500444' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-03-10',
                'applicant_name' => 'Megan Price',
                'applicant_email' => 'meganclr96@gmail.com',
            ],
            '1-15641606604' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-03-25',
                'applicant_name' => 'Daniel Rogers',
                'applicant_email' => 'exams@pulsemusicliverpool.com',
            ],
            '1-14835765869' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-03-02',
                'applicant_name' => 'Paul Sheridan',
                'applicant_email' => 'madmusic6@hotmail.com',
            ],
            '1-14835557379' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-03-02',
                'applicant_name' => 'Paul Sheridan',
                'applicant_email' => 'madmusic6@hotmail.com',
            ],
            '1-14243820189' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-02-23',
                'applicant_name' => 'Paul Sheridan',
                'applicant_email' => 'madmusic6@hotmail.com',
            ],
            '1-14163844479' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-02-18',
                'applicant_name' => 'Megan Price',
                'applicant_email' => 'meganclr96@gmail.com',
            ],
            '1-14090535219' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-02-17',
                'applicant_name' => 'Rachel Jones',
                'applicant_email' => 'rachelsimms1969@gmail.com',
            ],
            '1-13750176989' => [
                'subject_area' => 'Music',
                'candidates' => 3,
                'requested_start_date' => '2026-02-15',
                'applicant_name' => 'Clare Keeling',
                'applicant_email' => 'musiclearn11@gmail.com',
            ],
            '1-13748006149' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-02-15',
                'applicant_name' => 'Clare Keeling',
                'applicant_email' => 'musiclearn11@gmail.com',
            ],
            '1-13478401579' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-02-14',
                'applicant_name' => 'Megan Price',
                'applicant_email' => 'meganclr96@gmail.com',
            ],
            '1-11543471049' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-01-22',
                'applicant_name' => 'Daniel Rogers',
                'applicant_email' => 'exams@pulsemusicliverpool.com',
            ],
            '1-11490766629' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 1,
                'requested_start_date' => '2026-01-13',
                'applicant_name' => 'Daniel Rogers',
                'applicant_email' => 'exams@pulsemusicliverpool.com',
            ],
            '1-11478522619' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-01-09',
                'applicant_name' => 'Megan Price',
                'applicant_email' => 'meganclr96@gmail.com',
            ],
            '1-15899713974' => [
                'subject_area' => 'Music',
                'candidates' => 1,
                'requested_start_date' => '2026-03-30',
                'applicant_name' => 'Maria Nielsen',
                'applicant_email' => 'mkn21@me.com',
            ],
            '1-15451163944' => [
                'subject_area' => 'Rock and Pop',
                'candidates' => 4,
                'requested_start_date' => '2026-03-20',
                'applicant_name' => 'Daniel Rogers',
                'applicant_email' => 'exams@pulsemusicliverpool.com',
            ],
        ];
    }

    private function getEntries(): array
    {
        return [
            // ── Order 1-15280573474 — 10 Mar, Pulse Music (Daniel Rogers) ──
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15279928344', 'candidate_name' => 'Thomas Escribano', 'instrument' => 'Drums', 'grade' => '2', 'subject_area' => 'Rock and Pop', 'fee' => 61.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280381044', 'candidate_name' => 'Olivia Ashcroft', 'instrument' => 'Drums', 'grade' => '2', 'subject_area' => 'Rock and Pop', 'fee' => 61.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => 'CANCELLED', 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280254974', 'candidate_name' => 'Clayton Lo', 'instrument' => 'Drums', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15279928394', 'candidate_name' => 'George Higham', 'instrument' => 'Drums', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280405884', 'candidate_name' => 'Andrew Davies', 'instrument' => 'Drums', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280405934', 'candidate_name' => 'Evie Crawford', 'instrument' => 'Drums', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280255004', 'candidate_name' => 'Joe Gallagher', 'instrument' => 'Guitar', 'grade' => '5', 'subject_area' => 'Rock and Pop', 'fee' => 88.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280573404', 'candidate_name' => 'Milo Hugh', 'instrument' => 'Guitar', 'grade' => 'Initial', 'subject_area' => 'Rock and Pop', 'fee' => 49.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280057324', 'candidate_name' => 'Alexander Campbell', 'instrument' => 'Guitar', 'grade' => '1', 'subject_area' => 'Rock and Pop', 'fee' => 55.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15280573474', 'candidate_number' => '1-15280573434', 'candidate_name' => 'Sam Brooks', 'instrument' => 'Guitar', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],

            // ── Order 1-15279275724 — 10 Mar, Learn Music (Clare Keeling) ──
            ['order_number' => '1-15279275724', 'candidate_number' => '1-5810705043', 'candidate_name' => 'Naomi Ruth Maher', 'instrument' => 'Piano', 'grade' => '4', 'subject_area' => 'Music', 'fee' => 78.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => 70, 'result' => 'Pass'],
            ['order_number' => '1-15279275724', 'candidate_number' => '1-15279077954', 'candidate_name' => 'Anugrahchandra Nidhin', 'instrument' => 'Piano', 'grade' => '1', 'subject_area' => 'Music', 'fee' => 55.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15279275724', 'candidate_number' => '1-15279278554', 'candidate_name' => 'Yuling Huang', 'instrument' => 'Flute', 'grade' => '2', 'subject_area' => 'Music', 'fee' => 61.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15279275724', 'candidate_number' => '1-10567842683', 'candidate_name' => 'Tilly Lamb', 'instrument' => 'Clarinet', 'grade' => '2', 'subject_area' => 'Music', 'fee' => 61.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => null, 'result' => null],

            // ── Order 1-15279500444 — 10 Mar, Parent booking (Megan Price) ──
            ['order_number' => '1-15279500444', 'candidate_number' => '1-15279500414', 'candidate_name' => 'Alfie John Clapson', 'instrument' => 'Keyboards', 'grade' => '1', 'subject_area' => 'Rock and Pop', 'fee' => 55.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Megan Price', 'score' => null, 'result' => null],

            // ── Order 1-15641606604 — 25 Mar, Pulse Music (Daniel Rogers) ──
            ['order_number' => '1-15641606604', 'candidate_number' => '1-15641410114', 'candidate_name' => 'Flynn Munro', 'instrument' => 'Drums', 'grade' => '2', 'subject_area' => 'Rock and Pop', 'fee' => 61.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],

            // ── Order 1-14835765869 — 2 Mar, Paul Sheridan ──
            ['order_number' => '1-14835765869', 'candidate_number' => '1-14835996853', 'candidate_name' => 'Aneirin Dennis', 'instrument' => 'Guitar', 'grade' => '3', 'subject_area' => 'Rock and Pop', 'fee' => 68.00, 'teacher_name' => 'Paul Sheridan', 'school_name' => null, 'notes' => null, 'score' => 75, 'result' => 'Merit'],

            // ── Order 1-14835557379 — 2 Mar, Paul Sheridan ──
            ['order_number' => '1-14835557379', 'candidate_number' => '1-6173593323', 'candidate_name' => 'Sajeevan Arudseelan', 'instrument' => 'Piano', 'grade' => '3', 'subject_area' => 'Music', 'fee' => 68.00, 'teacher_name' => 'Paul Sheridan', 'school_name' => null, 'notes' => null, 'score' => 65, 'result' => 'Pass'],

            // ── Order 1-14243820189 — 23 Feb, Paul Sheridan ──
            ['order_number' => '1-14243820189', 'candidate_number' => '1-7728518913', 'candidate_name' => 'Keerthanaa Arudseelan', 'instrument' => 'Piano', 'grade' => '3', 'subject_area' => 'Music', 'fee' => 68.00, 'teacher_name' => 'Paul Sheridan', 'school_name' => null, 'notes' => null, 'score' => 72, 'result' => 'Pass'],

            // ── Order 1-14163844479 — 18 Feb, Parent booking (Megan Price) ──
            ['order_number' => '1-14163844479', 'candidate_number' => '1-10160064603', 'candidate_name' => 'Peter Mylechreest', 'instrument' => 'Saxophone', 'grade' => '4', 'subject_area' => 'Music', 'fee' => 78.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Megan Price', 'score' => 79, 'result' => 'Merit'],

            // ── Order 1-14090535219 — 17 Feb, Parent booking (Rachel Jones) ──
            ['order_number' => '1-14090535219', 'candidate_number' => '1-14090565303', 'candidate_name' => 'Isaac Pennington', 'instrument' => 'Jazz Clarinet', 'grade' => '5', 'subject_area' => 'Music', 'fee' => 88.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Rachel Jones', 'score' => 77, 'result' => 'Merit'],

            // ── Order 1-13750176989 — 15 Feb, Learn Music (Clare Keeling) ──
            ['order_number' => '1-13750176989', 'candidate_number' => '1-5589446423', 'candidate_name' => 'Mira Ghali', 'instrument' => 'Piano', 'grade' => '2', 'subject_area' => 'Music', 'fee' => 61.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => 61, 'result' => 'Pass'],
            ['order_number' => '1-13750176989', 'candidate_number' => '1-5121736343', 'candidate_name' => 'George John Canning Yates', 'instrument' => 'Piano', 'grade' => '5', 'subject_area' => 'Music', 'fee' => 88.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => 70, 'result' => 'Pass'],
            ['order_number' => '1-13750176989', 'candidate_number' => '1-13750484503', 'candidate_name' => 'Harrison John Burslem', 'instrument' => 'Piano', 'grade' => 'Initial', 'subject_area' => 'Music', 'fee' => 49.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => 66, 'result' => 'Pass'],

            // ── Order 1-13748006149 — 15 Feb, Learn Music (Clare Keeling) ──
            ['order_number' => '1-13748006149', 'candidate_number' => '1-13746448053', 'candidate_name' => 'George Ghali', 'instrument' => 'Drums', 'grade' => '2', 'subject_area' => 'Rock and Pop', 'fee' => 61.00, 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null, 'score' => 79, 'result' => 'Merit'],

            // ── Order 1-13478401579 — 14 Feb, Parent booking (Megan Price) ──
            ['order_number' => '1-13478401579', 'candidate_number' => '1-10035059003', 'candidate_name' => 'Jess Iris Wykes', 'instrument' => 'Flute', 'grade' => '3', 'subject_area' => 'Music', 'fee' => 68.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Megan Price', 'score' => 90, 'result' => 'Distinction'],

            // ── Order 1-11543471049 — 22 Jan, Pulse Music (Daniel Rogers) ──
            ['order_number' => '1-11543471049', 'candidate_number' => '1-11543472573', 'candidate_name' => 'Teddy Thompson-Davies', 'instrument' => 'Guitar', 'grade' => 'Initial', 'subject_area' => 'Rock and Pop', 'fee' => 49.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => 83, 'result' => 'Merit'],

            // ── Order 1-11490766629 — 13 Jan, Pulse Music (Daniel Rogers) ──
            ['order_number' => '1-11490766629', 'candidate_number' => '1-11490766343', 'candidate_name' => 'Isaac Richman', 'instrument' => 'Drums', 'grade' => '8', 'subject_area' => 'Rock and Pop', 'fee' => 120.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => 76, 'result' => 'Merit'],

            // ── Order 1-11478522619 — 9 Jan, Parent booking (Megan Price) ──
            ['order_number' => '1-11478522619', 'candidate_number' => '1-11478522463', 'candidate_name' => 'Marie Lewis Follett', 'instrument' => 'Piano', 'grade' => '1', 'subject_area' => 'Music', 'fee' => 55.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Megan Price', 'score' => 66, 'result' => 'Pass'],

            // ── Order 1-15899713974 — 30 Mar, Parent booking (Maria Nielsen) ──
            ['order_number' => '1-15899713974', 'candidate_number' => '1-15899370904', 'candidate_name' => 'Delfina Yelich Battisacchi', 'instrument' => 'Singing', 'grade' => '1', 'subject_area' => 'Music', 'fee' => 55.00, 'teacher_name' => null, 'school_name' => null, 'notes' => 'Parent booking — Maria Nielsen', 'score' => null, 'result' => null],

            // ── Order 1-15451163944 — 20 Mar, Pulse Music (Daniel Rogers) ──
            ['order_number' => '1-15451163944', 'candidate_number' => '1-15451220944', 'candidate_name' => 'Otis Frieze', 'instrument' => 'Drums', 'grade' => '4', 'subject_area' => 'Rock and Pop', 'fee' => 78.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15451163944', 'candidate_number' => '1-15451163914', 'candidate_name' => 'James Preston', 'instrument' => 'Drums', 'grade' => '5', 'subject_area' => 'Rock and Pop', 'fee' => 88.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15451163944', 'candidate_number' => '1-15451580044', 'candidate_name' => 'Oscar Cain', 'instrument' => 'Drums', 'grade' => '2', 'subject_area' => 'Rock and Pop', 'fee' => 61.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
            ['order_number' => '1-15451163944', 'candidate_number' => '1-15451621464', 'candidate_name' => 'Charlotte Sutton', 'instrument' => 'Drums', 'grade' => '3', 'subject_area' => 'Rock and Pop', 'fee' => 68.00, 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null, 'score' => null, 'result' => null],
        ];
    }
}
