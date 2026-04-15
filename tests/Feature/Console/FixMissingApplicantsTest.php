<?php
// tests/Feature/Console/FixMissingApplicantsTest.php

use App\Models\ExamContact;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links the known missing applicants to their orders', function () {
    $orderPaul = Order::create([
        'trinity_order_number' => '1-16043046624',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Venue A',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-04-08',
    ]);

    $orderRachel = Order::create([
        'trinity_order_number' => '1-16044396864',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Venue B',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-04-10',
    ]);

    $orderMark = Order::create([
        'trinity_order_number' => '1-15549565825',
        'delivery_method' => 'Default',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'venue' => 'Venue C',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-07-11',
    ]);

    $this->artisan('orders:fix-missing-applicants')
        ->assertExitCode(0);

    $paul = ExamContact::where('email', 'madmusic6@hotmail.com')->first();
    $rachel = ExamContact::where('email', 'rachelsimms1969@gmail.com')->first();
    $mark = ExamContact::where('email', 'fionashore@hotmail.co.uk')->first();

    expect($paul)->not->toBeNull();
    expect($rachel)->not->toBeNull();
    expect($mark)->not->toBeNull();

    expect($orderPaul->fresh()->created_by_contact_id)->toBe($paul->id);
    expect($orderRachel->fresh()->created_by_contact_id)->toBe($rachel->id);
    expect($orderMark->fresh()->created_by_contact_id)->toBe($mark->id);

    $this->assertDatabaseHas('order_contacts', [
        'order_id' => $orderPaul->id,
        'exam_contact_id' => $paul->id,
        'role_in_order' => 'applicant',
        'is_primary' => true,
    ]);

    $this->assertDatabaseHas('order_contacts', [
        'order_id' => $orderRachel->id,
        'exam_contact_id' => $rachel->id,
        'role_in_order' => 'applicant',
        'is_primary' => true,
    ]);

    $this->assertDatabaseHas('order_contacts', [
        'order_id' => $orderMark->id,
        'exam_contact_id' => $mark->id,
        'role_in_order' => 'applicant',
        'is_primary' => true,
    ]);
});

it('does not fail when a mapped order is missing', function () {
    $this->artisan('orders:fix-missing-applicants')
        ->assertExitCode(0);

    expect(ExamContact::count())->toBe(0);
});

it('supports dry run without writing anything', function () {
    Order::create([
        'trinity_order_number' => '1-16043046624',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Venue A',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-04-08',
    ]);

    $this->artisan('orders:fix-missing-applicants', [
        '--dry-run' => true,
    ])->assertExitCode(0);

    expect(ExamContact::count())->toBe(0);

    $this->assertDatabaseMissing('order_contacts', [
        'role_in_order' => 'applicant',
    ]);
});