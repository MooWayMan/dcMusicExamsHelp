<?php
// tests/Feature/Console/ImportLegacyContactsTest.php

use App\Models\ExamContact;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Make sure the source connection has the minimal tables this command needs
    DB::connection('source_pgsql')->statement('DROP TABLE IF EXISTS exam_entries');
    DB::connection('source_pgsql')->statement('DROP TABLE IF EXISTS orders');

    DB::connection('source_pgsql')->statement('
        CREATE TABLE orders (
            id bigserial primary key,
            trinity_order_number varchar(255) null,
            applicant_name varchar(255) null,
            applicant_email varchar(255) null
        )
    ');

    DB::connection('source_pgsql')->statement('
        CREATE TABLE exam_entries (
            id bigserial primary key,
            teacher_name varchar(255) null
        )
    ');
});

it('creates applicant and teacher contacts from the legacy source', function () {
    DB::connection('source_pgsql')->table('orders')->insert([
        [
            'trinity_order_number' => 'ORDER-100',
            'applicant_name' => 'Clare Keeling',
            'applicant_email' => 'clare@example.com',
        ],
    ]);

    DB::connection('source_pgsql')->table('exam_entries')->insert([
        ['teacher_name' => 'Daniel Rogers'],
    ]);

    $this->artisan('contacts:import-legacy')
        ->assertExitCode(0);

    $this->assertDatabaseHas('exam_contacts', [
        'name' => 'Clare Keeling',
        'email' => 'clare@example.com',
    ]);

    $daniel = ExamContact::where('name', 'Daniel Rogers')->first();
    expect($daniel)->not->toBeNull();
    expect($daniel->isTeacher())->toBeTrue();
});

it('does not duplicate an existing contact matched by email', function () {
    ExamContact::create([
        'name' => 'Clare Keeling',
        'email' => 'clare@example.com',
    ]);

    DB::connection('source_pgsql')->table('orders')->insert([
        [
            'trinity_order_number' => 'ORDER-101',
            'applicant_name' => 'Clare Keeling',
            'applicant_email' => 'clare@example.com',
        ],
    ]);

    $this->artisan('contacts:import-legacy')
        ->assertExitCode(0);

    expect(ExamContact::where('email', 'clare@example.com')->count())->toBe(1);
});

it('links applicant contacts to existing orders when link-orders is used', function () {
    $order = Order::create([
        'trinity_order_number' => 'ORDER-102',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Venue A',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-04-10',
    ]);

    DB::connection('source_pgsql')->table('orders')->insert([
        [
            'trinity_order_number' => 'ORDER-102',
            'applicant_name' => 'Rachel Jones',
            'applicant_email' => 'rachel@example.com',
        ],
    ]);

    $this->artisan('contacts:import-legacy', [
        '--link-orders' => true,
    ])->assertExitCode(0);

    $contact = ExamContact::where('email', 'rachel@example.com')->first();

    expect($contact)->not->toBeNull();
    expect($order->fresh()->created_by_contact_id)->toBe($contact->id);

    $this->assertDatabaseHas('order_contacts', [
        'order_id' => $order->id,
        'exam_contact_id' => $contact->id,
        'role_in_order' => 'applicant',
        'is_primary' => true,
    ]);
});