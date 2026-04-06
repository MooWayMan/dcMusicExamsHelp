<?php

// app/Console/Commands/ImportQ1Results.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Illuminate\Console\Command;

class ImportQ1Results extends Command
{
    protected $signature = 'exam:import-q1 {--fresh : Delete existing Q1 2026 entries before importing}';
    protected $description = 'Import Q1 2026 (Jan-Mar) exam results into the database';

    /**
     * Map Trinity instrument names to our seeded instrument names.
     */
    private function instrumentMap(): array
    {
        return [
            'Singing' => 'Singing (Classical)',
            'Trombone' => 'Trombone',
            'Tenor Horn' => 'Euphonium', // Closest match — tenor horn not seeded separately
            'Flute' => 'Flute',
            'Acoustic Guitar' => 'Guitar (Classical)',
            'Piano' => 'Piano',
            'Clarinet' => 'Clarinet',
            'Oboe' => 'Oboe',
            'Violin' => 'Violin',
            'Viola' => 'Viola',
            'Cornet' => 'Cornet',
            'R&P Vocals' => 'Singing (Rock/Pop)',
            'R&P Guitar' => 'Guitar (Rock/Pop)',
            'R&P Drums' => 'Drum Kit',
        ];
    }

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $deleted = ExamEntry::whereHas('order', function ($q) {
                $q->where('requested_start_date', '>=', '2026-01-01')
                  ->where('requested_start_date', '<=', '2026-03-31');
            })->delete();
            $this->info("Deleted {$deleted} existing Q1 2026 entries.");
        }

        $orders = $this->getOrders();
        $results = $this->getResults();

        $instrumentCache = [];
        $orderCache = [];
        $created = 0;
        $skipped = 0;

        foreach ($results as $entry) {
            // Find or create the order
            $orderKey = $entry['order_number'];
            if (! isset($orderCache[$orderKey])) {
                $orderData = $orders[$orderKey] ?? null;
                if (! $orderData) {
                    $this->warn("Order {$orderKey} not found in order data — skipping.");
                    $skipped++;
                    continue;
                }

                $orderCache[$orderKey] = Order::firstOrCreate(
                    ['trinity_order_number' => $orderKey],
                    [
                        'delivery_method' => $orderData['delivery_method'],
                        'subject_area' => $orderData['subject_area'],
                        'candidates' => $orderData['candidates'],
                        'venue' => $orderData['venue'],
                        'order_status' => $orderData['order_status'],
                        'requested_start_date' => $orderData['requested_start_date'],
                        'commission_rate' => $orderData['delivery_method'] === 'Default' ? 28.00 : 20.00,
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

            // Check for duplicate
            $exists = ExamEntry::where('order_id', $order->id)
                ->where('candidate_name', $entry['candidate_name'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            ExamEntry::create([
                'order_id' => $order->id,
                'instrument_id' => $instrument?->id,
                'candidate_name' => $entry['candidate_name'],
                'teacher_name' => $entry['teacher_name'],
                'school_name' => $entry['school_name'],
                'grade' => $entry['grade'],
                'subject_area' => $order->subject_area,
                'delivery_method' => $order->delivery_method,
                'result' => $entry['result'],
                'score' => $entry['score'],
                'exam_date' => $entry['exam_date'],
                'notes' => $entry['notes'] ?? null,
            ]);

            $created++;
        }

        $this->info("Import complete: {$created} entries created, {$skipped} skipped.");

        // Summary
        $distinctions = ExamEntry::where('score', '>=', 87)->count();
        $merits = ExamEntry::where('score', '>=', 75)->where('score', '<', 87)->count();
        $passes = ExamEntry::where('score', '>=', 60)->where('score', '<', 75)->count();

        $this->table(
            ['Result', 'Count'],
            [
                ['Distinction (87+)', $distinctions],
                ['Merit (75-86)', $merits],
                ['Pass (60-74)', $passes],
                ['Total', $distinctions + $merits + $passes],
            ]
        );

        // Hall of Fame
        $topDistinction = ExamEntry::whereNotNull('score')
            ->orderByDesc('score')
            ->first();
        $topMerit = ExamEntry::where('score', '>=', 75)
            ->where('score', '<', 87)
            ->orderByDesc('score')
            ->first();

        if ($topDistinction) {
            $this->info("🏆 Top Distinction: {$topDistinction->candidate_name} — {$topDistinction->score}");
        }
        if ($topMerit) {
            $this->info("🏆 Top Merit: {$topMerit->candidate_name} — {$topMerit->score}");
        }

        return Command::SUCCESS;
    }

    private function getOrders(): array
    {
        return [
            '1-11508172910' => [
                'delivery_method' => 'Default',
                'subject_area' => 'Music',
                'candidates' => 12,
                'venue' => 'Learn Music Ltd',
                'order_status' => 'Delivered',
                'requested_start_date' => '2026-03-05',
            ],
            '1-11508308070' => [
                'delivery_method' => 'Default',
                'subject_area' => 'Music',
                'candidates' => 12,
                'venue' => 'Wirral School of Music',
                'order_status' => 'Delivered',
                'requested_start_date' => '2026-03-06',
            ],
            '1-12208881501' => [
                'delivery_method' => 'Default',
                'subject_area' => 'Rock and Pop',
                'candidates' => 7,
                'venue' => 'Learn Music Ltd',
                'order_status' => 'Delivered',
                'requested_start_date' => '2026-03-07',
            ],
            '1-11478141779' => [
                'delivery_method' => 'Default',
                'subject_area' => 'Rock and Pop',
                'candidates' => 8,
                'venue' => 'Learn Music Ltd',
                'order_status' => 'Delivered',
                'requested_start_date' => '2026-03-07',
            ],
        ];
    }

    private function getResults(): array
    {
        return [
            // ── Order 1-11508172910 — 5 Mar, Learn Music Ltd ──
            ['order_number' => '1-11508172910', 'candidate_name' => 'Aria Maddison Chambers', 'instrument' => 'Singing', 'grade' => '1', 'score' => 91, 'result' => 'Distinction', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Ravi Michael Steff', 'instrument' => 'Trombone', 'grade' => '4', 'score' => 87, 'result' => 'Distinction', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Solomon Elliot David Wetherall', 'instrument' => 'Tenor Horn', 'grade' => '8', 'score' => 87, 'result' => 'Distinction', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Primrose Nancy Gannon', 'instrument' => 'Singing', 'grade' => '1', 'score' => 87, 'result' => 'Distinction', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Maya Ghali', 'instrument' => 'Piano', 'grade' => '2', 'score' => 77, 'result' => 'Merit', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Elise Florence Scott', 'instrument' => 'Flute', 'grade' => '5', 'score' => 75, 'result' => 'Merit', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Dean Gwyther', 'instrument' => 'Clarinet', 'grade' => '1', 'score' => 75, 'result' => 'Merit', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Imogen Mayes', 'instrument' => 'Acoustic Guitar', 'grade' => 'Initial', 'score' => 72, 'result' => 'Pass', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Niamh Keyna Anakin', 'instrument' => 'Clarinet', 'grade' => '3', 'score' => 72, 'result' => 'Pass', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Isaac Pover', 'instrument' => 'Piano', 'grade' => '1', 'score' => 70, 'result' => 'Pass', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Farrah Harper Fennell', 'instrument' => 'Piano', 'grade' => '3', 'score' => 70, 'result' => 'Pass', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11508172910', 'candidate_name' => 'Kate Leyland', 'instrument' => 'Acoustic Guitar', 'grade' => '3', 'score' => 66, 'result' => 'Pass', 'exam_date' => '2026-03-05', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],

            // ── Order 1-11508308070 — 6 Mar, Wirral School of Music ──
            ['order_number' => '1-11508308070', 'candidate_name' => 'Seth James Barraclough', 'instrument' => 'Trombone', 'grade' => '8', 'score' => 93, 'result' => 'Distinction', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Anna Martin', 'instrument' => 'Piano', 'grade' => '1', 'score' => 92, 'result' => 'Distinction', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Julia Zamirska', 'instrument' => 'Oboe', 'grade' => '4', 'score' => 88, 'result' => 'Distinction', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Sam Williamson', 'instrument' => 'Piano', 'grade' => '2', 'score' => 87, 'result' => 'Distinction', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Maya Parkinson', 'instrument' => 'Piano', 'grade' => '1', 'score' => 83, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Imogen Hughes', 'instrument' => 'Piano', 'grade' => '3', 'score' => 82, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Krystian Debek', 'instrument' => 'Violin', 'grade' => '4', 'score' => 82, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Florence Cookson', 'instrument' => 'Piano', 'grade' => '1', 'score' => 81, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Alice Jun Mei Khoo', 'instrument' => 'Cornet', 'grade' => '6', 'score' => 81, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Henry Rodway', 'instrument' => 'Piano', 'grade' => '1', 'score' => 75, 'result' => 'Merit', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Megan Parkinson', 'instrument' => 'Piano', 'grade' => '1', 'score' => 71, 'result' => 'Pass', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],
            ['order_number' => '1-11508308070', 'candidate_name' => 'Lucas Hassall', 'instrument' => 'Piano', 'grade' => '5', 'score' => 61, 'result' => 'Pass', 'exam_date' => '2026-03-06', 'teacher_name' => null, 'school_name' => 'Wirral School of Music', 'notes' => null],

            // ── Order 1-12208881501 — 7 Mar, R&P, Learn Music Ltd ──
            ['order_number' => '1-12208881501', 'candidate_name' => 'Amy Norcott', 'instrument' => 'R&P Vocals', 'grade' => '8', 'score' => 90, 'result' => 'Distinction', 'exam_date' => '2026-03-07', 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Mia Mason', 'instrument' => 'R&P Vocals', 'grade' => '7', 'score' => 81, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Jenny Capstick', 'school_name' => 'Hillside High School', 'notes' => 'Booked by Paul on behalf of Jenny Capstick'],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Pearl Fay', 'instrument' => 'R&P Vocals', 'grade' => '8', 'score' => 76, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Charlotte McVey', 'instrument' => 'R&P Vocals', 'grade' => '1', 'score' => 72, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Jenny Capstick', 'school_name' => 'Hillside High School', 'notes' => 'Booked by Paul on behalf of Jenny Capstick'],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Zachary Beswick', 'instrument' => 'R&P Guitar', 'grade' => '4', 'score' => 66, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Zach Hughes', 'instrument' => 'R&P Guitar', 'grade' => 'Initial', 'score' => 63, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Daniel Rogers', 'school_name' => 'Pulse Music School', 'notes' => null],
            ['order_number' => '1-12208881501', 'candidate_name' => 'Lilly-Mae Dibbert', 'instrument' => 'R&P Vocals', 'grade' => '1', 'score' => 63, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Jenny Capstick', 'school_name' => 'Hillside High School', 'notes' => 'Booked by Paul on behalf of Jenny Capstick'],

            // ── Order 1-11478141779 — 7 Mar, R&P, Learn Music Ltd ──
            ['order_number' => '1-11478141779', 'candidate_name' => 'Thomas Gander', 'instrument' => 'R&P Guitar', 'grade' => '2', 'score' => 82, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Roxanne Twomey', 'school_name' => 'School of Rox', 'notes' => null],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Alfie Coburn', 'instrument' => 'R&P Guitar', 'grade' => '2', 'score' => 78, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Roxanne Twomey', 'school_name' => 'School of Rox', 'notes' => null],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Francesca Lee', 'instrument' => 'R&P Guitar', 'grade' => '1', 'score' => 78, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Roxanne Twomey', 'school_name' => 'School of Rox', 'notes' => null],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Jacob Thomas Leslie', 'instrument' => 'R&P Guitar', 'grade' => 'Initial', 'score' => 76, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Gillian Leslie', 'school_name' => null, 'notes' => 'Parent applicant'],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Jasper Christian O\'Malley', 'instrument' => 'R&P Guitar', 'grade' => '8', 'score' => 75, 'result' => 'Merit', 'exam_date' => '2026-03-07', 'teacher_name' => 'Adrian O\'Malley', 'school_name' => null, 'notes' => 'Parent applicant'],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Jemima Claire Reed', 'instrument' => 'R&P Vocals', 'grade' => '5', 'score' => 70, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Daniel Carty', 'instrument' => 'R&P Drums', 'grade' => '1', 'score' => 70, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Roxanne Twomey', 'school_name' => 'School of Rox', 'notes' => null],
            ['order_number' => '1-11478141779', 'candidate_name' => 'Philip Martin Gazdecki', 'instrument' => 'R&P Guitar', 'grade' => '2', 'score' => 60, 'result' => 'Pass', 'exam_date' => '2026-03-07', 'teacher_name' => 'Clare Keeling', 'school_name' => 'Learn Music Ltd', 'notes' => null],
        ];
    }
}
