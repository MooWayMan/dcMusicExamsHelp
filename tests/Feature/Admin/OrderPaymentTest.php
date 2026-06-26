<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function paymentAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function paymentTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Model — isPaid() helper + scopes
// ──────────────────────────────────────────

test('isPaid returns false when commission_paid_at is null', function () {
    $order = Order::factory()->create(['commission_paid_at' => null]);

    expect($order->isPaid())->toBeFalse();
});

test('isPaid returns true when commission_paid_at is set', function () {
    $order = Order::factory()->create(['commission_paid_at' => '2026-04-02']);

    expect($order->isPaid())->toBeTrue();
});

test('paid scope returns only orders with a remittance date', function () {
    Order::factory()->create(['commission_paid_at' => '2026-04-02']);
    Order::factory()->create(['commission_paid_at' => '2026-03-19']);
    Order::factory()->create(['commission_paid_at' => null]);

    expect(Order::paid()->count())->toBe(2);
});

test('unpaid scope returns only orders without a remittance date', function () {
    Order::factory()->create(['commission_paid_at' => '2026-04-02']);
    Order::factory()->create(['commission_paid_at' => null]);
    Order::factory()->create(['commission_paid_at' => null]);

    expect(Order::unpaid()->count())->toBe(2);
});

// ──────────────────────────────────────────
// Index filter — paid / unpaid
// ──────────────────────────────────────────

test('orders index can filter by paid', function () {
    $admin = paymentAdmin();
    Order::factory()->count(2)->create(['commission_paid_at' => '2026-04-02']);
    Order::factory()->count(3)->create(['commission_paid_at' => null]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['paid' => 'paid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('orders.data', 2));
});

test('orders index can filter by unpaid', function () {
    $admin = paymentAdmin();
    Order::factory()->count(2)->create(['commission_paid_at' => '2026-04-02']);
    Order::factory()->count(3)->create(['commission_paid_at' => null]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['paid' => 'unpaid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('orders.data', 3));
});

test('orders payload includes is_paid and commission_paid_at', function () {
    $admin = paymentAdmin();

    Order::factory()->create([
        'trinity_order_number' => '1-14163844479',
        'commission_paid_at' => '2026-04-02',
        'commission_paid_amount' => 15.60,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.data.0.is_paid', true)
            ->where('orders.data.0.commission_paid_at', '02 Apr 2026')
            ->where('orders.data.0.commission_paid_amount', '15.60')
        );
});

test('order show payload includes payment status', function () {
    $admin = paymentAdmin();

    $order = Order::factory()->create([
        'commission_paid_at' => '2026-04-02',
        'commission_paid_amount' => 15.60,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('order.is_paid', true)
            ->where('order.commission_paid_at', '02 Apr 2026')
            ->where('order.commission_paid_amount', '15.60')
        );
});

test('order show payload marks an unpaid order', function () {
    $admin = paymentAdmin();

    $order = Order::factory()->create(['commission_paid_at' => null]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('order.is_paid', false)
            ->where('order.commission_paid_at', null)
        );
});

test('summary totals split paid vs unpaid', function () {
    $admin = paymentAdmin();

    Order::factory()->create([
        'commission_amount' => 24.00,
        'commission_paid_at' => '2026-02-24',
        'commission_paid_amount' => 24.00,
    ]);
    Order::factory()->create([
        'commission_amount' => 39.60,
        'commission_paid_at' => '2026-04-02',
        'commission_paid_amount' => 39.60,
    ]);
    Order::factory()->create([
        'commission_amount' => 100.00,
        'commission_paid_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_paid', '63.60')
            ->where('summary.total_unpaid', '100.00')
        );
});

test('paid filter passes through to frontend filters', function () {
    $this->actingAs(paymentAdmin())
        ->get(route('admin.orders.index', ['paid' => 'unpaid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.paid', 'unpaid'));
});

// ──────────────────────────────────────────
// Artisan: orders:mark-paid
// ──────────────────────────────────────────

test('mark-paid command stamps paid fields on the order', function () {
    $order = Order::factory()->create([
        'trinity_order_number' => '1-14163844479',
        'commission_amount' => 15.60,
        'commission_paid_at' => null,
    ]);

    Artisan::call('orders:mark-paid', [
        'trinity_order_number' => '1-14163844479',
        '--amount' => 15.60,
        '--paid-date' => '2026-04-02',
    ]);

    $order->refresh();

    expect($order->commission_paid_at?->toDateString())->toBe('2026-04-02');
    expect((float) $order->commission_paid_amount)->toBe(15.60);
});

test('mark-paid command defaults amount to commission_amount when not provided', function () {
    $order = Order::factory()->create([
        'trinity_order_number' => '1-13750176989',
        'commission_amount' => 39.60,
        'commission_paid_at' => null,
    ]);

    Artisan::call('orders:mark-paid', [
        'trinity_order_number' => '1-13750176989',
        '--paid-date' => '2026-04-02',
    ]);

    expect((float) $order->fresh()->commission_paid_amount)->toBe(39.60);
});

test('mark-paid command fails gracefully when order not found', function () {
    $exit = Artisan::call('orders:mark-paid', [
        'trinity_order_number' => '1-DOES-NOT-EXIST',
        '--paid-date' => '2026-04-02',
    ]);

    expect($exit)->toBe(1); // FAILURE
});

test('mark-paid --dry-run does not write to the DB', function () {
    $order = Order::factory()->create([
        'trinity_order_number' => '1-14163844479',
        'commission_paid_at' => null,
    ]);

    Artisan::call('orders:mark-paid', [
        'trinity_order_number' => '1-14163844479',
        '--amount' => 15.60,
        '--paid-date' => '2026-04-02',
        '--dry-run' => true,
    ]);

    expect($order->fresh()->commission_paid_at)->toBeNull();
});

test('mark-paid command marks all duplicate rows when prod has no unique constraint', function () {
    // Prod DB has no unique constraint on trinity_order_number (schema drift —
    // see memory: orders_no_unique). The migration declares ->unique() so local
    // enforces it. Drop the index here to reproduce the real prod state.
    DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_trinity_order_number_unique');

    Order::factory()->create([
        'trinity_order_number' => '1-11478141779',
        'commission_paid_at' => null,
    ]);
    Order::factory()->create([
        'trinity_order_number' => '1-11478141779',
        'commission_paid_at' => null,
    ]);

    Artisan::call('orders:mark-paid', [
        'trinity_order_number' => '1-11478141779',
        '--amount' => 173.04,
        '--paid-date' => '2026-03-17',
    ]);

    $paid = Order::where('trinity_order_number', '1-11478141779')
        ->whereNotNull('commission_paid_at')
        ->count();

    expect($paid)->toBe(2);
});

// ──────────────────────────────────────────
// Seeder: CommissionBackfillQ1Seeder
// ──────────────────────────────────────────

test('Q1 backfill seeder marks matching orders as paid and skips missing ones', function () {
    // Create two of the twelve Q1 paid orders
    Order::factory()->create([
        'trinity_order_number' => '1-14163844479',
        'commission_paid_at' => null,
    ]);
    Order::factory()->create([
        'trinity_order_number' => '1-11508172910',
        'commission_paid_at' => null,
    ]);
    // Create an unrelated order that shouldn't get touched
    Order::factory()->create([
        'trinity_order_number' => '1-UNRELATED',
        'commission_paid_at' => null,
    ]);

    Artisan::call('db:seed', ['--class' => 'CommissionBackfillQ1Seeder']);

    expect(Order::where('trinity_order_number', '1-14163844479')->first()->commission_paid_at?->toDateString())->toBe('2026-04-02');
    expect(Order::where('trinity_order_number', '1-11508172910')->first()->commission_paid_at?->toDateString())->toBe('2026-03-19');
    expect(Order::where('trinity_order_number', '1-UNRELATED')->first()->commission_paid_at)->toBeNull();
});
