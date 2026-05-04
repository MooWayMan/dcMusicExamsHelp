<?php

use App\Models\Subscriber;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Admin Subscribers index
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'name' => 'Site Admin',
        'email' => 'admin@example.com',
    ]);
});

test('admin can view the subscribers index', function () {
    Subscriber::factory()->create([
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
        'source' => 'trinity_checklist',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/Subscribers/Index')
                ->has('subscribers.data')
                ->has('summary')
                ->has('sources')
        );
});

test('non-admin cannot view the subscribers index', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/subscribers')
        ->assertStatus(403);
});

test('guests are redirected to login from the subscribers index', function () {
    $this->get('/admin/subscribers')->assertRedirect('/login');
});

test('subscribers index respects the search filter (by name)', function () {
    Subscriber::factory()->create(['name' => 'Findable Felicity', 'email' => 'find@example.com']);
    Subscriber::factory()->create(['name' => 'Other Person', 'email' => 'other@example.com']);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers?search=Findable')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/Subscribers/Index')
                ->where('subscribers.data.0.name', 'Findable Felicity')
                ->where('subscribers.data.0.email', 'find@example.com')
        );
});

test('subscribers index respects the search filter (by email)', function () {
    Subscriber::factory()->create(['name' => 'Generic A', 'email' => 'unique-needle@example.com']);
    Subscriber::factory()->create(['name' => 'Generic B', 'email' => 'haystack@example.com']);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers?search=needle')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->where('subscribers.data.0.email', 'unique-needle@example.com')
        );
});

test('subscribers index respects the marketing_consent filter (yes)', function () {
    Subscriber::factory()->create([
        'name' => 'Consented Cathy',
        'email' => 'consent@example.com',
        'marketing_consent_at' => now(),
    ]);
    Subscriber::factory()->create([
        'name' => 'No Consent Norman',
        'email' => 'noconsent@example.com',
        'marketing_consent_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers?marketing_consent=yes')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->has('subscribers.data', 1)
                ->where('subscribers.data.0.email', 'consent@example.com')
        );
});

test('subscribers index respects the marketing_consent filter (no)', function () {
    Subscriber::factory()->create([
        'name' => 'Consented Cathy',
        'email' => 'consent@example.com',
        'marketing_consent_at' => now(),
    ]);
    Subscriber::factory()->create([
        'name' => 'No Consent Norman',
        'email' => 'noconsent@example.com',
        'marketing_consent_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers?marketing_consent=no')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->has('subscribers.data', 1)
                ->where('subscribers.data.0.email', 'noconsent@example.com')
        );
});

test('subscribers index links a subscriber to a User account when emails match', function () {
    User::factory()->create([
        'role' => 'teacher',
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
    ]);
    Subscriber::factory()->create([
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/subscribers')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->where('subscribers.data.0.email', 'tina@example.com')
                ->where('subscribers.data.0.linked_user.name', 'Tina Teacher')
        );
});
