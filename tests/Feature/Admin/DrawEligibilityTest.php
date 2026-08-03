<?php

// tests/Feature/Admin/DrawEligibilityTest.php
//
// Who gets a ticket in which draw. Paul's rule, 3 Aug 2026:
//
//   TEACHER draw follows the MONEY. Trinity charged the fee and paid
//   commission whether or not the candidate turned up, so the teacher earned
//   their ticket by making the entry — "i dont mind the teacher going in the
//   draw, the money was paid".
//
//   STUDENT draw follows the EXAM. No sitting, no certificate, no ticket.
//
//   CANCELLED is a refund and is out of both.
//
// A December re-sit on a 100% voucher therefore gives the candidate a ticket
// and the teacher nothing: no money changes hands, and the teacher was
// already credited when the entry was first made in July.

use App\Models\ExamEntry;
use App\Models\Order;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function drawOrder(): Order
{
    return Order::create([
        'trinity_order_number' => '1-DRAW-'.uniqid('', true),
        'delivery_method' => 'Default',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => Carbon::create(2026, 7, 10),
    ]);
}

function drawEntry(?string $notes, ?int $score = 80): ExamEntry
{
    return ExamEntry::create([
        'order_id' => drawOrder()->id,
        'candidate_name' => 'Candidate '.uniqid('', true),
        'candidate_number' => '1-'.random_int(10000000, 99999999),
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Default',
        'teacher_name' => 'Daniel Rogers',
        'exam_date' => Carbon::create(2026, 7, 10),
        'score' => $notes === null ? $score : null,
        'notes' => $notes,
    ]);
}

test('a sat exam counts for both draws', function () {
    drawEntry(null);

    expect(ExamEntry::whereResultPossible()->count())->toBe(1);
});

test('a no-show is out of the student draw', function () {
    drawEntry(ExamEntry::NOTE_NO_SHOW);

    // whereResultPossible() is what gates the student pool, certificates,
    // Recognition and Pending Results.
    expect(ExamEntry::whereResultPossible()->count())->toBe(0)
        ->and(ExamEntry::count())->toBe(1);
});

test('a withdrawal with a re-entry permit is out of the student draw', function () {
    $e = drawEntry(ExamEntry::NOTE_RE_ENTRY);
    $e->update(['re_entry_code' => '1-18154879067']);

    expect(ExamEntry::whereResultPossible()->count())->toBe(0)
        ->and($e->fresh()->re_entry_code)->toBe('1-18154879067');
});

test('a cancelled entry is out of everything, including the paid set', function () {
    drawEntry(ExamEntry::NOTE_CANCELLED);

    $paid = ExamEntry::query()
        ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '!=', ExamEntry::NOTE_CANCELLED))
        ->count();

    expect($paid)->toBe(0)
        ->and(ExamEntry::whereResultPossible()->count())->toBe(0);
});

test('no-shows and withdrawals stay in the paid set the teacher draw uses', function () {
    drawEntry(null);
    drawEntry(ExamEntry::NOTE_NO_SHOW);
    drawEntry(ExamEntry::NOTE_RE_ENTRY);
    drawEntry(ExamEntry::NOTE_CANCELLED);

    $paid = ExamEntry::query()
        ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '!=', ExamEntry::NOTE_CANCELLED))
        ->count();

    // Three paid entries earn teacher tickets; only the one sat exam earns a
    // student ticket.
    expect($paid)->toBe(3)
        ->and(ExamEntry::whereResultPossible()->count())->toBe(1);
});
