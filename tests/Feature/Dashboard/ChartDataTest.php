<?php

// tests/Feature/Dashboard/ChartDataTest.php
//
// The "At a glance" charts on the teacher dashboard are drawn entirely from
// the `examEntries` prop — no extra request, no separate endpoint. That makes
// the payload the contract: drop `grade`, `score` or `exam_date` from
// DashboardController's select and the charts silently render empty rather
// than erroring, which is exactly the kind of regression nobody notices.
//
// These tests pin the fields the charts read, and pin the two rules the
// charts share with the rest of the page: the range bounds them, and a
// candidate still awaiting a result is included.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function chartOrder(Carbon $start): Order
{
    return Order::create([
        'trinity_order_number' => '1-CHART-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $start,
    ]);
}

function chartTeacher(): ExamContact
{
    $contact = ExamContact::create([
        'name' => 'Maria Nielsen',
        'email' => 'maria@example.com',
        'source' => 'trinity_csv',
    ]);
    $contact->addType('teacher');

    return $contact;
}

function chartEntry(ExamContact $teacher, array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? null;

    return ExamEntry::create(array_merge([
        'order_id' => chartOrder($date ?? Carbon::create(2026, 3, 1))->id,
        'candidate_number' => '1-'.uniqid('', true),
        'candidate_name' => 'A Candidate',
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'teacher_contact_id' => $teacher->id,
    ], $attrs));
}

beforeEach(function (): void {
    $this->teacher = chartTeacher();
    $this->user = User::factory()->create([
        'email' => 'maria@example.com',
        'role' => 'teacher',
    ]);
});

test('every field the charts read is present on each entry', function () {
    chartEntry($this->teacher, [
        'grade' => '6',
        'score' => 91,
        'result' => 'Distinction',
        'exam_date' => Carbon::create(2026, 3, 12),
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('examEntries', 1)
            ->where('examEntries.0.grade', '6')
            ->where('examEntries.0.score', 91)
            ->where('examEntries.0.result', 'Distinction')
            // "d M Y" — the charts parse this shape, not an ISO string.
            ->where('examEntries.0.exam_date', '12 Mar 2026'));
});

test('a candidate awaiting a result still reaches the charts', function () {
    // No score, no result, no exam date — the Awaiting slice depends on this
    // row arriving rather than being filtered out upstream.
    chartEntry($this->teacher, [
        'grade' => '3',
        'score' => null,
        'result' => null,
        'exam_date' => null,
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p
            ->has('examEntries', 1)
            ->where('examEntries.0.score', null)
            ->where('examEntries.0.result', null)
            ->where('examEntries.0.exam_date', null));
});

test('the charts see the same slice as the table and the downloads', function () {
    chartEntry($this->teacher, ['exam_date' => Carbon::create(2026, 2, 10), 'score' => 80, 'result' => 'Merit']);
    chartEntry($this->teacher, ['exam_date' => Carbon::create(2026, 7, 10), 'score' => 65, 'result' => 'Pass']);

    $this->actingAs($this->user)
        ->get('/dashboard?from=2026-06-01&to=2026-08-01')
        ->assertInertia(fn ($p) => $p
            ->has('examEntries', 1)
            ->where('examEntries.0.exam_date', '10 Jul 2026')
            ->where('filters.from', '2026-06-01')
            ->where('filters.to', '2026-08-01'));
});

test('the range the charts label themselves with is the one the server used', function () {
    chartEntry($this->teacher, ['exam_date' => Carbon::create(2026, 4, 2), 'score' => 88, 'result' => 'Distinction']);

    // Backwards range: the controller swaps it, and the charts must caption
    // themselves with the corrected bounds rather than what was typed.
    $this->actingAs($this->user)
        ->get('/dashboard?from=2026-08-01&to=2026-01-01')
        ->assertInertia(fn ($p) => $p
            ->where('filters.from', '2026-01-01')
            ->where('filters.to', '2026-08-01')
            ->has('examEntries', 1));
});
