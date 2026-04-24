<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-shot backfill of order.user_id on F2F orders.
 *
 * Per reference_f2f_order_pattern.md: on F2F (delivery_method=Default/F2F)
 * the order-level user is the COORDINATOR, which is Paul. The real
 * per-candidate teacher lives on exam_entries.teacher_name. Without this
 * the admin order page shows "Teacher removed or unlinked" where it should
 * show Paul.
 *
 * Non-destructive: only touches rows where user_id IS NULL.
 */
class BackfillF2FOrderTeacher extends Command
{
    protected $signature = 'backfill:f2f-order-teacher
                            {--admin-email=musicexams@musicexams.help : Email of the admin user who coordinates F2F orders}
                            {--dry-run : Show what would change without updating}';

    protected $description = 'Set F2F orders.user_id to Paul (coordinator) where it is currently NULL';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $adminEmail = $this->option('admin-email');

        $admin = User::where('email', $adminEmail)->first();

        if (! $admin) {
            $this->error("Admin user not found: {$adminEmail}");
            return Command::FAILURE;
        }

        // Trinity's "Default" = F2F; newer data may use 'F2F' literally.
        $query = Order::whereIn('delivery_method', ['Default', 'F2F'])
            ->whereNull('user_id');

        $toUpdate = $query->count();

        if ($toUpdate === 0) {
            $this->info('No F2F orders with NULL user_id found — nothing to do.');
            return Command::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d F2F order(s) to user_id=%d (%s).',
            $dryRun ? 'Would update' : 'Updating',
            $toUpdate,
            $admin->id,
            $admin->name,
        ));

        $orders = $query->get(['id', 'trinity_order_number', 'requested_start_date', 'delivery_method']);

        foreach ($orders as $order) {
            $this->line(sprintf(
                '  %s %s  (%s, %s)',
                $dryRun ? '[dry-run]' : '✓',
                $order->trinity_order_number,
                $order->delivery_method,
                $order->requested_start_date?->format('j M Y') ?? '—',
            ));
        }

        if (! $dryRun) {
            // Raw update is faster than looping + save(); kept as explicit action.
            Order::whereIn('id', $orders->pluck('id'))->update(['user_id' => $admin->id]);
            $this->newLine();
            $this->info("Done — {$toUpdate} order(s) now linked to {$admin->name}.");
        } else {
            $this->newLine();
            $this->comment('Dry run — no changes made. Rerun without --dry-run to apply.');
        }

        return Command::SUCCESS;
    }
}
