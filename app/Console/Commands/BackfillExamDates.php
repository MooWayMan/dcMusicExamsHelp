<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillExamDates extends Command
{
    protected $signature = 'orders:backfill-exam-dates {--dry-run : Show how many rows would change without writing}';

    protected $description = 'Copy orders.requested_start_date to exam_entries.exam_date where exam_date is null';

    public function handle(): int
    {
        $candidates = DB::table('exam_entries as ee')
            ->join('orders as o', 'ee.order_id', '=', 'o.id')
            ->whereNull('ee.exam_date')
            ->whereNotNull('o.requested_start_date')
            ->count();

        $this->info("Found {$candidates} exam_entries with null exam_date and a non-null requested_start_date on the parent order.");

        if ($candidates === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no rows updated.');
            return self::SUCCESS;
        }

        $affected = DB::update(<<<'SQL'
            UPDATE exam_entries ee
            SET exam_date = o.requested_start_date
            FROM orders o
            WHERE ee.order_id = o.id
              AND ee.exam_date IS NULL
              AND o.requested_start_date IS NOT NULL
        SQL);

        $this->info("Updated {$affected} exam_entries.");

        return self::SUCCESS;
    }
}
