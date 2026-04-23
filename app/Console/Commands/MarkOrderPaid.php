<?php

// app/Console/Commands/MarkOrderPaid.php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class MarkOrderPaid extends Command
{
    protected $signature = 'orders:mark-paid
        {trinity_order_number? : A single Trinity order number, e.g. 1-14163844479 (omit when using --batch)}
        {--amount= : Net amount paid by Trinity for the single order (defaults to orders.commission_amount)}
        {--batch= : Comma-separated list of order:amount pairs, e.g. 1-14163844479:13.60,1-14835557379:13.60}
        {--paid-date= : Remittance date from the Trinity Finance statement (YYYY-MM-DD)}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Mark Trinity orders as commission-paid based on a remittance advice PDF';

    public function handle(): int
    {
        $paidDate = $this->option('paid-date') ?: now()->toDateString();
        $dryRun = $this->option('dry-run');
        $batch = $this->option('batch');
        $single = $this->argument('trinity_order_number');

        if ($batch && $single) {
            $this->error('Pass either a single order argument OR --batch, not both.');
            return self::FAILURE;
        }

        if (! $batch && ! $single) {
            $this->error('Must pass a trinity_order_number argument or --batch option.');
            return self::FAILURE;
        }

        $pairs = $batch
            ? $this->parseBatch($batch)
            : [[$single, $this->option('amount')]];

        if ($pairs === null) {
            return self::FAILURE;
        }

        $grandTotal = 0.0;
        $ordersMarked = 0;

        foreach ($pairs as [$orderNumber, $amount]) {
            $orders = Order::where('trinity_order_number', $orderNumber)->get();

            if ($orders->isEmpty()) {
                $this->error("No order found with trinity_order_number = {$orderNumber}");
                continue;
            }

            if ($orders->count() > 1) {
                // Prod has no unique constraint on trinity_order_number — warn but proceed,
                // marking all duplicate rows as paid so reconciliation stays complete.
                $this->warn("Found {$orders->count()} rows for {$orderNumber} (duplicates). Marking all as paid.");
            }

            foreach ($orders as $order) {
                $paidAmount = $amount !== null ? (float) $amount : (float) $order->commission_amount;

                $this->info("Order {$order->trinity_order_number} (id {$order->id}): paid £" . number_format($paidAmount, 2) . " on {$paidDate}");

                if (! $dryRun) {
                    $order->update([
                        'commission_paid_at' => $paidDate,
                        'commission_paid_amount' => $paidAmount,
                    ]);
                }

                $grandTotal += $paidAmount;
                $ordersMarked++;
            }
        }

        if (count($pairs) > 1) {
            $this->newLine();
            $this->info("Marked {$ordersMarked} order(s) paid — total £" . number_format($grandTotal, 2));
        }

        if ($dryRun) {
            $this->warn('Dry-run: no rows updated.');
        }

        return self::SUCCESS;
    }

    /**
     * Parse --batch=order:amount,order:amount,... into [[order, amount], ...].
     * Returns null on malformed input (error already reported).
     */
    private function parseBatch(string $batch): ?array
    {
        $pairs = [];
        foreach (explode(',', $batch) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            if (! str_contains($entry, ':')) {
                $this->error("Malformed --batch entry '{$entry}' — expected order:amount (e.g. 1-14163844479:13.60)");
                return null;
            }
            [$order, $amount] = explode(':', $entry, 2);
            $order = trim($order);
            $amount = trim($amount);
            if ($order === '' || ! is_numeric($amount)) {
                $this->error("Malformed --batch entry '{$entry}' — order and amount must both be set; amount must be numeric");
                return null;
            }
            $pairs[] = [$order, $amount];
        }
        return $pairs;
    }
}
