<?php

// app/Console/Commands/MarkOrderPaid.php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class MarkOrderPaid extends Command
{
    protected $signature = 'orders:mark-paid
        {trinity_order_number : The Trinity order number, e.g. 1-14163844479}
        {--amount= : Net amount paid by Trinity (defaults to orders.commission_amount)}
        {--paid-date= : Remittance date from the Trinity Finance statement (YYYY-MM-DD)}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Mark a Trinity order as commission-paid based on a remittance advice PDF';

    public function handle(): int
    {
        $trinityOrderNumber = $this->argument('trinity_order_number');
        $paidDate = $this->option('paid-date') ?: now()->toDateString();
        $amount = $this->option('amount');
        $dryRun = $this->option('dry-run');

        $orders = Order::where('trinity_order_number', $trinityOrderNumber)->get();

        if ($orders->isEmpty()) {
            $this->error("No order found with trinity_order_number = {$trinityOrderNumber}");
            return self::FAILURE;
        }

        if ($orders->count() > 1) {
            // Prod has no unique constraint on trinity_order_number — warn but proceed,
            // marking all duplicate rows as paid so reconciliation stays complete.
            $this->warn("Found {$orders->count()} rows for {$trinityOrderNumber} (duplicates). Marking all as paid.");
        }

        foreach ($orders as $order) {
            $paidAmount = $amount !== null ? (float) $amount : $order->commission_amount;

            $this->info("Order {$order->trinity_order_number} (id {$order->id}): paid £{$paidAmount} on {$paidDate}");

            if (! $dryRun) {
                $order->update([
                    'commission_paid_at' => $paidDate,
                    'commission_paid_amount' => $paidAmount,
                ]);
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run: no rows updated.');
        }

        return self::SUCCESS;
    }
}
