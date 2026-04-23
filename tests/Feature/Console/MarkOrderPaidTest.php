<?php
// tests/Feature/Console/MarkOrderPaidTest.php

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrder(string $number, float $commission = 0): Order
{
    return Order::create([
        'trinity_order_number' => $number,
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => '2026-03-01',
        'commission_rate' => 20.00,
        'commission_amount' => $commission,
    ]);
}

it('marks a single order as paid (backward compatible)', function () {
    makeOrder('1-14835765869', 13.60);

    $this->artisan('orders:mark-paid', [
        'trinity_order_number' => '1-14835765869',
        '--amount' => 13.60,
        '--paid-date' => '2026-04-23',
    ])->assertExitCode(0);

    $order = Order::where('trinity_order_number', '1-14835765869')->first();
    expect((string) $order->commission_paid_at->format('Y-m-d'))->toBe('2026-04-23')
        ->and((float) $order->commission_paid_amount)->toBe(13.60);
});

it('marks a batch of orders as paid in one run', function () {
    makeOrder('1-14835765869', 13.60);
    makeOrder('1-14835557379', 13.60);
    makeOrder('1-15279500444', 11.00);

    $this->artisan('orders:mark-paid', [
        '--batch' => '1-14835765869:13.60,1-14835557379:13.60,1-15279500444:11.00',
        '--paid-date' => '2026-04-23',
    ])->assertExitCode(0);

    $orders = Order::whereIn('trinity_order_number', [
        '1-14835765869',
        '1-14835557379',
        '1-15279500444',
    ])->get();

    expect($orders)->toHaveCount(3);
    foreach ($orders as $o) {
        expect($o->commission_paid_at->format('Y-m-d'))->toBe('2026-04-23')
            ->and($o->commission_paid_amount)->not->toBeNull();
    }

    $total = $orders->sum(fn ($o) => (float) $o->commission_paid_amount);
    expect(round($total, 2))->toBe(38.20);
});

it('continues when one batch order is missing and reports others', function () {
    makeOrder('1-14835765869', 13.60);
    // 1-99999999999 intentionally not created

    $this->artisan('orders:mark-paid', [
        '--batch' => '1-14835765869:13.60,1-99999999999:50.00',
        '--paid-date' => '2026-04-23',
    ])->assertExitCode(0);

    $found = Order::where('trinity_order_number', '1-14835765869')->first();
    expect((float) $found->commission_paid_amount)->toBe(13.60);
});

it('fails cleanly on malformed batch entry', function () {
    makeOrder('1-14835765869', 13.60);

    $this->artisan('orders:mark-paid', [
        '--batch' => '1-14835765869-no-colon',
        '--paid-date' => '2026-04-23',
    ])->assertExitCode(1);

    $order = Order::where('trinity_order_number', '1-14835765869')->first();
    expect($order->commission_paid_at)->toBeNull();
});

it('rejects both single order and batch at the same time', function () {
    $this->artisan('orders:mark-paid', [
        'trinity_order_number' => '1-14835765869',
        '--batch' => '1-14835765869:13.60',
    ])->assertExitCode(1);
});

it('dry-run does not write changes', function () {
    makeOrder('1-14835765869', 13.60);

    $this->artisan('orders:mark-paid', [
        '--batch' => '1-14835765869:13.60',
        '--paid-date' => '2026-04-23',
        '--dry-run' => true,
    ])->assertExitCode(0);

    $order = Order::where('trinity_order_number', '1-14835765869')->first();
    expect($order->commission_paid_at)->toBeNull();
});
