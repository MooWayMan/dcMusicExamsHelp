<?php

// tests/Feature/Admin/QuarterCommissionSourceTest.php
//
// Where /admin/quarter-comparison gets its commission from.
//
// Face-to-face commission is recorded ONCE on the order — orders.commission_amount
// is what Trinity remits and what reconciliation marks paid, and every other
// money page reads it. Quarter Comparison was the lone page deriving commission
// per entry from exam_entries.fee, so the July 2026 F2F session (53 entries
// with no fee, £1,519.56 booked across four orders) rendered as a collapse to
// £342 while being correct everywhere else.

// NOTE ON THE ASSERTED TYPES: round() returns a float, but a whole one
// JSON-encodes as `300`, not `300.0`, and Inertia's where() compares strictly.
// So whole amounts are asserted as ints and only genuine decimals as floats.

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 8, 3, 12));
    $this->admin = User::factory()->create(['role' => 'admin']);
});

afterEach(fn () => Carbon::setTestNow());

function f2fOrder(?float $commission, Carbon $start): Order
{
    return Order::create([
        'trinity_order_number' => '1-QC-'.uniqid('', true),
        // Trinity's literal value for face-to-face. See DashboardController.
        'delivery_method' => 'Default',
        'subject_area' => 'Music',
        'candidates' => 2,
        'order_status' => 'Delivered',
        'requested_start_date' => $start,
        'commission_amount' => $commission,
    ]);
}

function unpricedEntry(Order $order, string $name): ExamEntry
{
    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => $name,
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'fee' => null,
        'exam_date' => $order->requested_start_date,
    ]);
}

// Buckets are built Q1..Q4 for the selected year, so quarters.2 is Q3 2026
// and quarters.1 is Q2 — the assertions below index them directly.

test('the order commission is used when its entries carry no fee', function () {
    $order = f2fOrder(1519.56, Carbon::create(2026, 7, 9));
    unpricedEntry($order, 'One');
    unpricedEntry($order, 'Two');

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('quarters.2.total_commission', 1519.56)
            ->where('quarters.2.unpriced_entries', 2)
            ->where('quarters.2.f2f_candidates', 2));
});

test('one order is counted once however many entries it holds', function () {
    // The bug this guards: crediting per entry would report 3 x 300.
    $order = f2fOrder(300.00, Carbon::create(2026, 7, 9));
    unpricedEntry($order, 'One');
    unpricedEntry($order, 'Two');
    unpricedEntry($order, 'Three');

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($p) => $p->where('quarters.2.total_commission', 300));
});

test('an order with no recorded commission still derives it per entry', function () {
    $order = f2fOrder(null, Carbon::create(2026, 7, 9));
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Priced',
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'fee' => 100.00,
        'exam_date' => Carbon::create(2026, 7, 9),
    ]);

    // 28% gross, the F2F rate.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($p) => $p
            ->where('quarters.2.total_commission', 28)
            ->where('quarters.2.unpriced_entries', 0));
});

test('an order commission lands in the quarter of its own start date', function () {
    // Entry dated in Q3, order requested in Q2 — the money belongs to Q2, and
    // must not be double-counted into both.
    $order = f2fOrder(400.00, Carbon::create(2026, 6, 20));
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Straddler',
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'fee' => null,
        'exam_date' => Carbon::create(2026, 7, 9),
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($p) => $p
            ->where('quarters.1.total_commission', 400)
            ->where('quarters.2.total_commission', 0));
});
