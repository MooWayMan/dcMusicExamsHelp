<?php

use App\Models\Task;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// API — Calendar Task Sync (POST /api/tasks/from-calendar)
// The Google Calendar → Task Manager pipeline
// ──────────────────────────────────────────

beforeEach(function () {
    // Set a known API token for testing
    config(['services.task_api.token' => 'test-api-token-123']);
});

test('valid token can create a task', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'REMINDER fix the booking page',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertStatus(201)
        ->assertJson(['skipped' => false]);

    $this->assertDatabaseHas('tasks', [
        'title' => 'REMINDER fix the booking page',
    ]);
});

test('request without token is rejected', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Should not work',
    ]);

    $response->assertStatus(401);
});

test('request with wrong token is rejected', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Should not work',
    ], [
        'Authorization' => 'Bearer wrong-token',
    ]);

    $response->assertStatus(401);
});

test('task creation requires title', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'detail' => 'No title given',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertStatus(422);
});

test('duplicate calendar event ID is skipped', function () {
    Task::factory()->create(['calendar_event_id' => 'gcal-event-abc']);

    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Same event again',
        'calendar_event_id' => 'gcal-event-abc',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertOk()
        ->assertJson(['skipped' => true]);
});

test('duplicate title on same day is skipped', function () {
    Task::factory()->create([
        'title' => 'Fix booking page',
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Fix booking page',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertOk()
        ->assertJson(['skipped' => true]);
});

test('task gets default values when optional fields are missing', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Quick voice note',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertStatus(201);

    $task = Task::where('title', 'Quick voice note')->first();
    expect($task->priority)->toBe('medium');
    expect($task->assigned_to)->toBe('Paul');
    expect($task->detail)->toBe('Added via voice (Google Calendar sync)');
});

test('custom priority and category are saved', function () {
    $response = $this->postJson('/api/tasks/from-calendar', [
        'title' => 'Urgent fix',
        'priority' => 'high',
        'category' => 'technical',
        'assigned_to' => 'Spider-Man',
    ], [
        'Authorization' => 'Bearer test-api-token-123',
    ]);

    $response->assertStatus(201);

    $task = Task::where('title', 'Urgent fix')->first();
    expect($task->priority)->toBe('high');
    expect($task->category)->toBe('technical');
    expect($task->assigned_to)->toBe('Spider-Man');
});
