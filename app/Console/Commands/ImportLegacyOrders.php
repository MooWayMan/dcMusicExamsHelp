<?php
// app/Console/Commands/ImportLegacyOrders.php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyOrders extends Command
{
    protected $signature = 'orders:import-legacy
                            {--dry-run : Preview changes without saving}
                            {--truncate : Clear orders before importing}';

    protected $description = 'Import orders safely from legacy source database';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');

        $source = DB::connection('source_pgsql');

        if ($truncate) {
            if ($dryRun) {
                $this->warn('DRY RUN: would truncate orders.');
            } else {
                Order::truncate();
                $this->info('Orders table truncated.');
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $orders = $source->table('orders')
            ->select([
                'trinity_order_number',
                'requested_start_date',
                'delivery_method',
                'subject_area',
                'candidates',
                'venue',
                'order_status',
                'applicant_name',
                'applicant_email',
            ])
            ->whereNotNull('trinity_order_number')
            ->orderBy('trinity_order_number')
            ->get();

        foreach ($orders as $row) {
            $orderNumber = trim((string) $row->trinity_order_number);

            if ($orderNumber === '') {
                $skipped++;
                continue;
            }

            $existing = Order::where('trinity_order_number', $orderNumber)->first();

            $payload = [
                'trinity_order_number' => $orderNumber,
                'requested_start_date' => $row->requested_start_date,
                'delivery_method' => $row->delivery_method,
                'subject_area' => $row->subject_area,
                'candidates' => $row->candidates,
                'venue' => $row->venue,
                'order_status' => $row->order_status,
                'applicant_name' => $row->applicant_name,
                'applicant_email' => $row->applicant_email,
                'notes' => null,
            ];

            if ($dryRun) {
                if ($existing) {
                    $this->line("Would update order: {$orderNumber}");
                    $updated++;
                } else {
                    $this->line("Would create order: {$orderNumber}");
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
                Order::create($payload);
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
            ]
        );

        $this->info('Legacy orders import complete.');

        return self::SUCCESS;
    }
}