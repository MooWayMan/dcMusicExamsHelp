<?php

namespace App\Console\Commands;

use App\Jobs\SyncSubscriberToHubSpot;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-off backfill: push EXISTING account holders into HubSpot on the
 * legitimate-interest "service/admin updates" basis, so the service list can
 * reach everyone who holds a dashboard account — regardless of whether they
 * ever opted into marketing.
 *
 * Driven by the `users` table, because that IS the definition of an account
 * holder. Reaching them via the `subscribers` table (the old approach) missed
 * anyone who registered before the live sync existed and so never got a
 * subscriber row. For each user we find-or-create their subscriber row, then
 * queue SyncSubscriberToHubSpot with serviceEligible = true: that upserts the
 * contact and stamps the service flag without touching marketing consent.
 * Idempotent (upsert + firstOrCreate) — safe to run more than once.
 *
 * Internal `admin` accounts are skipped — they are the ones sending the
 * notices, not customers who receive them. Soft-deleted users are excluded
 * automatically.
 *
 *   php artisan hubspot:backfill-service-contacts --dry-run
 *   php artisan hubspot:backfill-service-contacts
 *
 * Run AFTER activation: the HubSpot `service_admin_updates` property + list
 * exist and `HUBSPOT_SERVICE_PROPERTY` is set on the environment. Without the
 * token/property the queued jobs simply no-op, so an early run is harmless.
 */
class BackfillServiceContacts extends Command
{
    protected $signature = 'hubspot:backfill-service-contacts
        {--dry-run : Count who would be synced without queueing anything}';

    protected $description = 'Backfill existing account holders onto the HubSpot service/admin list (legitimate interest).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->info('DRY RUN — nothing will be queued.');
        }

        if (blank(config('services.hubspot.service_property'))) {
            $this->warn('HUBSPOT_SERVICE_PROPERTY is not set — the service flag will not be written until it is. Queued jobs no-op safely in the meantime.');
        }

        $total = 0;

        User::query()
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '!=', 'admin');
            })
            ->chunkById(200, function ($users) use ($dry, &$total) {
                foreach ($users as $user) {
                    $total++;

                    if ($dry) {
                        continue;
                    }

                    $subscriber = Subscriber::firstOrCreate(
                        ['email' => $user->email],
                        [
                            'name' => $user->name,
                            'role' => $user->role,
                            'source' => 'account_registration',
                            'subscribed_at' => now(),
                        ],
                    );

                    SyncSubscriberToHubSpot::dispatch($subscriber, serviceEligible: true);
                }
            });

        $this->info($dry
            ? "Dry run complete — {$total} account holder(s) would be queued for the service list."
            : "Done — queued {$total} account holder(s) for the HubSpot service list.");

        return self::SUCCESS;
    }
}
