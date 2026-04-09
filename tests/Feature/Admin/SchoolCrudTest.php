<?php

use App\Models\User;
use App\Models\School;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// School CRUD — Full lifecycle
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

// ── Create ──

test('admin can view create school form', function () {
    $this->actingAs($this->admin)
        ->get('/admin/schools/create')
        ->assertStatus(200);
});

test('admin can create a school', function () {
    $response = $this->actingAs($this->admin)
        ->post('/admin/schools', [
            'name' => 'St Anselm\'s College',
            'address' => 'Manor Drive',
            'city' => 'Birkenhead',
            'postcode' => 'CH43 1UQ',
            'phone' => '0151 652 1408',
            'email' => 'office@stanselmscollege.com',
            'contact_name' => 'Head of Music',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('schools', [
        'name' => 'St Anselm\'s College',
        'city' => 'Birkenhead',
    ]);
});

test('school creation requires name', function () {
    $this->actingAs($this->admin)
        ->post('/admin/schools', [
            'city' => 'Liverpool',
        ])
        ->assertSessionHasErrors('name');
});

// ── Read ──

test('admin can view school details', function () {
    $school = School::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/schools/{$school->id}")
        ->assertStatus(200);
});

test('admin can view school edit form', function () {
    $school = School::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/schools/{$school->id}/edit")
        ->assertStatus(200);
});

// ── Update ──

test('admin can update a school', function () {
    $school = School::factory()->create(['name' => 'Old School Name']);

    $this->actingAs($this->admin)
        ->put("/admin/schools/{$school->id}", [
            'name' => 'New School Name',
        ])
        ->assertRedirect();

    expect($school->fresh()->name)->toBe('New School Name');
});

// ── Delete (soft deletes) ──

test('admin can soft delete a school', function () {
    $school = School::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/schools/{$school->id}")
        ->assertRedirect();

    expect($school->fresh()->deleted_at)->not->toBeNull();
});

// ── Search ──

test('admin can search schools by name', function () {
    School::factory()->create(['name' => 'Hillside High School']);
    School::factory()->create(['name' => 'Valley Primary']);

    $this->actingAs($this->admin)
        ->get('/admin/schools?search=Hillside')
        ->assertStatus(200);
});
