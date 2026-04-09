<?php

use App\Models\User;
use App\Models\School;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Teacher CRUD — Full lifecycle
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

// ── Create ──

test('admin can view create teacher form', function () {
    $this->actingAs($this->admin)
        ->get('/admin/teachers/create')
        ->assertStatus(200);
});

test('admin can create a teacher', function () {
    $response = $this->actingAs($this->admin)
        ->post('/admin/teachers', [
            'name' => 'Sarah Music',
            'email' => 'sarah@musicschool.com',
            'phone' => '07700 900123',
            'notes' => 'Teaches piano and flute',
            'met_face_to_face' => true,
            'spoken_on_phone' => false,
            'contacted_by_email' => true,
            'how_they_found_us' => 'Google',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'sarah@musicschool.com',
        'name' => 'Sarah Music',
        'role' => 'teacher',
    ]);
});

test('teacher creation requires name', function () {
    $this->actingAs($this->admin)
        ->post('/admin/teachers', [
            'email' => 'noname@example.com',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertSessionHasErrors('name');
});

test('teacher creation requires email', function () {
    $this->actingAs($this->admin)
        ->post('/admin/teachers', [
            'name' => 'No Email',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertSessionHasErrors('email');
});

test('teacher creation rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post('/admin/teachers', [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertSessionHasErrors('email');
});

// ── Read ──

test('admin can view teacher profile', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($this->admin)
        ->get("/admin/teachers/{$teacher->id}")
        ->assertStatus(200);
});

test('admin can view teacher edit form', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($this->admin)
        ->get("/admin/teachers/{$teacher->id}/edit")
        ->assertStatus(200);
});

// ── Update ──

test('admin can update a teacher', function () {
    $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->put("/admin/teachers/{$teacher->id}", [
            'name' => 'New Name',
            'email' => $teacher->email,
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertRedirect();

    expect($teacher->fresh()->name)->toBe('New Name');
});

test('teacher update rejects email already taken by another user', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $other = User::factory()->create(['email' => 'other@example.com']);

    $this->actingAs($this->admin)
        ->put("/admin/teachers/{$teacher->id}", [
            'name' => 'Test',
            'email' => 'other@example.com',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertSessionHasErrors('email');
});

test('teacher can keep their own email on update', function () {
    $teacher = User::factory()->create(['role' => 'teacher', 'email' => 'keep@example.com']);

    $this->actingAs($this->admin)
        ->put("/admin/teachers/{$teacher->id}", [
            'name' => 'Updated Name',
            'email' => 'keep@example.com',
            'instruments' => [],
            'schools' => [],
            'subject_areas' => [],
        ])
        ->assertSessionHasNoErrors();
});

// ── Delete & Restore (soft deletes) ──

test('admin can soft delete a teacher', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($this->admin)
        ->delete("/admin/teachers/{$teacher->id}")
        ->assertRedirect();

    expect($teacher->fresh()->deleted_at)->not->toBeNull();
});

test('admin can restore a soft-deleted teacher', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $teacher->delete();

    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$teacher->id}/restore")
        ->assertRedirect();

    expect($teacher->fresh()->deleted_at)->toBeNull();
});

// ── Deletion Impact ──

test('admin can check deletion impact', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $response = $this->actingAs($this->admin)
        ->getJson("/admin/teachers/{$teacher->id}/deletion-impact");

    $response->assertOk()
        ->assertJsonStructure(['students_count', 'orders_count', 'contact_logs_count', 'schools_count']);
});

// ── Search & Filters ──

test('admin can search teachers by name', function () {
    User::factory()->create(['role' => 'teacher', 'name' => 'Unique Teacher Name']);
    User::factory()->create(['role' => 'teacher', 'name' => 'Other Person']);

    $this->actingAs($this->admin)
        ->get('/admin/teachers?search=Unique')
        ->assertStatus(200);
});

test('teachers list is paginated', function () {
    User::factory()->count(20)->create(['role' => 'teacher']);

    $this->actingAs($this->admin)
        ->get('/admin/teachers')
        ->assertStatus(200);
});
