<?php

// database/seeders/CommissionBackfillQ1Seeder.php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

/**
 * One-off backfill of Q1 2026 Trinity commission payments.
 *
 * Data sourced from five Trinity Finance remittance advice PDFs dated
 * 27 Jan, 24 Feb, 17 Mar, 19 Mar, and 2 Apr 2026.
 *
 * Safe to run multiple times — updates paid fields in place, no inserts.
 * Orders not in the DB (pre-2026 entries Paul chose not to log) are skipped.
 */
class CommissionBackfillQ1Seeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            // 24 February 2026 remittance — CEB012009 DGD batch
            ['order' => '1-11490766629', 'amount' => 24.00, 'paid_at' => '2026-02-24'],
            ['order' => '1-11478522619', 'amount' => 11.00, 'paid_at' => '2026-02-24'],
            ['order' => '1-11543471049', 'amount' => 9.80,  'paid_at' => '2026-02-24'],

            // 17 March 2026 remittance — F2F order with four adjustments, net £173.04
            ['order' => '1-11478141779', 'amount' => 173.04, 'paid_at' => '2026-03-17'],

            // 19 March 2026 remittance — F2F Liverpool sessions
            ['order' => '1-11508172910', 'amount' => 257.04, 'paid_at' => '2026-03-19'],
            ['order' => '1-11508308070', 'amount' => 270.76, 'paid_at' => '2026-03-19'],

            // 2 April 2026 remittance — CEB012010 DGD batch + one F2F
            ['order' => '1-14163844479', 'amount' => 15.60, 'paid_at' => '2026-04-02'],
            ['order' => '1-13750176989', 'amount' => 39.60, 'paid_at' => '2026-04-02'],
            ['order' => '1-14243820189', 'amount' => 13.60, 'paid_at' => '2026-04-02'],
            ['order' => '1-14090535219', 'amount' => 17.60, 'paid_at' => '2026-04-02'],
            ['order' => '1-13748006149', 'amount' => 12.20, 'paid_at' => '2026-04-02'],
            ['order' => '1-13478401579', 'amount' => 13.60, 'paid_at' => '2026-04-02'],
        ];

        $matched = 0;
        $missing = [];

        foreach ($payments as $payment) {
            $affected = Order::where('trinity_order_number', $payment['order'])
                ->update([
                    'commission_paid_at' => $payment['paid_at'],
                    'commission_paid_amount' => $payment['amount'],
                ]);

            if ($affected > 0) {
                $matched++;
            } else {
                $missing[] = $payment['order'];
            }
        }

        $this->command?->info("Marked {$matched} orders as paid.");

        if (! empty($missing)) {
            $this->command?->warn('Not found in DB (skipped): ' . implode(', ', $missing));
        }
    }
}
