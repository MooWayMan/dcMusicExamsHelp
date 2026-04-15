<?php
// tests/Feature/Console/ImportLegacyExamEntriesTest.php

use App\Models\ExamEntry;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::connection('production_import')->statement('DROP TABLE IF EXISTS exam_entries');
    DB::connection('production_import')->statement('DROP TABLE IF EXISTS orders');

    DB::connection('production_import')->statement('
        CREATE TABLE orders (
            id bigserial primary key,
            trinity_order_number varchar(255) null
        )
    ');

    DB::connection('production_import')->statement('
        CREATE TABLE exam_entries (
            id bigserial primary key,
            order_id bigint not null,
            grade varchar(255) null,
            subject_area varchar(255) null,
            delivery_method varchar(255) null,
            result varchar(255) null,
            exam_date date null,
            notes text null,
            score smallint null,
            candidate_name varchar(255) null,
            teacher_name varchar(255) null,
            school_name varchar(255) null,
            show_full_name boolean not null default false,
            show_on_thank_you boolean not null default true,
            candidate_number varchar(255) null,
            fee numeric(8,2) null
        )
    ');
});

it('imports safe legacy exam entry fields into refactor exam_entries', function () {
    $refactorOrder = Order::create([
        'trinity_order_number' => 'ORDER-200',
        'delivery_method' => 'Default',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Venue A',
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-03-05',
    ]);

    DB::connection('production_import')->table('orders')->insert([
        'id' => 999,
        'trinity_order_number' => 'ORDER-200',
    ]);

    DB::connection('production_import')->table('exam_entries')->insert([
        'order_id' => 999,
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'result' => 'Merit',
        'exam_date' => '2026-03-05',
        'notes' => 'Legacy import row',
        'score' => 78,
        'candidate_name' => 'Test Candidate',
        'teacher_name' => 'Clare Keeling',
        'school_name' => 'Learn Music Ltd',
        'show_full_name' => true,
        'show_on_thank_you' => false,
        'candidate_number' => 'CAND-123',
        'fee' => 76.00,
    ]);

    $this->artisan('exam-entries:import-legacy')
        ->assertExitCode(0);

    $this->assertDatabaseHas('exam_entries', [
        'order_id' => $refactorOrder->id,
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'result' => 'Merit',
        'candidate_name' => 'Test Candidate',
        'teacher_name' => 'Clare Keeling',
        'school_name' => 'Learn Music Ltd',
        'candidate_number' => 'CAND-123',
        'score' => 78,
    ]);
});

it('updates an existing exam entry matched by order and candidate number', function () {
    $refactorOrder = Order::create([
        'trinity_order_number' => 'ORDER-201',
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'venue' => 'Venue B',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-03-10',
    ]);

    ExamEntry::create([
        'order_id' => $refactorOrder->id,
        'candidate_name' => 'Existing Candidate',
        'candidate_number' => 'CAND-999',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'result' => 'Pass',
        'exam_date' => '2026-03-01',
        'score' => 60,
        'teacher_name' => 'Old Teacher',
        'school_name' => 'Old School',
        'show_full_name' => false,
        'show_on_thank_you' => true,
        'fee' => 61.00,
    ]);

    DB::connection('production_import')->table('orders')->insert([
        'id' => 1000,
        'trinity_order_number' => 'ORDER-201',
    ]);

    DB::connection('production_import')->table('exam_entries')->insert([
        'order_id' => 1000,
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'result' => 'Distinction',
        'exam_date' => '2026-03-10',
        'notes' => 'Updated row',
        'score' => 91,
        'candidate_name' => 'Existing Candidate',
        'teacher_name' => 'Daniel Rogers',
        'school_name' => 'Pulse Music School',
        'show_full_name' => true,
        'show_on_thank_you' => true,
        'candidate_number' => 'CAND-999',
        'fee' => 86.00,
    ]);

    $this->artisan('exam-entries:import-legacy')
        ->assertExitCode(0);

    expect(ExamEntry::count())->toBe(1);

    $this->assertDatabaseHas('exam_entries', [
        'order_id' => $refactorOrder->id,
        'candidate_number' => 'CAND-999',
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'result' => 'Distinction',
        'score' => 91,
        'teacher_name' => 'Daniel Rogers',
        'school_name' => 'Pulse Music School',
    ]);
});

it('counts missing orders instead of importing orphaned rows', function () {
    DB::connection('production_import')->table('orders')->insert([
        'id' => 1001,
        'trinity_order_number' => 'ORDER-NOT-IN-REFACTOR',
    ]);

    DB::connection('production_import')->table('exam_entries')->insert([
        'order_id' => 1001,
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'result' => 'Pass',
        'exam_date' => '2026-03-05',
        'notes' => null,
        'score' => 65,
        'candidate_name' => 'Orphan Candidate',
        'teacher_name' => 'Someone',
        'school_name' => 'Some School',
        'show_full_name' => false,
        'show_on_thank_you' => true,
        'candidate_number' => 'ORPHAN-1',
        'fee' => 61.00,
    ]);

    $this->artisan('exam-entries:import-legacy')
        ->assertExitCode(0);

    expect(ExamEntry::count())->toBe(0);
});