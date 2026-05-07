<?php

use App\Mail\LeadMagnetDelivery;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Lead magnet email-capture (POST /lead-magnet/subscribe)
// ──────────────────────────────────────────

test('valid submission creates a subscriber with trinity_checklist source and sends the PDF email', function () {
    Mail::fake();

    $response = $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'tina@example.com',
        'name' => 'Tina Teacher',
        'source' => 'trinity_checklist',
    ]);

    Mail::assertSent(LeadMagnetDelivery::class, function ($mail) {
        return $mail->hasTo('tina@example.com');
    });
});

test('marketing_consent=true stamps marketing_consent_at', function () {
    Mail::fake();

    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Cathy Consent',
        'email' => 'cathy@example.com',
        'marketing_consent' => true,
    ])->assertOk();

    $sub = Subscriber::where('email', 'cathy@example.com')->first();
    expect($sub)->not->toBeNull();
    expect($sub->marketing_consent_at)->not->toBeNull();
});

test('marketing_consent omitted leaves marketing_consent_at null', function () {
    Mail::fake();

    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Quiet Quentin',
        'email' => 'quentin@example.com',
    ])->assertOk();

    $sub = Subscriber::where('email', 'quentin@example.com')->first();
    expect($sub)->not->toBeNull();
    expect($sub->marketing_consent_at)->toBeNull();
});

test('marketing_consent=false leaves marketing_consent_at null', function () {
    Mail::fake();

    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'No Thanks',
        'email' => 'no@example.com',
        'marketing_consent' => false,
    ])->assertOk();

    $sub = Subscriber::where('email', 'no@example.com')->first();
    expect($sub->marketing_consent_at)->toBeNull();
});

test('lead magnet email body links the free teacher account signup', function () {
    // Lead-magnet recipients are unauthenticated subscribers — the perfect
    // moment to put a free-teacher-account link in front of music teachers
    // who've just shown high intent. Render the Mailable directly and
    // assert the /register CTA is present.
    $rendered = (new LeadMagnetDelivery('Tina Teacher'))->render();

    expect($rendered)->toContain('/register');
    expect($rendered)->toContain('teacher account');
});

test('lead magnet subscription requires name + email', function () {
    Mail::fake();

    $this->postJson('/lead-magnet/subscribe', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);

    Mail::assertNothingSent();
});

test('lead magnet subscription rejects invalid email', function () {
    Mail::fake();

    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Bad Email',
        'email' => 'not-an-email',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');

    Mail::assertNothingSent();
});

test('re-submitting the same email upserts (no duplicate row) and re-stamps consent', function () {
    Mail::fake();

    // First submission — no consent
    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Repeat Rita',
        'email' => 'rita@example.com',
    ])->assertOk();

    // Second submission — now opts in
    $this->postJson('/lead-magnet/subscribe', [
        'name' => 'Repeat Rita',
        'email' => 'rita@example.com',
        'marketing_consent' => true,
    ])->assertOk();

    expect(Subscriber::where('email', 'rita@example.com')->count())->toBe(1);

    $sub = Subscriber::where('email', 'rita@example.com')->first();
    expect($sub->marketing_consent_at)->not->toBeNull();
});
