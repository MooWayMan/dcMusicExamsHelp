<?php

// app/Console/Commands/BackfillCommissionFromPaid.php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Some orders were imported without a commission_amount, so once Trinity's
 * remittance marks them paid they show as a paid £0.00 order (the Commission
 * column reads the expected figure, which is blank). This copies the actual
 * amount Trinity paid into commission_amount for any paid order missing one,
 * so the list and totals read correctly.
 *
 * Safe + idempotent: only touches paid orders whose commission_amount is
 * null or 0, and never overwrites a real expected commission.
 */
class BackfillCommissionFromPaid extends Command
{
    protected $signature = 'orders:backfill-commission-from-paid {--dry-run : Show how many rows would change without writing}';

    protected $description = 'Set commission_amount from commission_paid_amount for paid orders that have no commission figure';

    public function handle(): int
    {
        $orders = Order::query()
            ->whereNotNull('commission_paid_at')
            ->whereNotNull('commission_paid_amount')
            ->where(fn ($q) => $q->whereNull('commission_amount')->orWhere('commission_amount', 0))
            ->get();

        $this->info("Found {$orders->count()} paid order(s) with no commission figure.");

        if ($orders->isEmpty()) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($orders as $order) {
                $this->line("  {$order->trinity_order_number}: would set commission_amount = £" . number_format((float) $order->commission_paid_amount, 2));
            }
            $this->warn('Dry-run: no rows updated.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($orders as $order) {
            $order->update(['commission_amount' => $order->commission_paid_amount]);
            $updated++;
        }

        $this->info("Updated {$updated} order(s).");

        return self::SUCCESS;
    }
}
