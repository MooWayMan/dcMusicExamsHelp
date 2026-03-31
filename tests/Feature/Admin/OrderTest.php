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
