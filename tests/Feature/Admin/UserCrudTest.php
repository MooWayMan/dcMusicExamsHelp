<?php

use App\Models\ExamContact;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Admin Users page — read-only view of registered accounts
// (the auth side; distinct from /admin/contacts which is the wider
//  exam_contacts people system)
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'name' => 'Site Admin',
        'email' => 'admin@example.com',
    ]);
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view the users index', function () {
    $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Tina Teacher']);

    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/Users/Index')
            ->has('users.data')
            ->has('summary')
            ->has('roles')
    );
});

test('users index respects the search filter', function () {
    User::factory()->create(['role' => 'teacher', 'name' => 'Findable Teacher', 'email' => 'find@example.com']);
    User::factory()->create(['role' => 'teacher', 'name' => 'Other Person', 'email' => 'other@example.com']);

    $this->actingAs($this->admin)
        ->get('/admin/users?search=Findable')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/Users/Index')
                ->where('users.data.0.name', 'Findable Teacher')
                ->where('users.data.0.email', 'find@example.com')
        );
});

test('users index respects the role filter', function () {
    User::factory()->create(['role' => 'parent', 'name' => 'Patricia Parent', 'email' => 'p@example.com']);
    User::factory()->create(['role' => 'teacher', 'name' => 'Tom Teacher', 'email' => 't@example.com']);

    $this->actingAs($this->admin)
        ->get('/admin/users?role=parent')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->where('users.data.0.role', 'parent')
                ->where('filters.role', 'parent')
        );
});

test('users index summary counts the new role types', function () {
    User::factory()->create(['role' => 'school_admin']);
    User::factory()->create(['role' => 'parent']);
    User::factory()->create(['role' => 'self']);
    User::factory()->create(['role' => 'teacher']);

    $this->actingAs($this->admin)
        ->get('/admin/users')
        ->assertInertia(
            fn ($page) => $page
                ->where('summary.admins', 1)
                ->where('summary.school_admins', 1)
                ->where('summary.teachers', 1)
                ->where('summary.parents', 1)
                ->where('summary.selves', 1)
        );
});

test('non-admin cannot view the users index', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/users')
        ->assertStatus(403);
});

test('guests are redirected from the users index to login', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view a registered user with no linked contact', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
        'name' => 'Lonely Teacher',
        'email' => 'lonely@example.com',
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$teacher->id}")
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/Users/Show')
                ->where('user.name', 'Lonely Teacher')
                ->where('user.email', 'lonely@example.com')
                ->where('linkedContact', null)
        );
});

test('admin show links the user to their exam_contacts row by email', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
        'name' => 'Linked Teacher',
        'email' => 'linked@example.com',
    ]);

    $contact = ExamContact::create([
        'name' => 'Linked Teacher',
        'email' => 'linked@example.com',
        'source' => 'trinity_csv',
    ]);
    $contact->addType('teacher');

    $this->actingAs($this->admin)
        ->get("/admin/users/{$teacher->id}")
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/Users/Show')
                ->where('linkedContact.id', $contact->id)
                ->where('linkedContact.name', 'Linked Teacher')
        );
});

test('non-admin cannot view a user show page', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $other = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get("/admin/users/{$other->id}")
        ->assertStatus(403);
});

test('guests are redirected from a user show page to login', function () {
    $other = User::factory()->create(['role' => 'teacher']);

    $this->get("/admin/users/{$other->id}")->assertRedirect('/login');
});
