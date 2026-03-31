<?php

use App\Models\School;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function schoolAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function schoolTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access schools index', function () {
    $this->get(route('admin.schools.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access schools index', function () {
    $this->actingAs(schoolTeacher())
        ->get(route('admin.schools.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view schools index', function () {
    $admin = schoolAdmin();
    School::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.schools.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Schools/Index')
            ->has('schools.data', 3)
        );
});

test('schools index can be searched by name', function () {
    $admin = schoolAdmin();
    School::factory()->create(['name' => 'Maple Academy']);
    School::factory()->create(['name' => 'Oak School']);

    $this->actingAs($admin)
        ->get(route('admin.schools.index', ['search' => 'Maple']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('schools.data', 1)
            ->where('schools.data.0.name', 'Maple Academy')
        );
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create(['name' => 'Test School']);

    $this->actingAs($admin)
        ->get(route('admin.schools.show', $school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Schools/Show')
            ->where('school.name', 'Test School')
        );
});

// ──────────────────────────────────────────
// Create & Store
// ──────────────────────────────────────────

test('admin can view the create school form', function () {
    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Schools/Create'));
});

test('admin can create a new school', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('admin.schools.store'), [
            'name' => 'New School',
            'address' => '123 Test Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('schools', [
        'name' => 'New School',
        'city' => 'London',
    ]);
});

// ──────────────────────────────────────────
// Edit & Update
// ──────────────────────────────────────────

test('admin can view the edit school form', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.schools.edit', $school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Schools/Edit'));
});

test('admin can update a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create(['name' => 'Old School']);

    $this->actingAs($admin)
        ->put(route('admin.schools.update', $school), [
            'name' => 'Renamed School',
        ])
        ->assertRedirect();

    expect($school->fresh()->name)->toBe('Renamed School');
});

// ──────────────────────────────────────────
// Delete
// ──────────────────────────────────────────

test('admin can delete a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.schools.destroy', $school))
        ->assertRedirect();

    expect($school->fresh()->trashed())->toBeTrue();
});
