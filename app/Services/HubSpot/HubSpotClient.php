<?php

namespace App\Services\HubSpot;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the HubSpot CRM v3 API. Only what we need: upserting a
 * contact by email so the app is the source of truth for marketing consent
 * and HubSpot mirrors it.
 *
 * Auth is a Private App token (Bearer). Nothing here fires unless a token is
 * configured — callers should check isConfigured() first (the sync job does),
 * which keeps staging/local/test blank-token environments inert.
 */
class HubSpotClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.hubspot.token'));
    }

    /**
     * Create-or-update a contact keyed on its email address and return the
     * HubSpot contact id. Uses the batch upsert endpoint (idProperty=email) so
     * a single call handles both the new-contact and existing-contact cases
     * without us having to search first.
     *
     * @param  array<string, string>  $properties  HubSpot property name => value
     */
    public function upsertContact(string $email, array $properties = []): string
    {
        $base = rtrim((string) config('services.hubspot.base_url'), '/');

        $response = Http::withToken(config('services.hubspot.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($base.'/crm/v3/objects/contacts/batch/upsert', [
                'inputs' => [[
                    'idProperty' => 'email',
                    'id' => $email,
                    'properties' => array_merge(['email' => $email], $properties),
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'HubSpot contact upsert failed ('.$response->status().'): '.$response->body()
            );
        }

        $id = $response->json('results.0.id');

        if (! $id) {
            throw new RuntimeException('HubSpot upsert returned no contact id: '.$response->body());
        }

        return (string) $id;
    }
}
