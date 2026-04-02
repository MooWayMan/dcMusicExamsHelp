<?php

// tests/Feature/Admin/TaskTest.php

use App\Models\Task;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function taskAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function taskNonAdmin(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access tasks index', function () {
    $this->get(route('admin.tasks.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access tasks index', function () {
    $this->actingAs(taskNonAdmin())
        ->get(route('admin.tasks.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view tasks index', function () {
    $admin = taskAdmin();
    Task::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Tasks/Index')
            ->has('tasks.data', 3)
        );
});

test('tasks index includes notes field', function () {
    $admin = taskAdmin();
    Task::factory()->create(['notes' => 'Some important notes here']);

    $this->actingAs($admin)
        ->get(route('admin.tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.notes', 'Some important notes here')
        );
});

test('tasks can be searched by notes content', function () {
    $admin = taskAdmin();
    Task::factory()->create(['title' => 'Unrelated task', 'notes' => null]);
    Task::factory()->create(['title' => 'OAuth fix', 'notes' => 'Took 5 hours debugging refresh token']);

    $this->actingAs($admin)
        ->get(route('admin.tasks.index', ['search' => 'refresh token']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'OAuth fix')
        );
});

test('tasks can be filtered by status', function () {
    $admin = taskAdmin();
    Task::factory()->count(2)->create(['status' => 'pending']);
    Task::factory()->count(3)->create(['status' => 'completed', 'completed_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.tasks.index', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 2)
        );
});

test('tasks can be filtered by category', function () {
    $admin = taskAdmin();
    Task::factory()->create(['category' => 'technical']);
    Task::factory()->create(['category' => 'marketing']);
    Task::factory()->create(['category' => 'technical']);

    $this->actingAs($admin)
        ->get(route('admin.tasks.index', ['category' => 'technical']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 2)
        );
});

// ──────────────────────────────────────────
// Create & Store
// ──────────────────────────────────────────

test('admin can view create task form', function () {
    $this->actingAs(taskAdmin())
        ->get(route('admin.tasks.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Tasks/Create')
        );
});

test('admin can store a task with notes', function () {
    $admin = taskAdmin();

    $this->actingAs($admin)
        ->post(route('admin.tasks.store'), [
            'title' => 'Test task with notes',
            'detail' => 'Some detail',
            'notes' => 'This took 3 hours because of X, Y, Z',
            'priority' => 'high',
            'assigned_to' => 'Paul',
            'category' => 'technical',
        ])
        ->assertRedirect(route('admin.tasks.index'));

    $task = Task::where('title', 'Test task with notes')->first();
    expect($task)->not->toBeNull();
    expect($task->notes)->toBe('This took 3 hours because of X, Y, Z');
});

test('task can be stored without notes', function () {
    $admin = taskAdmin();

    $this->actingAs($admin)
        ->post(route('admin.tasks.store'), [
            'title' => 'Task without notes',
            'priority' => 'medium',
        ])
        ->assertRedirect(route('admin.tasks.index'));

    $task = Task::where('title', 'Task without notes')->first();
    expect($task)->not->toBeNull();
    expect($task->notes)->toBeNull();
});

test('task title is required', function () {
    $admin = taskAdmin();

    $this->actingAs($admin)
        ->post(route('admin.tasks.store'), [
            'priority' => 'medium',
        ])
        ->assertSessionHasErrors('title');
});

// ──────────────────────────────────────────
// Edit & Update
// ──────────────────────────────────────────

test('admin can view edit task form with notes', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['notes' => 'Existing notes']);

    $this->actingAs($admin)
        ->get(route('admin.tasks.edit', $task))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Tasks/Edit')
            ->where('task.notes', 'Existing notes')
        );
});

test('admin can update a task with notes', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->put(route('admin.tasks.update', $task), [
            'title' => 'Updated title',
            'notes' => 'Added these notes during update',
            'priority' => 'low',
            'status' => 'pending',
        ])
        ->assertRedirect(route('admin.tasks.index'));

    $task->refresh();
    expect($task->notes)->toBe('Added these notes during update');
});

// ──────────────────────────────────────────
// Inline Notes (AJAX)
// ──────────────────────────────────────────

test('admin can save notes inline via AJAX', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['notes' => null]);

    $this->actingAs($admin)
        ->patchJson(route('admin.tasks.notes', $task), [
            'notes' => 'Inline notes saved from the index page',
        ])
        ->assertOk()
        ->assertJson(['saved' => true]);

    $task->refresh();
    expect($task->notes)->toBe('Inline notes saved from the index page');
});

test('admin can clear notes via AJAX', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['notes' => 'Old notes']);

    $this->actingAs($admin)
        ->patchJson(route('admin.tasks.notes', $task), [
            'notes' => '',
        ])
        ->assertOk();

    $task->refresh();
    expect($task->notes)->toBeNull();
});

test('guests cannot save notes via AJAX', function () {
    $task = Task::factory()->create();

    $this->patchJson(route('admin.tasks.notes', $task), [
        'notes' => 'Hacker notes',
    ])->assertUnauthorized();
});

// ──────────────────────────────────────────
// Toggle
// ──────────────────────────────────────────

test('admin can toggle task to completed', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->patchJson(route('admin.tasks.toggle', $task))
        ->assertOk()
        ->assertJson(['status' => 'completed']);

    $task->refresh();
    expect($task->status)->toBe('completed');
    expect($task->completed_at)->not->toBeNull();
});

test('admin can toggle task back to pending', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create(['status' => 'completed', 'completed_at' => now()]);

    $this->actingAs($admin)
        ->patchJson(route('admin.tasks.toggle', $task))
        ->assertOk()
        ->assertJson(['status' => 'pending']);

    $task->refresh();
    expect($task->status)->toBe('pending');
    expect($task->completed_at)->toBeNull();
});

// ──────────────────────────────────────────
// Delete
// ──────────────────────────────────────────

test('admin can delete a task', function () {
    $admin = taskAdmin();
    $task = Task::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.tasks.destroy', $task))
        ->assertRedirect(route('admin.tasks.index'));

    expect(Task::find($task->id))->toBeNull();
    expect(Task::withTrashed()->find($task->id))->not->toBeNull(); // soft deleted
});
