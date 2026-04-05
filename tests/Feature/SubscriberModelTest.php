<?php

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// Scope: active
// ──────────────────────────────────────────

test('active scope filters unsubscribed subscribers', function () {
    Subscriber::factory()->asTeacher()->create();
    Subscriber::factory()->asTeacher()->unsubscribed()->create();
    Subscriber::factory()->asParent()->create();

    $active = Subscriber::active()->get();

    expect($active->count())->toBe(2);
});

test('active scope excludes all unsubscribed subscribers', function () {
    $active = Subscriber::factory()->asTeacher()->create();
    $unsubscribed = Subscriber::factory()->unsubscribed()->create();

    $resultIds = Subscriber::active()->pluck('id')->toArray();

    expect($resultIds)->toContain($active->id)
        ->not->toContain($unsubscribed->id);
});

test('active scope with no unsubscribed returns all subscribers', function () {
    Subscriber::factory()->count(5)->create();

    expect(Subscriber::active()->count())->toBe(5);
});

test('active scope with all unsubscribed returns empty', function () {
    Subscriber::factory()->count(3)->unsubscribed()->create();

    expect(Subscriber::active()->count())->toBe(0);
});

test('active scope returns correct count for mixed active and inactive', function () {
    Subscriber::factory()->count(3)->create();
    Subscriber::factory()->count(2)->unsubscribed()->create();

    expect(Subscriber::active()->count())->toBe(3);
});

// ──────────────────────────────────────────
// Method: isActive()
// ──────────────────────────────────────────

test('isActive returns true for subscribed subscriber', function () {
    $subscriber = Subscriber::factory()->create();

    expect($subscriber->isActive())->toBeTrue();
});

test('isActive returns false for unsubscribed subscriber', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create();

    expect($subscriber->isActive())->toBeFalse();
});

test('isActive returns true when unsubscribed_at is null', function () {
    $subscriber = Subscriber::factory()->create([
        'unsubscribed_at' => null,
    ]);

    expect($subscriber->isActive())->toBeTrue();
});

test('isActive returns false when unsubscribed_at is not null', function () {
    $subscriber = Subscriber::factory()->create([
        'unsubscribed_at' => now(),
    ]);

    expect($subscriber->isActive())->toBeFalse();
});

test('isActive changes when unsubscribed_at is set', function () {
    $subscriber = Subscriber::factory()->create();
    expect($subscriber->isActive())->toBeTrue();

    $subscriber->update(['unsubscribed_at' => now()]);
    expect($subscriber->isActive())->toBeFalse();
});

test('isActive changes when unsubscribed_at is cleared', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create();
    expect($subscriber->isActive())->toBeFalse();

    $subscriber->update(['unsubscribed_at' => null]);
    expect($subscriber->isActive())->toBeTrue();
});

// ──────────────────────────────────────────
// Fillable Fields
// ──────────────────────────────────────────

test('fillable fields are correctly defined', function () {
    $subscriber = new Subscriber();

    expect($subscriber->getFillable())
        ->toContain('name')
        ->toContain('email')
        ->toContain('role')
        ->toContain('source')
        ->toContain('subscribed_at')
        ->toContain('unsubscribed_at');
});

test('can mass assign fillable fields', function () {
    $subscriber = Subscriber::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'teacher',
        'source' => 'landing',
    ]);

    $subscriber->refresh();

    expect($subscriber->name)->toBe('John Doe')
        ->and($subscriber->email)->toBe('john@example.com')
        ->and($subscriber->role)->toBe('teacher')
        ->and($subscriber->source)->toBe('landing');
});

test('fillable includes exactly 6 fields', function () {
    $subscriber = new Subscriber();

    expect($subscriber->getFillable())->toHaveCount(6);
});

// ──────────────────────────────────────────
// Casts
// ──────────────────────────────────────────

test('subscribed_at is cast to datetime', function () {
    $subscriber = Subscriber::factory()->create();
    $subscriber->refresh();

    expect($subscriber->subscribed_at)->toBeInstanceOf(\DateTimeInterface::class);
});

test('unsubscribed_at is cast to datetime', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create();
    $subscriber->refresh();

    expect($subscriber->unsubscribed_at)->toBeInstanceOf(\DateTimeInterface::class);
});

test('unsubscribed_at returns null when not set', function () {
    $subscriber = Subscriber::factory()->create();

    expect($subscriber->unsubscribed_at)->toBeNull();
});

// ──────────────────────────────────────────
// Model Attributes
// ──────────────────────────────────────────

test('subscriber has all required attributes', function () {
    $subscriber = Subscriber::factory()->create([
        'name' => 'Test Name',
        'email' => 'test@example.com',
        'role' => 'student',
        'source' => 'email',
    ]);

    expect($subscriber)
        ->name->toBe('Test Name')
        ->email->toBe('test@example.com')
        ->role->toBe('student')
        ->source->toBe('email');
});

test('subscriber nullable fields can be null', function () {
    $subscriber = Subscriber::factory()->create([
        'role' => null,
        'unsubscribed_at' => null,
    ]);

    expect($subscriber->role)->toBeNull()
        ->unsubscribed_at->toBeNull();
});
