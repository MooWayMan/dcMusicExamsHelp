<?php
// tests/Feature/Console/ImportOrdersFromCsvTest.php

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports only 2026 orders from csv', function () {
    $csv = base_path('tests/tmp_orders.csv');

    file_put_contents($csv, implode("\n", [
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '10/04/2026,Digital,ORDER-1,Music,1,Venue A,Confirmed',
        '10/04/2025,Digital,ORDER-2,Music,1,Venue B,Confirmed',
    ]));

    $this->artisan('orders:import-csv', [
        'file' => 'tests/tmp_orders.csv',
        '--year' => 2026,
    ])->assertExitCode(0);

    expect(Order::count())->toBe(1)
        ->and(Order::first()->trinity_order_number)->toBe('ORDER-1');

    unlink($csv);
});

it('updates existing order instead of duplicating', function () {
    Order::create([
        'trinity_order_number' => 'ORDER-1',
        'delivery_method' => 'Default',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Old Venue',
        'order_status' => 'Pending',
        'requested_start_date' => '2026-04-01',
    ]);

    $csv = base_path('tests/tmp_orders.csv');

    file_put_contents($csv, implode("\n", [
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '10/04/2026,Digital,ORDER-1,Music,2,Venue A,Updated',
    ]));

    $this->artisan('orders:import-csv', [
        'file' => 'tests/tmp_orders.csv',
        '--year' => 2026,
    ])->assertExitCode(0);

    expect(Order::count())->toBe(1)
        ->and(Order::first()->candidates)->toBe(2)
        ->and(Order::first()->venue)->toBe('Venue A')
        ->and(Order::first()->order_status)->toBe('Updated');

    unlink($csv);
});

it('parses dates with time correctly', function () {
    $csv = base_path('tests/tmp_orders.csv');

    file_put_contents($csv, implode("\n", [
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '30/03/2026 00:00:00,Digital,ORDER-3,Music,1,Venue A,Confirmed',
    ]));

    $this->artisan('orders:import-csv', [
        'file' => 'tests/tmp_orders.csv',
        '--year' => 2026,
    ])->assertExitCode(0);

    expect(Order::first()->requested_start_date->toDateString())->toBe('2026-03-30');

    unlink($csv);
});