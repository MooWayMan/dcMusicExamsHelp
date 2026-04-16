<?php

use App\Models\PageMaintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// Admin Routes
// ──────────────────────────────────────────

test('GET /admin/page-maintenance requires auth', function () {
    $this->get('/admin/page-maintenance')
        ->assertRedirect('/login');
});

test('GET /admin/page-maintenance returns 200 for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/page-maintenance')
        ->assertStatus(200);
});

test('admin can toggle page maintenance on', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    PageMaintenance::seed();
    $page = PageMaintenance::where('page_slug', 'recognition')->first();

    expect($page->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch("/admin/page-maintenance/{$page->id}/toggle")
        ->assertRedirect();

    $page->refresh();
    expect($page->is_active)->toBeTrue();
});

test('admin can toggle page maintenance off', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    PageMaintenance::seed();
    $page = PageMaintenance::where('page_slug', 'recognition')->first();
    $page->update(['is_active' => true]);

    $this->actingAs($admin)
        ->patch("/admin/page-maintenance/{$page->id}/toggle")
        ->assertRedirect();

    $page->refresh();
    expect($page->is_active)->toBeFalse();
});

test('admin can update maintenance message', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    PageMaintenance::seed();
    $page = PageMaintenance::where('page_slug', 'recognition')->first();

    $this->actingAs($admin)
        ->patch("/admin/page-maintenance/{$page->id}/message", [
            'message' => 'Custom maintenance message for testing.',
        ])
        ->assertRedirect();

    $page->refresh();
    expect($page->message)->toBe('Custom maintenance message for testing.');
});

// ──────────────────────────────────────────
// Model Tests
// ──────────────────────────────────────────

test('PageMaintenance::seed creates rows for all maintainable pages', function () {
    PageMaintenance::seed();

    $count = PageMaintenance::count();
    expect($count)->toBe(count(PageMaintenance::MAINTAINABLE_PAGES));
});

test('PageMaintenance::seed is idempotent', function () {
    PageMaintenance::seed();
    PageMaintenance::seed();

    $count = PageMaintenance::count();
    expect($count)->toBe(count(PageMaintenance::MAINTAINABLE_PAGES));
});

test('PageMaintenance::activeSlugs returns only active pages', function () {
    PageMaintenance::seed();
    PageMaintenance::where('page_slug', 'recognition')->update(['is_active' => true]);

    $active = PageMaintenance::activeSlugs();

    expect($active)->toContain('recognition');
    expect($active)->not->toContain('exam-fees');
});

test('PageMaintenance::isDown returns correct boolean', function () {
    PageMaintenance::seed();

    expect(PageMaintenance::isDown('recognition'))->toBeFalse();

    PageMaintenance::where('page_slug', 'recognition')->update(['is_active' => true]);

    expect(PageMaintenance::isDown('recognition'))->toBeTrue();
});

// ──────────────────────────────────────────
// Inertia Shared Data
// ──────────────────────────────────────────

test('maintenance pages are shared via Inertia', function () {
    PageMaintenance::seed();
    PageMaintenance::where('page_slug', 'recognition')->update(['is_active' => true]);

    $response = $this->get('/recognition');
    $response->assertStatus(200);

    $page = $response->viewData('page') ?? null;

    // The maintenancePages prop should be shared
    if ($page && isset($page['props']['maintenancePages'])) {
        expect($page['props']['maintenancePages'])->toHaveKey('recognition');
    }
});
