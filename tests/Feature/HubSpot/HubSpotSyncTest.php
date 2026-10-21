<?php

use App\Jobs\SyncSubscriberToHubSpot;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\HubSpot\HubSpotClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Dispatch wiring — the consent moments that should push to HubSpot.
// ──────────────────────────────────────────

test('registering with the opt-in ticked syncs to HubSpot after the response', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Bus::fake();

    $this->post(route('register.store'), [
        'name' => 'Connie Consent',
        'email' => 'connie@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'marketing_consent' => true,
    ])->assertRedirect();

    Bus::assertDispatchedAfterResponse(SyncSubscriberToHubSpot::class, fn ($job) => $job->subscriber->email === 'connie@example.com');
});

test('registering without the opt-in still syncs to HubSpot as service-eligible (no marketing consent)', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Bus::fake();

    $this->post(route('register.store'), [
        'name' => 'Nora NoThanks',
        'email' => 'nora@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    Bus::assertDispatchedAfterResponse(
        SyncSubscriberToHubSpot::class,
        fn ($job) => $job->subscriber->email === 'nora@example.com'
            && $job->serviceEligible === true
            && is_null($job->subscriber->marketing_consent_at)
    );
});

test('opting in from the profile page syncs to HubSpot after the response', function () {
    Bus::fake();
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->patch(route('email-preferences.update'), ['marketing_consent' => true])
        ->assertRedirect(route('profile.edit'));

    Bus::assertDispatchedAfterResponse(SyncSubscriberToHubSpot::class);
});

test('withdrawing consent from the profile page still syncs so the opt-out propagates', function () {
    Bus::fake();
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

    Bus::assertDispatchedAfterResponse(SyncSubscriberToHubSpot::class);
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

test('a service-eligible subscriber with no marketing consent is upserted with the service flag on and marketing off', function () {
    config([
        'services.hubspot.token' => 'test-token',
        'services.hubspot.base_url' => 'https://api.hubapi.com',
        'services.hubspot.consent_property' => 'app_marketing_consent',
        'services.hubspot.service_property' => 'service_admin_updates',
    ]);
    Http::fake([
        'api.hubapi.com/*' => Http::response(['results' => [['id' => '99002']]], 200),
    ]);

    $subscriber = Subscriber::create([
        'name' => 'Sam Service',
        'email' => 'sam@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => null,
    ]);

    (new SyncSubscriberToHubSpot($subscriber, serviceEligible: true))->handle(app(HubSpotClient::class));

    Http::assertSent(function ($request) {
        $body = $request->data()['inputs'][0];

        return $body['properties']['app_marketing_consent'] === 'false'
            && $body['properties']['service_admin_updates'] === 'true';
    });

    expect($subscriber->fresh()->hubspot_contact_id)->toBe('99002');
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

test('the backfill syncs every account holder at once, creating a subscriber row where none exists', function () {
    Bus::fake();

    // Account holder who already has a subscriber row (from an earlier flow).
    User::factory()->create(['email' => 'ben@example.com', 'role' => 'teacher']);
    Subscriber::create([
        'name' => 'Ben Both',
        'email' => 'ben@example.com',
        'source' => 'account_registration',
        'subscribed_at' => now(),
    ]);

    // Account holder with NO subscriber row yet — the backfill must create one
    // and still run the service sync (this is the case the old query missed).
    User::factory()->create(['name' => 'Dan Dashboard', 'email' => 'dan@example.com', 'role' => 'teacher']);

    // Internal admin — must be skipped, they send the notices, not receive them.
    User::factory()->create(['email' => 'paul@example.com', 'role' => 'admin']);

    // Marketing-only subscriber who never registered — not an account holder.
    Subscriber::create([
        'name' => 'Cara Checklist',
        'email' => 'cara@example.com',
        'source' => 'trinity_checklist',
        'subscribed_at' => now(),
    ]);

    $this->artisan('hubspot:backfill-service-contacts')->assertSuccessful();

    // Ben + Dan only — admin and the marketing-only subscriber are excluded.
    Bus::assertDispatchedSync(SyncSubscriberToHubSpot::class, 2);
    Bus::assertDispatchedSync(
        SyncSubscriberToHubSpot::class,
        fn ($job) => $job->subscriber->email === 'ben@example.com' && $job->serviceEligible === true
    );
    Bus::assertDispatchedSync(
        SyncSubscriberToHubSpot::class,
        fn ($job) => $job->subscriber->email === 'dan@example.com' && $job->serviceEligible === true
    );
    Bus::assertNotDispatchedSync(
        SyncSubscriberToHubSpot::class,
        fn ($job) => $job->subscriber->email === 'paul@example.com'
    );
    Bus::assertNotDispatchedSync(
        SyncSubscriberToHubSpot::class,
        fn ($job) => $job->subscriber->email === 'cara@example.com'
    );

    // The account holder who lacked a subscriber row now has one.
    expect(Subscriber::where('email', 'dan@example.com')->exists())->toBeTrue();
});

// ──────────────────────────────────────────
// No queue worker (21 Oct 2026). The sync runs in the same request, after the
// page has gone back, because a worker polling the database every 10 seconds
// for about two signups a month kept the serverless database awake and billed
// around the clock. These prove it still reaches HubSpot with no worker, and
// that nothing can quietly go back onto a queue nobody is reading.
// ──────────────────────────────────────────

test('a signup reaches HubSpot with no queue worker running, and leaves nothing on the queue', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    config([
        'services.hubspot.token' => 'test-token',
        'services.hubspot.base_url' => 'https://api.hubapi.com',
        'services.hubspot.consent_property' => 'app_marketing_consent',
        // The real queue, not the test default — if anything were queued it
        // would sit in the jobs table, which is what this checks.
        'queue.default' => 'database',
    ]);
    Http::fake([
        'api.hubapi.com/*' => Http::response(['results' => [['id' => '99100']]], 200),
    ]);

    $this->post(route('register.store'), [
        'name' => 'Wendy Workerless',
        'email' => 'wendy@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'marketing_consent' => true,
    ])->assertRedirect();

    expect(Subscriber::where('email', 'wendy@example.com')->value('hubspot_contact_id'))->toBe('99100')
        ->and(\Illuminate\Support\Facades\DB::table('jobs')->count())->toBe(0);
});

test('the HubSpot sync is never put on a queue', function () {
    // Two ways it could drift back: the job becoming queueable again, or a
    // caller using plain dispatch(). guardOffenders strips comments first.
    expect(is_subclass_of(SyncSubscriberToHubSpot::class, \Illuminate\Contracts\Queue\ShouldQueue::class))->toBeFalse();

    $offenders = guardOffenders('/SyncSubscriberToHubSpot::dispatch\s*\(/', []);

    expect($offenders)->toBe([]);
});

// ──────────────────────────────────────────
// Only production may reach the live portal. A local .env holding the live
// token let the test suite write 137 fake contacts into it.
// ──────────────────────────────────────────

function hubspotTokenWhen(string $appEnv): ?string
{
    $saved = [];

    foreach (['APP_ENV' => $appEnv, 'HUBSPOT_API_TOKEN' => 'pat-live-token'] as $key => $value) {
        $saved[$key] = [$_SERVER[$key] ?? null, $_ENV[$key] ?? null, getenv($key)];
        $_SERVER[$key] = $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    try {
        return (require base_path('config/services.php'))['hubspot']['token'];
    } finally {
        foreach ($saved as $key => [$server, $env, $put]) {
            if ($server === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $server;
            }

            if ($env === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $env;
            }

            putenv($put === false ? $key : "{$key}={$put}");
        }
    }
}

test('the live HubSpot token is read in production', function () {
    expect(hubspotTokenWhen('production'))->toBe('pat-live-token');
});

test('the live HubSpot token is ignored everywhere else, even when the env file holds it', function (string $appEnv) {
    expect(hubspotTokenWhen($appEnv))->toBeNull();
})->with(['testing', 'local', 'staging']);

test('registering a user in the test suite sends nothing to HubSpot', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    Http::fake();

    $this->post(route('register.store'), [
        'name' => 'Faye Fixture',
        'email' => 'faye@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'marketing_consent' => true,
    ])->assertRedirect();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'hubapi.com'));
});
