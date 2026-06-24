<?php

// tests/Feature/ExpiredSessionTest.php

use Illuminate\Support\Facades\Route;

/*
 * Session-expiry behaviour for non-GET requests (6 Jun 2026 — ported
 * from MusicRegisterOnline's "PATCH /login" incident). A PATCH fired
 * after the session died used to follow the auth 302 redirect WITH the
 * PATCH method (browsers only downgrade POST to GET on a 302) and
 * explode as a MethodNotAllowedHttpException on /login. The respond()
 * hook in bootstrap/app.php now forces 303 on redirects answering
 * PUT/PATCH/DELETE, and turns 419 Page Expired into a quiet redirect
 * back. These tests pin that contract.
 */

test('a logged-out PATCH gets a 303 redirect to login, never a 302', function () {
    $this->patch('/settings/profile', [], ['X-Inertia' => 'true'])
        ->assertStatus(303)
        ->assertRedirect('/login');
});

test('a logged-out DELETE also gets a 303', function () {
    $this->delete('/settings/profile', [], ['X-Inertia' => 'true'])
        ->assertStatus(303)
        ->assertRedirect('/login');
});

test('a logged-out GET keeps the ordinary 302 redirect', function () {
    $this->get('/settings/profile')
        ->assertStatus(302)
        ->assertRedirect('/login');
});

test('419 page expired becomes a quiet redirect back with a flash message', function () {
    Route::patch('expired-session-probe', fn () => abort(419))->middleware('web');

    $this->from('/dashboard')
        ->patch('/expired-session-probe', [], ['X-Inertia' => 'true'])
        ->assertStatus(303)
        ->assertRedirect('/dashboard')
        ->assertSessionHas('error');
});
