<?php

use App\Models\Subscriber;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Newsletter Subscription (POST /subscribe)
// ──────────────────────────────────────────

test('visitor can subscribe with valid data', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'role' => 'teacher',
        'source' => 'popup',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'jane@example.com',
        'name' => 'Jane Smith',
        'role' => 'teacher',
        'source' => 'popup',
    ]);
});

test('subscriber is created with default source when none provided', function () {
    $this->postJson('/subscribe', [
        'name' => 'Tom Jones',
        'email' => 'tom@example.com',
    ]);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'tom@example.com',
        'source' => 'website',
    ]);
});

test('subscription requires name', function () {
    $response = $this->postJson('/subscribe', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('subscription requires email', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'Test User',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('subscription requires valid email format', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'Test User',
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('subscription role must be teacher, parent or student', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'hacker',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('role');
});

test('already subscribed user gets friendly message', function () {
    Subscriber::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/subscribe', [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'message' => 'You are already subscribed. Thank you!']);
});

test('unsubscribed user can re-subscribe', function () {
    Subscriber::factory()->unsubscribed()->create([
        'email' => 'comeback@example.com',
        'name' => 'Old Name',
    ]);

    $response = $this->postJson('/subscribe', [
        'name' => 'New Name',
        'email' => 'comeback@example.com',
        'role' => 'parent',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'message' => 'Welcome back! You have been re-subscribed.']);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'comeback@example.com',
        'name' => 'New Name',
        'role' => 'parent',
        'unsubscribed_at' => null,
    ]);
});

test('subscription works without optional role', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'No Role User',
        'email' => 'norole@example.com',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('subscribers', [
        'email' => 'norole@example.com',
        'role' => null,
    ]);
});
