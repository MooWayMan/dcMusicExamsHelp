<?php

use App\Models\User;
use App\Models\Task;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Task Management — CRUD + AJAX
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

// ── Create ──

test('admin can view create task form', function () {
    $this->actingAs($this->admin)
        ->get('/admin/tasks/create')
        ->assertStatus(200);
});

test('admin can create a task', function () {
    $this->actingAs($this->admin)
        ->post('/admin/tasks', [
            'title' => 'Fix the booking page',
            'detail' => 'Blue on blue links need sorting',
            'priority' => 'high',
            'assigned_to' => 'Spider-Man',
            'category' => 'technical',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Fix the booking page',
        'priority' => 'high',
        'assigned_to' => 'Spider-Man',
    ]);
});

test('task creation requires title', function () {
    $this->actingAs($this->admin)
        ->post('/admin/tasks', [
            'priority' => 'medium',
        ])
        ->assertSessionHasErrors('title');
});

test('task priority must be valid', function () {
    $this->actingAs($this->admin)
        ->post('/admin/tasks', [
            'title' => 'Test',
            'priority' => 'urgent',
        ])
        ->assertSessionHasErrors('priority');
});

// ── Update ──

test('admin can view task edit form', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/tasks/{$task->id}/edit")
        ->assertStatus(200);
});

test('admin can update a task', function () {
    $task = Task::factory()->create(['title' => 'Old Title']);

    $this->actingAs($this->admin)
        ->put("/admin/tasks/{$task->id}", [
            'title' => 'New Title',
            'priority' => 'low',
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    expect($task->fresh()->title)->toBe('New Title');
});

test('completing a task sets completed_at timestamp', function () {
    $task = Task::factory()->create(['status' => 'pending', 'completed_at' => null]);

    $this->actingAs($this->admin)
        ->put("/admin/tasks/{$task->id}", [
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => 'completed',
        ])
        ->assertRedirect();

    expect($task->fresh()->completed_at)->not->toBeNull();
});

// ── Delete ──

test('admin can delete a task', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/tasks/{$task->id}")
        ->assertRedirect();

    expect(Task::withTrashed()->find($task->id)->deleted_at)->not->toBeNull();
});

// ── AJAX Toggle ──

test('admin can toggle task completion via AJAX', function () {
    $task = Task::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($this->admin)
        ->patchJson("/admin/tasks/{$task->id}/toggle");

    $response->assertOk();
    expect($task->fresh()->status)->toBe('completed');
});

test('admin can toggle completed task back to pending', function () {
    $task = Task::factory()->completed()->create();

    $response = $this->actingAs($this->admin)
        ->patchJson("/admin/tasks/{$task->id}/toggle");

    $response->assertOk();
    expect($task->fresh()->status)->toBe('pending');
});

// ── AJAX Notes ──

test('admin can update task notes via AJAX', function () {
    $task = Task::factory()->create();

    $response = $this->actingAs($this->admin)
        ->patchJson("/admin/tasks/{$task->id}/notes", [
            'notes' => 'Updated notes from Spider-Man',
        ]);

    $response->assertOk();
    expect($task->fresh()->notes)->toBe('Updated notes from Spider-Man');
});

// ── AJAX Sync ──

test('admin can sync task counts via AJAX', function () {
    Task::factory()->count(3)->create(['status' => 'pending']);
    Task::factory()->count(2)->completed()->create();

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/tasks/sync');

    $response->assertOk();
});

// ── Filters ──

test('admin can filter tasks by status', function () {
    Task::factory()->create(['status' => 'pending']);
    Task::factory()->completed()->create();

    $this->actingAs($this->admin)
        ->get('/admin/tasks?status=active')
        ->assertStatus(200);
});

test('admin can filter tasks by priority', function () {
    Task::factory()->high()->create();
    Task::factory()->low()->create();

    $this->actingAs($this->admin)
        ->get('/admin/tasks?priority=high')
        ->assertStatus(200);
});

test('admin can filter tasks by category', function () {
    Task::factory()->create(['category' => 'technical']);
    Task::factory()->create(['category' => 'content']);

    $this->actingAs($this->admin)
        ->get('/admin/tasks?category=technical')
        ->assertStatus(200);
});

test('admin can search tasks', function () {
    Task::factory()->create(['title' => 'Fix the booking page']);
    Task::factory()->create(['title' => 'Write new FAQ']);

    $this->actingAs($this->admin)
        ->get('/admin/tasks?search=booking')
        ->assertStatus(200);
});
