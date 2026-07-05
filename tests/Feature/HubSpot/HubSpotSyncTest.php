<?php

use App\Jobs\SyncSubscriberToHubSpot;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\HubSpot\HubSpotClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Dispatch wiring — the consent moments that should push to HubSpot.
// ──────────────────────────────────────────

test('registering with the opt-in ticked queues a HubSpot sync', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Queue::fake();

    $this->post(route('register.store'), [
        'name' => 'Connie Consent',
        'email' => 'connie@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'marketing_consent' => true,
    ])->assertRedirect();

    Queue::assertPushed(SyncSubscriberToHubSpot::class, fn ($job) => $job->subscriber->email === 'connie@example.com');
});

test('registering without the opt-in does not queue a HubSpot sync', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Queue::fake();

    $this->post(route('register.store'), [
        'name' => 'Nora NoThanks',
        'email' => 'nora@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    Queue::assertNotPushed(SyncSubscriberToHubSpot::class);
});

test('opting in from the profile page queues a HubSpot sync', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->patch(route('email-preferences.update'), ['marketing_consent' => true])
        ->assertRedirect(route('profile.edit'));

    Queue::assertPushed(SyncSubscriberToHubSpot::class);
});

test('withdrawing consent from the profile page still queues a sync so the opt-out propagates', function () {
    Queue::fake();
    $user = User::factory()->create(['role' => 'teacher']);
    Subscriber::create([
        'name' => $user->name,
        'email' => $user->email,
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch(route('email-preferences.update'), [])
        ->assertRedirect(route('profile.edit'));

    Queue::assertPushed(SyncSubscriberToHubSpot::class);
});

// ──────────────────────────────────────────
// Job behaviour — what actually hits the HubSpot API.
// ──────────────────────────────────────────

test('the job upserts a consented subscriber and records the returned contact id', function () {
    config([
        'services.hubspot.token' => 'test-token',
        'services.hubspot.base_url' => 'https://api.hubapi.com',
        'services.hubspot.consent_property' => 'app_marketing_consent',
    ]);
    Http::fake([
        'api.hubapi.com/*' => Http::response(['results' => [['id' => '99001']]], 200),
    ]);

    $subscriber = Subscriber::create([
        'name' => 'Connie Consent',
        'email' => 'connie@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => now(),
    ]);

    (new SyncSubscriberToHubSpot($subscriber))->handle(app(HubSpotClient::class));

    Http::assertSent(function ($request) {
        $body = $request->data()['inputs'][0];

        return str_contains($request->url(), '/crm/v3/objects/contacts/batch/upsert')
            && $body['idProperty'] === 'email'
            && $body['id'] === 'connie@example.com'
            && $body['properties']['firstname'] === 'Connie'
            && $body['properties']['lastname'] === 'Consent'
            && $body['properties']['app_marketing_consent'] === 'true';
    });

    $subscriber->refresh();
    expect($subscriber->hubspot_contact_id)->toBe('99001');
    expect($subscriber->hubspot_synced_at)->not->toBeNull();
});

test('the job no-ops entirely when no HubSpot token is configured', function () {
    config([
        'services.hubspot.token' => null,
        'services.hubspot.consent_property' => 'app_marketing_consent',
    ]);
    Http::fake();

    $subscriber = Subscriber::create([
        'name' => 'Connie Consent',
        'email' => 'connie@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => now(),
    ]);

    (new SyncSubscriberToHubSpot($subscriber))->handle(app(HubSpotClient::class));

    Http::assertNothingSent();
    expect($subscriber->fresh()->hubspot_contact_id)->toBeNull();
});

test('withdrawal propagates as consent=false for an already-synced contact', function () {
    config([
        'services.hubspot.token' => 'test-token',
        'services.hubspot.base_url' => 'https://api.hubapi.com',
        'services.hubspot.consent_property' => 'app_marketing_consent',
    ]);
    Http::fake([
        'api.hubapi.com/*' => Http::response(['results' => [['id' => '99001']]], 200),
    ]);

    $subscriber = Subscriber::create([
        'name' => 'Wanda Withdrawn',
        'email' => 'wanda@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => null,
        'hubspot_contact_id' => '99001',
    ]);

    (new SyncSubscriberToHubSpot($subscriber))->handle(app(HubSpotClient::class));

    Http::assertSent(fn ($request) => $request->data()['inputs'][0]['properties']['app_marketing_consent'] === 'false');
});

test('withdrawal for a contact never synced to HubSpot does nothing', function () {
    config([
        'services.hubspot.token' => 'test-token',
        'services.hubspot.base_url' => 'https://api.hubapi.com',
        'services.hubspot.consent_property' => 'app_marketing_consent',
    ]);
    Http::fake();

    $subscriber = Subscriber::create([
        'name' => 'Ned NeverConsented',
        'email' => 'ned@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => null,
        'hubspot_contact_id' => null,
    ]);

    (new SyncSubscriberToHubSpot($subscriber))->handle(app(HubSpotClient::class));

    Http::assertNothingSent();
});
