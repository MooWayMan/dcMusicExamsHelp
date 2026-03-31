<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Helper: create an admin user
// ──────────────────────────────────────────
function createAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function createTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Dashboard
// ──────────────────────────────────────────

test('guests cannot access admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin dashboard', function () {
    $this->actingAs(createTeacher())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can access the dashboard', function () {
    $this->actingAs(createAdmin())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Dashboard'));
});
