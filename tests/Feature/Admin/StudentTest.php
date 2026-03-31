<?php

use App\Models\Student;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function studentAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function studentTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access students index', function () {
    $this->get(route('admin.students.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access students index', function () {
    $this->actingAs(studentTeacher())
        ->get(route('admin.students.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view students index', function () {
    $admin = studentAdmin();
    $teacher = studentTeacher();
    Student::factory()->count(4)->create(['user_id' => $teacher->id]);

    $this->actingAs($admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Index')
            ->has('students.data', 4)
        );
});

test('students index can be searched by name', function () {
    $admin = studentAdmin();
    $teacher = studentTeacher();
    Student::factory()->create(['user_id' => $teacher->id, 'first_name' => 'Emily', 'last_name' => 'Clark']);
    Student::factory()->create(['user_id' => $teacher->id, 'first_name' => 'James', 'last_name' => 'Smith']);

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['search' => 'Emily']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
        );
});
