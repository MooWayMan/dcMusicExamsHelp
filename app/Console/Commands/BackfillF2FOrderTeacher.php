<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Deprecated. The orders.user_id column was dropped on 2026-04-26 as the
 * final piece of the unified contacts refactor — the order-side teacher
 * link now lives on `created_by_contact_id` (→ exam_contacts).
 *
 * Kept as a stub so cron/CI references don't 404; can be deleted once any
 * external schedulers are confirmed clean.
 */
class BackfillF2FOrderTeacher extends Command
{
    protected $signature = 'backfill:f2f-order-teacher';

    protected $description = '[DEPRECATED] No-op — orders.user_id was dropped 2026-04-26';

    public function handle(): int
    {
        $this->warn('backfill:f2f-order-teacher is deprecated.');
        $this->line('The orders.user_id column was dropped in the unified contacts refactor.');
        $this->line('Order → person link now lives on orders.created_by_contact_id.');

        return Command::SUCCESS;
    }
}
