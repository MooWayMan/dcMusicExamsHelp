<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Authentication
// ──────────────────────────────────────────

test('login page loads', function () {
    $this->get('/login')->assertStatus(200);
});

test('user can login with correct credentials', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
});

test('user cannot login with wrong password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('user cannot login with non-existent email', function () {
    $this->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

// ──────────────────────────────────────────
// Dashboard (requires auth)
// ──────────────────────────────────────────

test('guest cannot access dashboard', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});

// ──────────────────────────────────────────
// Profile Settings (requires auth)
// ──────────────────────────────────────────

test('guest cannot access profile settings', function () {
    $this->get('/settings/profile')
        ->assertRedirect('/login');
});

test('authenticated user can view profile settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertStatus(200);
});

test('user can update their name and email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->email)->toBe('updated@example.com');
});

test('profile update requires name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'email' => 'test@example.com',
        ])
        ->assertSessionHasErrors('name');
});

test('profile update requires valid email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Test',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('email');
});

// ──────────────────────────────────────────
// Password Update (requires auth + verified)
// ──────────────────────────────────────────

test('user can update their password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasNoErrors();
});

test('password update requires current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'wrong-current',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasErrors('current_password');
});

test('password update requires confirmation to match', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');
});

test('new password must be at least 8 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});
