<?php

use App\Models\User;
use App\Models\SessionLog;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Session Logs — Track development hours
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin can create a session log', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-09',
            'hours' => 3.5,
            'notes' => 'Fixed blue-on-blue links and wrote Pest tests',
        ])
        ->assertRedirect(route('admin.session-logs.index'));

    $this->assertDatabaseHas('session_logs', [
        'date' => '2026-04-09',
        'hours' => 3.5,
    ]);
});

test('session log requires date', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'hours' => 2,
        ])
        ->assertSessionHasErrors('date');
});

test('session log requires hours', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-09',
        ])
        ->assertSessionHasErrors('hours');
});

test('session log hours must be at least 0.5', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-09',
            'hours' => 0.1,
        ])
        ->assertSessionHasErrors('hours');
});

test('session log hours cannot exceed 24', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-09',
            'hours' => 25,
        ])
        ->assertSessionHasErrors('hours');
});

test('session log date must be unique', function () {
    SessionLog::create([
        'date' => '2026-04-08',
        'hours' => 2,
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-08',
            'hours' => 3,
        ])
        ->assertSessionHasErrors('date');
});

test('admin can update a session log', function () {
    $log = SessionLog::create([
        'date' => '2026-04-07',
        'hours' => 2,
        'notes' => 'Old notes',
    ]);

    $this->actingAs($this->admin)
        ->put("/admin/session-logs/{$log->id}", [
            'date' => '2026-04-07',
            'hours' => 4,
            'notes' => 'Updated notes',
        ])
        ->assertRedirect(route('admin.session-logs.index'));

    $fresh = $log->fresh();
    expect((float) $fresh->hours)->toBe(4.0);
    expect($fresh->notes)->toBe('Updated notes');
});

test('admin can delete a session log', function () {
    $log = SessionLog::create([
        'date' => '2026-04-06',
        'hours' => 1.5,
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/session-logs/{$log->id}")
        ->assertRedirect(route('admin.session-logs.index'));

    $this->assertDatabaseMissing('session_logs', ['id' => $log->id]);
});

test('session log notes are optional', function () {
    $this->actingAs($this->admin)
        ->post('/admin/session-logs', [
            'date' => '2026-04-05',
            'hours' => 1,
        ])
        ->assertRedirect(route('admin.session-logs.index'));

    $this->assertDatabaseHas('session_logs', [
        'date' => '2026-04-05',
        'notes' => null,
    ]);
});
