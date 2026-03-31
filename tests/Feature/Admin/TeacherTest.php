<?php

use App\Models\School;
use App\Models\Student;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function teacherUser(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'teacher'], $attrs));
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access teachers index', function () {
    $this->get(route('admin.teachers.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access teachers index', function () {
    $this->actingAs(teacherUser())
        ->get(route('admin.teachers.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view teachers index', function () {
    $admin = adminUser();
    teacherUser(['name' => 'Jane Music']);
    teacherUser(['name' => 'John Piano']);

    $this->actingAs($admin)
        ->get(route('admin.teachers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Teachers/Index')
            ->has('teachers.data', 2)
        );
});

test('teachers index can be searched by name', function () {
    $admin = adminUser();
    teacherUser(['name' => 'Alice Strings']);
    teacherUser(['name' => 'Bob Brass']);

    $this->actingAs($admin)
        ->get(route('admin.teachers.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('teachers.data', 1)
            ->where('teachers.data.0.name', 'Alice Strings')
        );
});

test('teachers index can be sorted by name', function () {
    $admin = adminUser();
    teacherUser(['name' => 'Zara Violin']);
    teacherUser(['name' => 'Adam Cello']);

    $this->actingAs($admin)
        ->get(route('admin.teachers.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('teachers.data.0.name', 'Adam Cello')
        );
});

test('teachers index can be sorted by students count', function () {
    $admin = adminUser();
    $teacher1 = teacherUser(['name' => 'Few Students']);
    $teacher2 = teacherUser(['name' => 'Many Students']);

    Student::factory()->count(1)->create(['user_id' => $teacher1->id]);
    Student::factory()->count(5)->create(['user_id' => $teacher2->id]);

    $this->actingAs($admin)
        ->get(route('admin.teachers.index', ['sort' => 'students_count', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('teachers.data.0.name', 'Many Students')
        );
});

test('teachers index can be sorted by schools', function () {
    $admin = adminUser();
    $teacher1 = teacherUser(['name' => 'Teacher Alpha']);
    $teacher2 = teacherUser(['name' => 'Teacher Zulu']);

    $schoolA = School::factory()->create(['name' => 'Alpha Academy']);
    $schoolZ = School::factory()->create(['name' => 'Zulu School']);

    $teacher1->schools()->attach($schoolZ);
    $teacher2->schools()->attach($schoolA);

    $this->actingAs($admin)
        ->get(route('admin.teachers.index', ['sort' => 'schools', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('teachers.data.0.name', 'Teacher Zulu') // Alpha Academy comes first
        );
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view a teacher profile', function () {
    $admin = adminUser();
    $teacher = teacherUser(['name' => 'Jane Music']);

    $this->actingAs($admin)
        ->get(route('admin.teachers.show', $teacher))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Teachers/Show')
            ->where('teacher.name', 'Jane Music')
        );
});

// ──────────────────────────────────────────
// Create & Store
// ──────────────────────────────────────────

test('admin can view the create teacher form', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.teachers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Teachers/Create'));
});

test('admin can create a new teacher', function () {
    $admin = adminUser();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'New Teacher',
            'email' => 'newteacher@test.com',
            'phone' => '07700 900000',
            'school_ids' => [$school->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'name' => 'New Teacher',
        'email' => 'newteacher@test.com',
        'role' => 'teacher',
    ]);
});

// ──────────────────────────────────────────
// Edit & Update
// ──────────────────────────────────────────

test('admin can view the edit teacher form', function () {
    $admin = adminUser();
    $teacher = teacherUser();

    $this->actingAs($admin)
        ->get(route('admin.teachers.edit', $teacher))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Teachers/Edit'));
});

test('admin can update a teacher', function () {
    $admin = adminUser();
    $teacher = teacherUser(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->put(route('admin.teachers.update', $teacher), [
            'name' => 'Updated Name',
            'email' => $teacher->email,
        ])
        ->assertRedirect();

    expect($teacher->fresh()->name)->toBe('Updated Name');
});

// ──────────────────────────────────────────
// Delete
// ──────────────────────────────────────────

test('admin can delete a teacher', function () {
    $admin = adminUser();
    $teacher = teacherUser();

    $this->actingAs($admin)
        ->delete(route('admin.teachers.destroy', $teacher))
        ->assertRedirect();

    expect($teacher->fresh()->trashed())->toBeTrue();
});
