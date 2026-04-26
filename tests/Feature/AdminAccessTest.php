<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Admin Panel — Access Control
// Ensures teachers and guests can't sneak in
// ──────────────────────────────────────────

test('guest cannot access admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect('/login');
});

test('teacher cannot access admin dashboard', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin')
        ->assertStatus(403);
});

test('admin can access admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertStatus(200);
});

// ── Schools section ──

test('guest cannot access schools list', function () {
    $this->get('/admin/schools')
        ->assertRedirect('/login');
});

test('teacher cannot access schools list', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/schools')
        ->assertStatus(403);
});

test('admin can access schools list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/schools')
        ->assertStatus(200);
});

// ── Tasks section ──

test('guest cannot access tasks', function () {
    $this->get('/admin/tasks')
        ->assertRedirect('/login');
});

test('teacher cannot access tasks', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/tasks')
        ->assertStatus(403);
});

test('admin can access tasks', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/tasks')
        ->assertStatus(200);
});

// ── Orders section ──

test('guest cannot access orders', function () {
    $this->get('/admin/orders')
        ->assertRedirect('/login');
});

test('teacher cannot access orders', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/orders')
        ->assertStatus(403);
});

test('admin can access orders', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/orders')
        ->assertStatus(200);
});

// ── Students section ──

test('guest cannot access students', function () {
    $this->get('/admin/students')
        ->assertRedirect('/login');
});

test('teacher cannot access students', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/students')
        ->assertStatus(403);
});

test('admin can access students', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/students')
        ->assertStatus(200);
});

// ── Session Logs section ──

test('guest cannot access session logs', function () {
    $this->get('/admin/session-logs')
        ->assertRedirect('/login');
});

test('admin can access session logs', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/session-logs')
        ->assertStatus(200);
});

// ── Certificates section ──

test('guest cannot access certificates', function () {
    $this->get('/admin/certificates')
        ->assertRedirect('/login');
});

test('admin can access certificates', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/certificates')
        ->assertStatus(200);
});

// ── Roadmap section ──

test('guest cannot access roadmap', function () {
    $this->get('/admin/roadmap')
        ->assertRedirect('/login');
});

test('admin can access roadmap', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/roadmap')
        ->assertStatus(200);
});

// ── Quick Replies section ──

test('guest cannot access quick replies', function () {
    $this->get('/admin/quick-replies')
        ->assertRedirect('/login');
});

test('admin can access quick replies and sees the four templates', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/quick-replies')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('admin/QuickReplies/Index')
            ->has('templates', 4)
            ->where('templates.0.id', 'parent-enquiry')
            ->where('templates.1.id', 'teacher-enquiry')
            ->where('templates.2.id', 'old-address-nudge')
            ->where('templates.3.id', 'who-books')
        );
});

test('quick replies template content includes the canonical site references', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/quick-replies')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('admin/QuickReplies/Index')
            // Parent template should mention /for-parents
            ->where(
                'templates.0.body',
                fn ($body) => str_contains($body, 'musicexams.help/for-parents')
                    && str_contains($body, 'Take a Bow')
                    && str_contains($body, 'Standing Ovation')
            )
            // Teacher template should mention centre 120 + Faber + /for-teachers
            ->where(
                'templates.1.body',
                fn ($body) => str_contains($body, 'centre code **120**')
                    && str_contains($body, 'musicexams.help/for-teachers')
                    && str_contains($body, 'Faber')
            )
        );
});
