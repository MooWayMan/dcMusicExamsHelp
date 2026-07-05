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
 *     previously synced (hubspot_contact_id present); consent property = false
 *     so the "All Marketing Subscribers" smart list drops them. We never create
 *     a brand-new HubSpot contact for someone who has no consent.
 *
 * No-ops entirely when no HubSpot token is configured (staging/local/test),
 * matching the blank-Mailchimp-keys convention.
 */
class SyncSubscriberToHubSpot implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscriber $subscriber) {}

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

        // Opted out and never synced => nothing to do in HubSpot.
        if (! $hasConsent && ! $subscriber->hubspot_contact_id) {
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

        return $properties;
    }
}
