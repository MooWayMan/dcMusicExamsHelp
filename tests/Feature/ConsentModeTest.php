<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Google Consent Mode v2 — bootstrap guard
// ──────────────────────────────────────────
//
// Context: 2026-06-25. The 15 Jun ads audit found £508 of Google clicks
// converting to 0 *measured* conversions. The pipes were fine — the gap
// was the cookie banner: GA4 + Meta only loaded AFTER "Accept", so every
// visitor who declined (or hadn't chosen yet) was invisible to Google.
//
// Fix: gtag.js now loads on EVERY page with Consent Mode defaults set to
// denied. In that state Google sets no cookies and stores no identifiers
// (GDPR/PECR safe) but sends cookieless pings that let Google Ads MODEL
// the decliners' conversions. accept()/decline() then fire a consent
// update (that part is client-side JS, not covered here).
//
// These tests pin the server-rendered bootstrap so a future edit can't
// silently revert to the old "only load on accept" hard gate.

// Tracking (GA4 + Meta) is gated to production (15 Jul 2026 — internal-traffic
// exclusion). Force the app env to production for the "it loads" assertions.
beforeEach(function () {
    $this->app['env'] = 'production';
});

test('the root view sets Consent Mode defaults before loading gtag', function () {
    $this->get('/')
        ->assertStatus(200)
        // Consent defaults must be declared (the heart of Consent Mode).
        ->assertSee("gtag('consent', 'default'", false)
        ->assertSee('analytics_storage', false)
        ->assertSee('ad_user_data', false)
        ->assertSee('ad_personalization', false);
});

test('gtag.js loads unconditionally, not behind an accepted gate', function () {
    $response = $this->get('/')->assertStatus(200);

    // The library must be requested on every page load.
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W', false);

    // Guard against the old hard gate creeping back: the GA loader must NOT
    // be wrapped in `=== 'accepted'`. (The Meta Pixel legitimately still
    // uses that check, so we assert on the gtag src line's gate instead.)
    $html = $response->getContent();
    $gaPos = strpos($html, "gtag/js?id=G-TZJ8ZCZW3W");
    $consentPos = strpos($html, "gtag('consent', 'default'");
    expect($gaPos)->not->toBeFalse();
    expect($consentPos)->not->toBeFalse();
    // Consent defaults are declared before the library is appended.
    expect($consentPos)->toBeLessThan($gaPos);
});

test('the same GA4 measurement id is preserved (no property change)', function () {
    // Sanity guard: Consent Mode must reuse the existing property, never
    // silently swap to a new measurement id (which would orphan history).
    $this->get('/')
        ->assertStatus(200)
        ->assertSee('G-TZJ8ZCZW3W', false);
});

// ──────────────────────────────────────────
// Production-only gate — dev/staging never load tracking
// ──────────────────────────────────────────
// Internal-traffic exclusion (15 Jul 2026): off production, the gtag() shim is
// still defined (so consumer JS stays safe) but the GA library + Meta Pixel are
// never requested, so school WiFi / staging smoke-tests can't skew the data.

test('outside production the GA library and Meta Pixel are NOT loaded', function () {
    $this->app['env'] = 'staging';

    $response = $this->get('/')->assertStatus(200);

    // The external tags must not be requested off-production.
    $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=G-TZJ8ZCZW3W', false);
    $response->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);
    // Consent Mode defaults are part of the gated block, so they're absent too.
    $response->assertDontSee("gtag('consent', 'default'", false);

    // But the safe no-op gtag() shim is still present so calls never throw.
    $response->assertSee('window.gtag = gtag', false)
        ->assertSee('window.__TRACKING_ENABLED__', false);
});
