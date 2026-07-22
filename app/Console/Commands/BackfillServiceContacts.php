<?php

namespace App\Console\Commands;

use App\Jobs\SyncSubscriberToHubSpot;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-off backfill: push EXISTING account holders into HubSpot on the
 * legitimate-interest "service/admin updates" basis, so the service list
 * catches everyone who registered BEFORE the service sync went live. The live
 * sync only fires on new registrations / profile toggles from now on, so the
 * back-catalogue needs this nudge once.
 *
 * Targets active subscribers that are account holders — source
 * `account_registration` OR an email that matches a registered user (that
 * second clause also catches people who first arrived via the checklist and
 * later signed up, whose source stays `trinity_checklist`). Each is queued
 * through SyncSubscriberToHubSpot with serviceEligible = true; marketing
 * consent is mirrored separately and left untouched. Idempotent (upsert) —
 * safe to run more than once.
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

        $query = Subscriber::query()
            ->whereNull('unsubscribed_at')
            ->where(function ($q) {
                $q->where('source', 'account_registration')
                    ->orWhereIn('email', fn ($sub) => $sub->from('users')->select('email'));
            });

        $total = 0;

        $query->chunkById(200, function ($subscribers) use ($dry, &$total) {
            foreach ($subscribers as $subscriber) {
                $total++;

                if (! $dry) {
                    SyncSubscriberToHubSpot::dispatch($subscriber, serviceEligible: true);
                }
            }
        });

        $this->info($dry
            ? "Dry run complete — {$total} account holder(s) would be queued for the service list."
            : "Done — queued {$total} account holder(s) for the HubSpot service list.");

        return self::SUCCESS;
    }
}
