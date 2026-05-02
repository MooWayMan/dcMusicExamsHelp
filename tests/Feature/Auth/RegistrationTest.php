<?php

use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Tests below run only if Features::registration() is enabled in fortify.php.
// Currently disabled (2026-04-27) — they auto-skip.
// ──────────────────────────────────────────

test('registration screen can be rendered', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

// ──────────────────────────────────────────
// Belt-and-braces: when the feature is disabled, /register must NOT exist.
// This test runs only when registration is OFF, so it's the inverse of the
// two above — together they cover both states.
// ──────────────────────────────────────────

test('registration is disabled and returns 404', function () {
    if (Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is enabled — see tests above.');
    }

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});