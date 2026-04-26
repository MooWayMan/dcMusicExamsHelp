<?php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Local-only fixture: take N existing single-instrument students and add
 * extra exam_entries on a different instrument, so the Students chips UI
 * can be eyeballed against real-looking data.
 *
 * All extra entries are attached to a single throwaway order
 * `LOCAL-MULTI-INST-TEST` — cleanup is one DELETE on that order.
 */
class SeedLocalMultiInstrumentStudents extends Command
{
    protected const TAG = 'LOCAL-MULTI-INST-TEST';

    protected $signature = 'local:seed-multi-instruments
                            {--count=5 : How many students to convert into multi-instrument students}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Local fixture — add extra-instrument exam_entries so students chips UI can be eyeballed';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production. This command is local-only.');

            return Command::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $dryRun = (bool) $this->option('dry-run');

        $instruments = Instrument::all();
        if ($instruments->count() < 2) {
            $this->error('Need at least 2 instruments seeded to create multi-instrument students.');

            return Command::FAILURE;
        }

        // Find students whose exam_entries reference exactly one distinct
        // instrument. Those are the candidates we can promote to multi.
        $candidates = Student::query()
            ->withCount(['examEntries as distinct_instrument_count' => function ($q) {
                $q->select(DB::raw('COUNT(DISTINCT instrument_id)'));
            }])
            ->has('examEntries')
            ->get()
            ->where('distinct_instrument_count', '=', 1)
            ->take($count);

        if ($candidates->isEmpty()) {
            $this->warn('No single-instrument students found — nothing to do.');
            $this->line('(Either everyone is already multi-instrument or there are no exam_entries yet.)');

            return Command::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d student(s) into multi-instrument fixtures.',
            $dryRun ? 'Would convert' : 'Converting',
            $candidates->count(),
        ));

        // One throwaway order holds all the extra entries — that way cleanup
        // is `Order::where('trinity_order_number', self::TAG)->forceDelete()`.
        $fixtureOrder = null;
        if (! $dryRun) {
            $fixtureOrder = Order::firstOrCreate(
                ['trinity_order_number' => self::TAG],
                [
                    'delivery_method' => 'Digital',
                    'subject_area'    => 'Music',
                    'order_status'    => 'Submitted',
                    'requested_start_date' => now()->toDateString(),
                    'commission_rate' => 0,
                    'commission_amount' => 0,
                    'candidates'      => 0,
                    'venue'           => 'LOCAL FIXTURE — safe to delete',
                    'notes'           => 'Created by local:seed-multi-instruments. Delete this order to undo.',
                ],
            );
        }

        $added = 0;

        foreach ($candidates as $student) {
            $existingInstrumentIds = ExamEntry::where('student_id', $student->id)
                ->whereNotNull('instrument_id')
                ->pluck('instrument_id')
                ->unique()
                ->all();

            $availableInstruments = $instruments->whereNotIn('id', $existingInstrumentIds);
            if ($availableInstruments->isEmpty()) {
                continue;
            }

            // Pick 1-2 different instruments. Two adds visual variety to the
            // chips column without going overboard.
            $extraInstruments = $availableInstruments->random(min(2, $availableInstruments->count()));
            if (! $extraInstruments instanceof \Illuminate\Support\Collection) {
                $extraInstruments = collect([$extraInstruments]);
            }

            foreach ($extraInstruments as $instrument) {
                $this->line(sprintf(
                    '  %s %s  +  %s',
                    $dryRun ? '[dry-run]' : '✓',
                    $student->full_name,
                    $instrument->name,
                ));

                if (! $dryRun) {
                    ExamEntry::create([
                        'order_id'        => $fixtureOrder->id,
                        'student_id'      => $student->id,
                        'instrument_id'   => $instrument->id,
                        'candidate_name'  => $student->full_name,
                        'grade'           => '1',
                        'subject_area'    => 'Music',
                        'delivery_method' => 'Digital',
                        'source'          => 'manual',
                        'notes'           => 'Local fixture — '.self::TAG,
                    ]);
                }
                $added++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->comment("Dry run — would add {$added} exam_entries. Rerun without --dry-run to apply.");
        } else {
            $this->info("Added {$added} exam_entries on order {$fixtureOrder->trinity_order_number}.");
            $this->line("To undo: Order::where('trinity_order_number', '".self::TAG."')->forceDelete();");
        }

        return Command::SUCCESS;
    }
}
