<?php

// tests/Feature/Dashboard/PrizeDrawTicketsTest.php
//
// "You have N tickets" on the teacher dashboard.
//
// The count matched on the teacher_name STRING only. The enrolment-list import
// writes teacher_name = null — Trinity doesn't name a teacher until results
// arrive — so a teacher saw "You have 0 tickets in the Q3 2026 draw so far"
// directly above a list of their own pending candidates. Found on Daniel
// Rogers (contact 84, prod): 61 entries credited to him, 11 of them with no
// teacher_name at all.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Freeze inside Q3 2026 so "current quarter" can't drift with the calendar.
    Carbon::setTestNow(Carbon::create(2026, 8, 3, 12));

    $this->teacher = ExamContact::create([
        'name' => 'Daniel Rogers',
        'email' => 'daniel@example.com',
        'source' => 'trinity_csv',
    ]);
    $this->teacher->addType('teacher');

    $this->user = User::factory()->create([
        'email' => 'daniel@example.com',
        'role' => 'teacher',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function q3Order(): Order
{
    return Order::create([
        'trinity_order_number' => '1-TICK-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 7, 20),
    ]);
}

test('a pre-result entry linked only by submitter still earns a ticket', function () {
    // Exactly what the enrolment-list import writes.
    ExamEntry::create([
        'order_id' => q3Order()->id,
        'candidate_name' => 'Awaiting Candidate',
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'teacher_name' => null,
        'teacher_contact_id' => null,
        'submitter_contact_id' => $this->teacher->id,
        'score' => null,
        'result' => null,
        'exam_date' => null,
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->where('teacherPrizeDraw.my_current_quarter_tickets', 1));
});

test('a named teacher still earns their ticket', function () {
    ExamEntry::create([
        'order_id' => q3Order()->id,
        'candidate_name' => 'Named Candidate',
        'grade' => '3',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'teacher_name' => 'Daniel Rogers',
        'exam_date' => Carbon::create(2026, 7, 20),
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p->where('teacherPrizeDraw.my_current_quarter_tickets', 1));
});

test('being both the named teacher and the submitter is one ticket, not two', function () {
    ExamEntry::create([
        'order_id' => q3Order()->id,
        'candidate_name' => 'Own Pupil',
        'grade' => '5',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'teacher_name' => 'Daniel Rogers',
        'teacher_contact_id' => $this->teacher->id,
        'submitter_contact_id' => $this->teacher->id,
        'exam_date' => Carbon::create(2026, 7, 20),
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p->where('teacherPrizeDraw.my_current_quarter_tickets', 1));
});

test('another teachers candidate earns this teacher nothing', function () {
    $other = ExamContact::create([
        'name' => 'Someone Else',
        'email' => 'else@example.com',
        'source' => 'trinity_csv',
    ]);

    ExamEntry::create([
        'order_id' => q3Order()->id,
        'candidate_name' => 'Not Theirs',
        'grade' => '2',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'teacher_name' => null,
        'submitter_contact_id' => $other->id,
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p->where('teacherPrizeDraw.my_current_quarter_tickets', 0));
});

test('an entry from a previous quarter does not count toward this one', function () {
    $order = Order::create([
        'trinity_order_number' => '1-TICKQ1-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 2, 10),
    ]);

    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Q1 Candidate',
        'grade' => '1',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'submitter_contact_id' => $this->teacher->id,
        'exam_date' => Carbon::create(2026, 2, 10),
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p->where('teacherPrizeDraw.my_current_quarter_tickets', 0));
});
