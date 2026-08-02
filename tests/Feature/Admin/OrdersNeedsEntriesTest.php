<?php

// tests/Feature/Admin/OrdersNeedsEntriesTest.php
//
// The "Needs enrolment list" filter on /admin/orders.
//
// Section 1 (Bulk Orders) creates an order from the orders CSV; the Enrolment
// List is what creates its candidates. Between the two the order looks
// complete — the Cands column comes from a COLUMN IN THE ORDERS CSV, so it can
// read "1 candidate" while holding none — but those candidates exist nowhere:
// not on the teacher's dashboard, not in Pending Results, not in the draw.
// (Found via James Worthington, order 1-18204862774, Q3 2026.)

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function orderNeedingEntries(array $attrs = []): Order
{
    return Order::create(array_merge([
        'trinity_order_number' => '1-NEEDS-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        // Straight from the orders CSV — deliberately NOT a count of rows held.
        'candidates' => 1,
        'order_status' => 'Ready to Deliver',
        'requested_start_date' => Carbon::create(2026, 7, 15),
    ], $attrs));
}

function orderWithEntries(): Order
{
    $order = orderNeedingEntries();

    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Already Imported',
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
    ]);

    return $order;
}

test('the filter shows only orders with no candidates imported', function () {
    $needs = orderNeedingEntries();
    $done = orderWithEntries();

    $this->actingAs($this->admin)
        ->get('/admin/orders?entries=missing')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('orders.data', 1)
            ->where('orders.data.0.trinity_order_number', $needs->trinity_order_number));

    $this->actingAs($this->admin)
        ->get('/admin/orders')
        ->assertInertia(fn ($p) => $p->has('orders.data', 2));
});

test('an order reading one candidate but holding none is still counted', function () {
    // The trap: candidates = 1 from the CSV, zero rows actually imported.
    orderNeedingEntries(['candidates' => 1]);

    $this->actingAs($this->admin)
        ->get('/admin/orders')
        ->assertInertia(fn ($p) => $p
            ->where('summary.needs_entries', 1)
            ->where('orders.data.0.candidates', 1)
            ->where('orders.data.0.exam_entries_count', 0));
});

test('the count is zero once every order has its candidates', function () {
    orderWithEntries();

    $this->actingAs($this->admin)
        ->get('/admin/orders')
        ->assertInertia(fn ($p) => $p->where('summary.needs_entries', 0));
});

test('the count respects the other filters in play', function () {
    // A Q3 order needing entries, and a Q1 one that also needs them — filtering
    // to this year's third quarter should only count the first.
    orderNeedingEntries(['requested_start_date' => Carbon::create(2026, 7, 15)]);
    orderNeedingEntries(['requested_start_date' => Carbon::create(2026, 1, 15)]);

    $this->actingAs($this->admin)
        ->get('/admin/orders?period=q-2026-3')
        ->assertInertia(fn ($p) => $p->where('summary.needs_entries', 1));
});

test('a teacher cannot see the orders list at all', function () {
    orderNeedingEntries();

    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->get('/admin/orders?entries=missing')
        ->assertForbidden();
});
