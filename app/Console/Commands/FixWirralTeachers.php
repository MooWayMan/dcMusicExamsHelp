<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Order;
use Illuminate\Console\Command;

class FixWirralTeachers extends Command
{
    protected $signature = 'data:fix-wirral-teachers';
    protected $description = 'Update Wirral order entries with teacher names from Candidate Listing PDF';

    public function handle(): int
    {
        // Wirral order: 1-11508308070
        $order = Order::where('trinity_order_number', '1-11508308070')->first();

        if (! $order) {
            $this->error('Wirral order 1-11508308070 not found.');
            return Command::FAILURE;
        }

        // Teacher attributions from Candidate Listing PDF
        // Applicant = the person who booked (teacher or parent)
        $attributions = [
            'Sam Williamson'          => ['teacher' => null, 'note' => 'Parent: Alexandra Bibby'],
            'Maya Parkinson'          => ['teacher' => null, 'note' => 'Parent: Jay Parkinson'],
            'Megan Parkinson'         => ['teacher' => null, 'note' => 'Parent: Jay Parkinson'],
            'Alice Jun Mei Khoo'      => ['teacher' => null, 'note' => 'Parent: Helen Khoo'],
            'Lucas Hassall'           => ['teacher' => 'Tracey Lea', 'note' => null],
            'Julia Zamirska'          => ['teacher' => 'Stephen Shotton', 'note' => null],
            'Florence Cookson'        => ['teacher' => 'Christopher Callaway', 'note' => null],
            'Anna Martin'             => ['teacher' => 'Christopher Callaway', 'note' => null],
            'Imogen Hughes'           => ['teacher' => 'Christopher Callaway', 'note' => null],
            'Henry Rodway'            => ['teacher' => 'Christopher Callaway', 'note' => null],
            'Krystian Debek'          => ['teacher' => 'Megan Thompson', 'note' => 'In Harmony Liverpool'],
            'Seth James Barraclough'  => ['teacher' => null, 'note' => 'Self-entry: Seth Barraclough'],
        ];

        $updated = 0;

        foreach ($attributions as $candidateName => $data) {
            $entry = ExamEntry::where('order_id', $order->id)
                ->whereRaw('LOWER(candidate_name) = ?', [strtolower($candidateName)])
                ->first();

            if (! $entry) {
                $this->warn("Entry not found: {$candidateName}");
                continue;
            }

            $changes = [];
            if ($data['teacher']) {
                $changes['teacher_name'] = $data['teacher'];
            }

            if ($changes) {
                $entry->update($changes);
                $updated++;
                $this->line("  Updated: {$candidateName} → teacher: {$data['teacher']}");
            } else {
                $this->line("  Skipped: {$candidateName} (parent booking)");
            }
        }

        $this->info("Done. {$updated} entries updated with teacher names.");
        $this->newLine();
        $this->info('Now run: php artisan data:populate-from-entries');
        $this->info('This will create teacher records for Christopher Callaway, Tracey Lea, etc.');

        return Command::SUCCESS;
    }
}
