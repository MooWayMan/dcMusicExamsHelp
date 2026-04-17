<?php
// app/Console/Commands/ImportLegacyExamEntries.php

namespace App\Console\Commands;

use App\Models\ExamEntry;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyExamEntries extends Command
{
    protected $signature = 'exam-entries:import-legacy
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Import safe legacy exam entry/result data into the refactor exam_entries table';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $source = DB::connection('production_import');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $missingOrders = 0;

        $rows = $source->table('exam_entries')
            ->select([
                'order_id',
                'grade',
                'subject_area',
                'delivery_method',
                'result',
                'exam_date',
                'notes',
                'score',
                'candidate_name',
                'teacher_name',
                'school_name',
                'show_full_name',
                'show_on_thank_you',
                'candidate_number',
                'fee',
            ])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $candidateNumber = trim((string) ($row->candidate_number ?? ''));
            $candidateName = trim((string) ($row->candidate_name ?? ''));
            $legacyOrderId = $row->order_id;

            if (! $legacyOrderId || $candidateName === '') {
                $skipped++;
                continue;
            }

            // Map legacy order_id -> legacy order number -> current refactor order
            $legacyOrder = $source->table('orders')
                ->select('trinity_order_number')
                ->where('id', $legacyOrderId)
                ->first();

            if (! $legacyOrder || empty($legacyOrder->trinity_order_number)) {
                $missingOrders++;
                continue;
            }

            $order = Order::where('trinity_order_number', $legacyOrder->trinity_order_number)->first();

            if (! $order) {
                $missingOrders++;
                continue;
            }

            $payload = [
                'order_id' => $order->id,
                'grade' => $row->grade,
                'subject_area' => $row->subject_area,
                'delivery_method' => $row->delivery_method,
                'result' => $row->result,
                'exam_date' => $row->exam_date,
                'notes' => $row->notes,
                'score' => $row->score,
                'candidate_name' => $row->candidate_name,
                'teacher_name' => $row->teacher_name,
                'school_name' => $row->school_name,
                'show_full_name' => (bool) ($row->show_full_name ?? false),
                'show_on_thank_you' => (bool) ($row->show_on_thank_you ?? true),
                'candidate_number' => $row->candidate_number,
                'fee' => $row->fee,
            ];

            $existing = null;

            if ($candidateNumber !== '') {
                $existing = ExamEntry::where('order_id', $order->id)
                    ->where('candidate_number', $candidateNumber)
                    ->first();
            }

            if (! $existing) {
                $existing = ExamEntry::where('order_id', $order->id)
                    ->where('candidate_name', $candidateName)
                    ->where(function ($q) use ($row) {
                        if ($row->exam_date) {
                            $q->whereDate('exam_date', $row->exam_date);
                        }
                    })
                    ->first();
            }

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would update legacy entry: {$candidateName}");
                    $updated++;
                } else {
                    $this->line("Would create legacy entry: {$candidateName}");
                    $created++;
                }
                continue;
            }

            if ($existing) {
                $existing->fill($payload);

                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                ExamEntry::create($payload);
                $created++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped/Unchanged', $skipped],
                ['Missing Orders', $missingOrders],
            ]
        );

        $this->info('Legacy exam entry import complete.');

        return self::SUCCESS;
    }
}