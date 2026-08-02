<?php

// tests/Feature/Support/EntryCreditTest.php
//
// The credit + awaiting rules shared by /admin/quarter-end and the certificate
// generator. Regression: Penelope Jane Mitchell (Q2 2026) came in via the
// Section 1b enrolment-list import with teacher_name = null, so the cert
// report grouped her into nobody's bucket and printed no "Awaiting Results"
// block, while Quarter End correctly credited her to Paul via the submitter.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Support\EntryCredit;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function creditEntry(array $attrs = []): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-CREDIT-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 6, 30),
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Anonymous Candidate',
        'grade' => 'Initial',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
    ], $attrs));
}

// ── nameFor ───────────────────────────────────────────────────────────────

test('an entry with a teacher name is credited to that teacher', function () {
    $entry = creditEntry(['teacher_name' => 'Paul Sheridan']);

    expect(EntryCredit::nameFor($entry, []))->toBe('Paul Sheridan');
});

test('a pre-result entry with no teacher name is credited to its submitter', function () {
    $paul = ExamContact::create(['name' => 'Paul Sheridan', 'email' => 'madmusic6@example.com', 'source' => 'manual']);

    // Exactly what the enrolment-list import writes: no score, no teacher,
    // no exam date — only the submitter link.
    $entry = creditEntry([
        'candidate_name' => 'Penelope Jane Mitchell',
        'teacher_name' => null,
        'score' => null,
        'exam_date' => null,
        'submitter_contact_id' => $paul->id,
        'source' => 'trinity_enrolment_list',
    ]);

    $names = EntryCredit::submitterNames(collect([$entry]));

    expect(EntryCredit::nameFor($entry, $names))->toBe('Paul Sheridan');
});

test('a blank-string teacher name is treated as absent, not its own bucket', function () {
    $paul = ExamContact::create(['name' => 'Paul Sheridan', 'email' => 'blank@example.com', 'source' => 'manual']);
    $entry = creditEntry(['teacher_name' => '   ', 'submitter_contact_id' => $paul->id]);

    $names = EntryCredit::submitterNames(collect([$entry]));

    expect(EntryCredit::nameFor($entry, $names))->toBe('Paul Sheridan');
});

test('an entry with neither a teacher nor a submitter falls back', function () {
    $entry = creditEntry(['teacher_name' => null, 'submitter_contact_id' => null]);

    expect(EntryCredit::nameFor($entry, []))->toBe('Unassigned');
});

test('the teacher name wins over the submitter when both are present', function () {
    $paul = ExamContact::create(['name' => 'Paul Sheridan', 'email' => 'both@example.com', 'source' => 'manual']);
    $entry = creditEntry(['teacher_name' => 'Maria Nielsen', 'submitter_contact_id' => $paul->id]);

    $names = EntryCredit::submitterNames(collect([$entry]));

    expect(EntryCredit::nameFor($entry, $names))->toBe('Maria Nielsen');
});

test('submitterNames resolves every referenced contact in one lookup', function () {
    $paul = ExamContact::create(['name' => 'Paul Sheridan', 'email' => 'a@example.com', 'source' => 'manual']);
    $maria = ExamContact::create(['name' => 'Maria Nielsen', 'email' => 'b@example.com', 'source' => 'manual']);

    $entries = collect([
        creditEntry(['submitter_contact_id' => $paul->id]),
        creditEntry(['submitter_contact_id' => $maria->id]),
        creditEntry(['submitter_contact_id' => null]),
    ]);

    expect(EntryCredit::submitterNames($entries))
        ->toBe([$paul->id => 'Paul Sheridan', $maria->id => 'Maria Nielsen']);
});

test('submitterNames on entries with no submitters does not query for nothing', function () {
    expect(EntryCredit::submitterNames(collect([creditEntry()])))->toBe([]);
});

// ── isAwaitingResult ──────────────────────────────────────────────────────

test('an unscored entry is awaiting a result', function () {
    expect(EntryCredit::isAwaitingResult(creditEntry(['score' => null])))->toBeTrue();
});

test('a scored entry is not awaiting a result', function () {
    expect(EntryCredit::isAwaitingResult(creditEntry(['score' => 52])))->toBeFalse();
});

test('NO_SHOW and CANCELLED are never awaiting a result', function () {
    // Both have a null score, but Trinity will never issue one — telling a
    // teacher to expect a result would be wrong.
    expect(EntryCredit::isAwaitingResult(creditEntry(['score' => null, 'notes' => ExamEntry::NOTE_NO_SHOW])))->toBeFalse()
        ->and(EntryCredit::isAwaitingResult(creditEntry(['score' => null, 'notes' => ExamEntry::NOTE_CANCELLED])))->toBeFalse();
});
