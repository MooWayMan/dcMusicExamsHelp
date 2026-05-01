<?php

// tests/Feature/Console/BackfillQ1MissingResultsTest.php

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Student;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Backfill — Q1 missing results recovery
// ──────────────────────────────────────────
// One-shot recovery for the 17 candidates whose results we pulled from TOL
// on 30 Apr 2026 but never wrote to the database. Source of truth: the
// 30 Apr handover file in Paul's Claude folder.

function makeBackfillOrder(): Order
{
    return Order::create([
        'trinity_order_number' => '1-TEST-' . uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 3, 10),
    ]);
}

function makeBackfillEntry(string $candidateNumber, string $candidateName, ?int $score = null): ExamEntry
{
    return ExamEntry::create([
        'order_id' => makeBackfillOrder()->id,
        'candidate_number' => $candidateNumber,
        'candidate_name' => $candidateName,
        'grade' => 'Grade 1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => null, // pre-backfill state — score and exam_date both null
        'score' => $score,
    ]);
}

test('backfill updates the 17 known candidates with correct score and result', function () {
    // Pick a handful covering all three result bands — full list isn't needed
    // for the assertion, we're proving the matching + update logic works.
    $anu = makeBackfillEntry('1-15279077954', 'Anugrahchandra Nidhin');
    $delfina = makeBackfillEntry('1-15899370904', 'Delfina Yelich Battisacchi');
    $clayton = makeBackfillEntry('1-15280254974', 'Clayton Lo');

    $this->artisan('exam:backfill-q1-missing-results')
        ->assertExitCode(0);

    expect($anu->fresh()->score)->toBe(77);
    expect($anu->fresh()->result)->toBe('Merit');
    expect($anu->fresh()->exam_date->toDateString())->toBe('2026-03-10');

    expect($delfina->fresh()->score)->toBe(88);
    expect($delfina->fresh()->result)->toBe('Distinction');
    expect($delfina->fresh()->exam_date->toDateString())->toBe('2026-03-30');

    // Clayton — fail with 0, recorded as TOL had it
    expect($clayton->fresh()->score)->toBe(0);
    expect($clayton->fresh()->result)->toBe('Fail');
});

test('backfill fixes Milo surname Hugh -> Lydon', function () {
    makeBackfillEntry('1-15280573404', 'Milo Hugh');
    Student::create(['first_name' => 'Milo', 'last_name' => 'Hugh']);

    $this->artisan('exam:backfill-q1-missing-results')->assertExitCode(0);

    $this->assertDatabaseHas('students', ['first_name' => 'Milo', 'last_name' => 'Lydon']);
    $this->assertDatabaseMissing('students', ['first_name' => 'Milo', 'last_name' => 'Hugh']);
});

test('backfill leaves Oscar Cain and Otis Frieze untouched (still genuinely pending)', function () {
    $oscar = makeBackfillEntry('1-15451580044', 'Oscar Cain');
    $otis  = makeBackfillEntry('1-15451220944', 'Otis Frieze');

    $this->artisan('exam:backfill-q1-missing-results')->assertExitCode(0);

    expect($oscar->fresh()->score)->toBeNull();
    expect($oscar->fresh()->result)->toBeNull();
    expect($otis->fresh()->score)->toBeNull();
    expect($otis->fresh()->result)->toBeNull();
});

test('backfill is idempotent — running twice does not re-touch already-scored rows', function () {
    $anu = makeBackfillEntry('1-15279077954', 'Anugrahchandra Nidhin');

    // First run — update lands
    $this->artisan('exam:backfill-q1-missing-results')->assertExitCode(0);
    $afterFirst = $anu->fresh();
    expect($afterFirst->score)->toBe(77);

    // Manually bump the score afterwards — simulates Paul correcting it
    $anu->update(['score' => 99, 'result' => 'Distinction']);

    // Second run must NOT clobber the manual change.
    $this->artisan('exam:backfill-q1-missing-results')->assertExitCode(0);
    expect($anu->fresh()->score)->toBe(99);
    expect($anu->fresh()->result)->toBe('Distinction');
});

test('backfill warns if a candidate is missing entirely (no exam_entries row)', function () {
    // No entries created — every candidate should be reported as missing.
    $this->artisan('exam:backfill-q1-missing-results')
        ->expectsOutputToContain('Missing exam_entries')
        ->assertExitCode(0);
});

test('dry-run does not write any changes', function () {
    $anu = makeBackfillEntry('1-15279077954', 'Anugrahchandra Nidhin');

    $this->artisan('exam:backfill-q1-missing-results --dry-run')->assertExitCode(0);

    expect($anu->fresh()->score)->toBeNull();
    expect($anu->fresh()->result)->toBeNull();
});
