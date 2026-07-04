<?php

use App\Models\Subscriber;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Account email preferences — the "change any time" toggle on the profile
// settings page. Consent lives on the matching subscribers row (by email).
// ──────────────────────────────────────────

test('the profile page exposes the current marketing consent state', function () {
    $user = User::factory()->create(['role' => 'teacher']);
    Subscriber::create([
        'name' => $user->name,
        'email' => $user->email,
        'source' => 'account_registration',
        'subscribed_at' => now(),
        'marketing_consent_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('marketingConsent', true));
});

test('a user can opt in, creating a consented subscriber row', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->patch(route('email-preferences.update'), ['marketing_consent' => true])
        ->assertRedirect(route('profile.edit'));

    $sub = Subscriber::where('email', $user->email)->first();
    expect($sub)->not->toBeNull();
    expect($sub->source)->toBe('account_settings');
    expect($sub->marketing_consent_at)->not->toBeNull();
});

test('a user can withdraw consent, clearing the timestamp but keeping the subscriber', function () {
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

    $sub = Subscriber::where('email', $user->email)->first();
    expect($sub)->not->toBeNull();
    expect($sub->marketing_consent_at)->toBeNull();
    expect($sub->unsubscribed_at)->toBeNull();
});

test('opting in twice does not move the original consent timestamp', function () {
    $user = User::factory()->create(['role' => 'teacher']);
    $original = now()->subMonth();
    Subscriber::create([
        'name' => $user->name,
        'email' => $user->email,
        'source' => 'account_registration',
        'subscribed_at' => $original,
        'marketing_consent_at' => $original,
    ]);

    $this->actingAs($user)
        ->patch(route('email-preferences.update'), ['marketing_consent' => true])
        ->assertRedirect(route('profile.edit'));

    $sub = Subscriber::where('email', $user->email)->first();
    expect($sub->marketing_consent_at->timestamp)->toBe($original->timestamp);
});

test('guests cannot update email preferences', function () {
    $this->patch(route('email-preferences.update'), ['marketing_consent' => true])
        ->assertRedirect(route('login'));
});
