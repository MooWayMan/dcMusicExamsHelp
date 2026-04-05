<?php

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// POST /subscribe - Valid Submissions
// ──────────────────────────────────────────

test('POST /subscribe with valid data creates a subscriber and returns JSON success', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Teacher',
        'email' => 'john@example.com',
        'role' => 'teacher',
        'source' => 'landing',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Thank you for subscribing!',
        ]);

    expect(Subscriber::where('email', 'john@example.com')->exists())->toBeTrue();
});

test('POST /subscribe creates subscriber with name, email, and role', function () {
    $this->postJson('/subscribe', [
        'name' => 'Jane Parent',
        'email' => 'jane@example.com',
        'role' => 'parent',
    ])->assertStatus(200);

    expect(Subscriber::where('email', 'jane@example.com')->first())
        ->name->toBe('Jane Parent')
        ->role->toBe('parent')
        ->source->toBe('website'); // Default source
});

test('POST /subscribe sets subscribed_at automatically via database default', function () {
    $this->postJson('/subscribe', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'student',
    ]);

    $subscriber = Subscriber::where('email', 'test@example.com')->first();
    expect($subscriber->subscribed_at)->not->toBeNull();
});

test('POST /subscribe with null role is accepted', function () {
    $this->postJson('/subscribe', [
        'name' => 'No Role User',
        'email' => 'norole@example.com',
        // role omitted
    ])->assertStatus(200);

    expect(Subscriber::where('email', 'norole@example.com')->first()->role)->toBeNull();
});

test('POST /subscribe with null source defaults to website', function () {
    $this->postJson('/subscribe', [
        'name' => 'Test',
        'email' => 'test@example.com',
        // source omitted
    ])->assertStatus(200);

    expect(Subscriber::where('email', 'test@example.com')->first()->source)->toBe('website');
});

// ──────────────────────────────────────────
// POST /subscribe - Validation Errors
// ──────────────────────────────────────────

test('POST /subscribe with missing email returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        // email omitted
        'role' => 'teacher',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('POST /subscribe with missing name returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        // name omitted
        'email' => 'john@example.com',
        'role' => 'teacher',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('POST /subscribe with invalid email returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'role' => 'teacher',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('POST /subscribe with invalid role returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'invalid-role',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

test('POST /subscribe with name exceeding 255 characters returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => str_repeat('a', 256),
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('POST /subscribe with email exceeding 255 characters returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => str_repeat('a', 244) . '@example.com', // Total > 255
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('POST /subscribe with source exceeding 50 characters returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'source' => str_repeat('a', 51),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['source']);
});

// ──────────────────────────────────────────
// POST /subscribe - Duplicate Handling
// ──────────────────────────────────────────

test('POST /subscribe with duplicate email returns success message', function () {
    $subscriber = Subscriber::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'teacher',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'You are already subscribed. Thank you!',
        ]);

    // Should not create another record
    expect(Subscriber::where('email', 'john@example.com')->count())->toBe(1);
});

test('POST /subscribe with re-subscription updates unsubscribed subscriber', function () {
    // Create an unsubscribed subscriber
    $subscriber = Subscriber::factory()
        ->unsubscribed()
        ->create([
            'email' => 'john@example.com',
            'name' => 'Old Name',
            'role' => 'parent',
        ]);

    $response = $this->postJson('/subscribe', [
        'name' => 'John Updated',
        'email' => 'john@example.com',
        'role' => 'teacher',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Welcome back! You have been re-subscribed.',
        ]);

    // Should update the existing subscriber
    expect(Subscriber::where('email', 'john@example.com')->count())->toBe(1);
    $updated = Subscriber::where('email', 'john@example.com')->first();
    expect($updated->name)->toBe('John Updated');
    expect($updated->role)->toBe('teacher');
    expect($updated->unsubscribed_at)->toBeNull();
});

test('POST /subscribe with re-subscription keeps existing role if not provided', function () {
    $subscriber = Subscriber::factory()
        ->unsubscribed()
        ->asTeacher()
        ->create(['email' => 'john@example.com']);

    $this->postJson('/subscribe', [
        'name' => 'John Updated',
        'email' => 'john@example.com',
        // role omitted
    ])->assertStatus(200);

    $updated = Subscriber::where('email', 'john@example.com')->first();
    expect($updated->role)->toBe('teacher'); // Kept original role
});

test('POST /subscribe with re-subscription updates subscribed_at timestamp', function () {
    $oldTime = now()->subDays(10);
    $subscriber = Subscriber::factory()
        ->unsubscribed()
        ->create([
            'email' => 'john@example.com',
            'subscribed_at' => $oldTime,
        ]);

    $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $updated = Subscriber::where('email', 'john@example.com')->first();
    expect($updated->subscribed_at->gt($oldTime))->toBeTrue();
});

// ──────────────────────────────────────────
// POST /subscribe - Content Type Handling
// ──────────────────────────────────────────

test('POST /subscribe returns JSON response', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertHeader('content-type', 'application/json');
});

test('POST /subscribe with empty string email returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => 'John Doe',
        'email' => '',
        'role' => 'teacher',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('POST /subscribe with empty string name returns validation error', function () {
    $response = $this->postJson('/subscribe', [
        'name' => '',
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
