<?php

namespace App\Jobs;

use App\Models\Subscriber;
use App\Services\HubSpot\HubSpotClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

/**
 * Pushes a single subscriber's marketing-consent state into HubSpot so the CRM
 * mirrors the app (which is the source of truth). Dispatched when someone opts
 * in at registration or toggles the preference on their profile, and also on
 * withdrawal so an opt-out propagates.
 *
 * Consent model:
 *   - opted IN  (marketing_consent_at set)  => upsert the contact, consent
 *     property = true. Creates the contact if HubSpot has never seen them.
 *   - opted OUT (marketing_consent_at null) => only update if they were
 *     previously synced (hubspot_contact_id present) OR they're service-eligible
 *     (below); consent property = false so the "All Marketing Subscribers" smart
 *     list drops them.
 *
 * Account holders (exam-admin relationship) are additionally flagged
 * service-eligible via $serviceEligible, so a legitimate-interest "service/admin
 * updates" list can reach them regardless of marketing consent. This is the one
 * case where we create a HubSpot contact for someone with no marketing consent.
 *
 * No-ops entirely when no HubSpot token is configured (staging/local/test),
 * matching the blank-Mailchimp-keys convention.
 */
class SyncSubscriberToHubSpot implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscriber $subscriber, public bool $serviceEligible = false) {}

    public function handle(HubSpotClient $hubspot): void
    {
        if (! $hubspot->isConfigured()) {
            return;
        }

        // Re-read fresh state — consent may have changed between dispatch and run.
        $subscriber = $this->subscriber->fresh();

        if (! $subscriber) {
            return;
        }

        $hasConsent = ! is_null($subscriber->marketing_consent_at);

        // Nothing to do only when there's no marketing consent, this isn't a
        // service-eligible account holder, and we've never synced them before.
        if (! $hasConsent && ! $this->serviceEligible && ! $subscriber->hubspot_contact_id) {
            return;
        }

        $properties = $this->contactProperties($subscriber->name, $hasConsent);

        $contactId = $hubspot->upsertContact($subscriber->email, $properties);

        $subscriber->forceFill([
            'hubspot_contact_id' => $contactId,
            'hubspot_synced_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function contactProperties(string $name, bool $hasConsent): array
    {
        $name = trim($name);
        $first = Str::before($name, ' ');
        $last = trim(Str::after($name, ' '));

        $properties = ['firstname' => $first !== '' ? $first : $name];

        if ($last !== '' && $last !== $name) {
            $properties['lastname'] = $last;
        }

        // Only mirror consent when a property has been configured in HubSpot.
        if ($property = config('services.hubspot.consent_property')) {
            $properties[$property] = $hasConsent ? 'true' : 'false';
        }

        // Flag account holders for the legitimate-interest service list. Only
        // ever set true here — we never strip the flag off an existing contact.
        if ($this->serviceEligible && $serviceProperty = config('services.hubspot.service_property')) {
            $properties[$serviceProperty] = 'true';
        }

        return $properties;
    }
}
