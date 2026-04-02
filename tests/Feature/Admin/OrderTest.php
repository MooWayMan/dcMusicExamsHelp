<?php

use App\Models\Order;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function orderAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function orderTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access orders index', function () {
    $this->get(route('admin.orders.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access orders index', function () {
    $this->actingAs(orderTeacher())
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view orders index', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();
    Order::factory()->count(3)->create(['user_id' => $teacher->id]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Index')
            ->has('orders.data', 3)
        );
});

test('orders can be filtered by delivery method', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();
    Order::factory()->digital()->count(2)->create(['user_id' => $teacher->id]);
    Order::factory()->faceToFace()->count(3)->create(['user_id' => $teacher->id]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['method' => 'digital']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 2)
        );
});

// ──────────────────────────────────────────
// Time Period Filters
// ──────────────────────────────────────────

test('orders can be filtered by this quarter', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();

    // This quarter
    Order::factory()->create([
        'user_id' => $teacher->id,
        'created_at' => now(),
    ]);

    // Last year (outside this quarter)
    Order::factory()->create([
        'user_id' => $teacher->id,
        'created_at' => now()->subYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_quarter']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
        );
});

test('orders can be filtered by this year', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();

    // This year
    Order::factory()->count(2)->create([
        'user_id' => $teacher->id,
        'created_at' => now()->startOfYear()->addDays(10),
    ]);

    // Previous year
    Order::factory()->create([
        'user_id' => $teacher->id,
        'created_at' => now()->subYear()->startOfYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_year']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 2)
        );
});

test('orders can be filtered by last 12 months', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();

    // 6 months ago (within last 12)
    Order::factory()->create([
        'user_id' => $teacher->id,
        'created_at' => now()->subMonths(6),
    ]);

    // 18 months ago (outside last 12)
    Order::factory()->create([
        'user_id' => $teacher->id,
        'created_at' => now()->subMonths(18),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'last_12']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
        );
});

test('summary stats respect time period filter', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();

    Order::factory()->create([
        'user_id' => $teacher->id,
        'commission_amount' => 100.00,
        'candidates' => 5,
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'user_id' => $teacher->id,
        'commission_amount' => 200.00,
        'candidates' => 10,
        'created_at' => now()->subYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_year']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_orders', 1)
            ->where('summary.total_candidates', 5)
        );
});

test('period filter passes through to frontend filters', function () {
    $admin = orderAdmin();

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'last_quarter']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.period', 'last_quarter')
        );
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view an order', function () {
    $admin = orderAdmin();
    $teacher = orderTeacher();
    $order = Order::factory()->create([
        'user_id' => $teacher->id,
        'trinity_order_number' => 'TRN-123456',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Show')
        );
});
