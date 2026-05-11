<?php

// tests/Feature/PublicFormProtectionTest.php
//
// Bot-protection tests for the three public POST endpoints — `/subscribe`,
// `/lead-magnet/subscribe`, `/contact`. These endpoints are reachable by
// anyone on the internet and (in the case of contact and lead-magnet) fire
// real outbound emails, so they're the prime target for spam abuse and
// email-bombing. Two layers of defence are tested here:
//
//   1. Honeypot — every form has a hidden `website_url` input. Real users
//      never see or fill it; bots scraping forms routinely auto-fill every
//      visible-looking text input. Submissions where `website_url` is
//      non-empty are silently dropped server-side (success-shaped response,
//      but no DB write, no email sent) so the bot can't detect the trap.
//
//   2. Rate limiting — Laravel's throttle:5,1 middleware caps each IP at
//      5 submissions per minute across the three endpoints. The 6th request
//      returns 429.
//
// Both defences are independently necessary: a slow distributed bot evades
// rate limiting; a fast bot that knows about the honeypot evades that. The
// combination cuts well into the long tail of automated abuse.

use App\Mail\LeadMagnetDelivery;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Note: rate-limiter cache is flushed before every Feature test by the
// global beforeEach in tests/Pest.php — no per-test cleanup needed here.

// ──────────────────────────────────────────────────────────────────────
// Honeypot — non-empty website_url means it's a bot. Silently drop.
// ──────────────────────────────────────────────────────────────────────

test('honeypot: /contact silently drops submissions with website_url filled', function () {
    Mail::fake();

    $response = $this->post('/contact', [
        'name' => 'Spammy McBot',
        'email' => 'bot@example.com',
        'message' => 'BUY VIAGRA NOW',
        'website_url' => 'https://spam.example.com', // a real user never sets this
    ]);

    // Bot-friendly response: looks like success so the bot can't detect
    // the trap and adapt.
    $response->assertRedirect();
    $response->assertSessionHas('success');

    // But nothing actually happened — no email sent.
    Mail::assertNothingSent();
});

test('honeypot: /lead-magnet/subscribe silently drops submissions with website_url filled', function () {
    Mail::fake();

    $response = $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Spammy McBot',
        'email' => 'bot@example.com',
        'website_url' => 'https://spam.example.com',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    // No subscriber created, no email sent.
    expect(Subscriber::where('email', 'bot@example.com')->exists())->toBeFalse();
    Mail::assertNothingSent();
});

test('honeypot: /subscribe silently drops submissions with website_url filled', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'Spammy McBot',
        'email' => 'bot@example.com',
        'website_url' => 'https://spam.example.com',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    expect(Subscriber::where('email', 'bot@example.com')->exists())->toBeFalse();
});

test('honeypot: empty website_url lets a legitimate submission through', function () {
    Mail::fake();

    // Real user — website_url is empty (the field exists in the HTML but
    // they didn't fill it because it's visually hidden).
    $response = $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Real Person',
        'email' => 'real@example.com',
        'website_url' => '',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    expect(Subscriber::where('email', 'real@example.com')->exists())->toBeTrue();
    Mail::assertSent(LeadMagnetDelivery::class);
});

// ──────────────────────────────────────────────────────────────────────
// Rate limiting — throttle:5,1 caps each IP at 5/min.
// ──────────────────────────────────────────────────────────────────────

test('throttle: /contact returns 429 on the 6th request within a minute', function () {
    Mail::fake();

    // 5 valid submissions — all succeed.
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->post('/contact', [
            'name' => "Visitor {$i}",
            'email' => "visitor{$i}@gmail.com",
            'message' => "Message {$i}",
        ]);
        $response->assertRedirect();
    }

    // 6th request — throttled.
    $response = $this->post('/contact', [
        'name' => 'Visitor 6',
        'email' => 'visitor6@gmail.com',
        'message' => 'Message 6',
    ]);

    $response->assertStatus(429);
});

test('throttle: /lead-magnet/subscribe returns 429 on the 6th request within a minute', function () {
    Mail::fake();

    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/lead-magnet/subscribe', [
            'name' => "Visitor {$i}",
            'email' => "visitor{$i}@example.com",
        ])->assertOk();
    }

    $response = $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Visitor 6',
        'email' => 'visitor6@example.com',
    ]);

    $response->assertStatus(429);
});

test('throttle: /subscribe returns 429 on the 6th request within a minute', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/subscribe', [
            'name' => "Visitor {$i}",
            'email' => "visitor{$i}@example.com",
        ])->assertOk();
    }

    $response = $this->postJson('/subscribe', [
        'name' => 'Visitor 6',
        'email' => 'visitor6@example.com',
    ]);

    $response->assertStatus(429);
});
