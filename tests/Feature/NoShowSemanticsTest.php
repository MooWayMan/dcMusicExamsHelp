<?php

// tests/Feature/NoShowSemanticsTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────────────────
// NO_SHOW vs CANCELLED semantics
// ──────────────────────────────────────────────────────────────────────────
// CANCELLED — true cancellation: refund issued, no fee, no commission.
// Excluded EVERYWHERE — Recognition, top scorers, prize draw, certificates,
// AND teacher volume tallies.
//
// NO_SHOW — booked + paid + commission earned, no submission. Excluded from
// result-based things (Recognition, top scorers, prize draw, certificates,
// pending-results queue) but DOES count for teacher volume tallies — the
// teacher booked the exam, that booking should still earn them a tally
// point toward the Bronze/Silver/Gold/Top Award badge.
//
// This file tests the result-based exclusions across the system. The
// volume-counts-NO_SHOW behaviour is exercised inside the QuarterEnd index
// flow (see QuarterEndTopScorersTest for the surrounding setup pattern).

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 5, 7, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * Build an ExamEntry the way the rest of this suite does — date-anchored
 * in Q1 2026 so quarter-scoped queries pick it up.
 */
function nsEntry(array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-NS-'.uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Anonymous Candidate',
        'instrument_id' => $piano->id,
        'grade' => '1',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => $date,
        'result' => 'Pass',
        'score' => 70,
        'show_on_thank_you' => true,
    ], $attrs));
}

// ── Scope smoke ───────────────────────────────────────────────────────────

test('whereResultPossible excludes both CANCELLED and NO_SHOW', function () {
    nsEntry(['candidate_name' => 'Clean']);
    nsEntry(['candidate_name' => 'Cancelled', 'notes' => ExamEntry::NOTE_CANCELLED]);
    nsEntry(['candidate_name' => 'NoShow',    'notes' => ExamEntry::NOTE_NO_SHOW]);

    $names = ExamEntry::whereResultPossible()->pluck('candidate_name')->all();

    expect($names)->toContain('Clean')
        ->not->toContain('Cancelled')
        ->not->toContain('NoShow');
});

test('whereResultPossible keeps entries with non-empty unrelated notes', function () {
    nsEntry(['candidate_name' => 'Has note', 'notes' => 'Late submission accepted']);

    expect(ExamEntry::whereResultPossible()->pluck('candidate_name')->all())
        ->toContain('Has note');
});

// ── Recognition page (ThankYouController) ────────────────────────────────

test('Recognition page hides NO_SHOW entries from the public table', function () {
    nsEntry(['candidate_name' => 'Visible Pass',   'score' => 88, 'result' => 'Distinction']);
    nsEntry(['candidate_name' => 'No-show Hidden', 'score' => null, 'result' => null,
             'notes' => ExamEntry::NOTE_NO_SHOW]);

    $this->get('/recognition')->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('ThankYou')
            ->where('allQuartersData', function ($quarters) {
                $names = collect($quarters)
                    ->flatMap(fn ($q) => collect($q['thankYouEntries'])->pluck('name'))
                    ->all();
                return in_array('Visible Pass', $names, true)
                    && ! collect($names)->contains(fn ($n) => str_contains($n ?? '', 'No-show'));
            })
        );
});

// ── Pending Results admin page ────────────────────────────────────────────

test('Pending Results page hides NO_SHOW entries (they will never get a score)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    nsEntry([
        'candidate_name' => 'Genuinely Pending',
        'score' => null,
        'result' => null,
    ]);
    nsEntry([
        'candidate_name' => 'Will Never Score',
        'score' => null,
        'result' => null,
        'notes' => ExamEntry::NOTE_NO_SHOW,
    ]);

    $this->actingAs($admin)->get('/admin/pending-results?quarter=1&year=2026')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/PendingResults/Index'))
        ->assertSee('Genuinely Pending')
        ->assertDontSee('Will Never Score');
});

// ── Teacher prize-draw eligibility ────────────────────────────────────────

test('NO_SHOW entries do not earn the teacher a prize-draw ticket', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Teacher with one valid + one NO_SHOW entry. Only the valid one
    // should count toward their ticket pool. Teacher needs ≥2 entries
    // OR registered teacher type to be eligible — register them so the
    // single valid entry is enough.
    $contact = ExamContact::create(['name' => 'Helen Hodgkiss']);
    $contact->addType('teacher');

    nsEntry(['teacher_name' => 'Helen Hodgkiss']);
    nsEntry(['teacher_name' => 'Helen Hodgkiss', 'notes' => ExamEntry::NOTE_NO_SHOW]);

    $response = $this->actingAs($admin)->postJson('/admin/quarter-end/draw', [
        'type' => 'teacher',
        'quarter' => 1,
        'year' => 2026,
        'mode' => 'test',
    ]);

    $response->assertOk();
    expect($response->json('total_tickets'))->toBe(1);
});

test('blank teacher_name does not create a phantom applicant row', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // One legitimate eligible teacher, one entry whose teacher_name is
    // an empty string (data entry slip-up). The empty-string row would
    // have appeared in the eligibility list as a blank applicant before
    // the filter was tightened.
    $contact = ExamContact::create(['name' => 'Helen Hodgkiss']);
    $contact->addType('teacher');

    nsEntry(['teacher_name' => 'Helen Hodgkiss']);
    nsEntry(['teacher_name' => '']);
    nsEntry(['teacher_name' => '   ']);  // whitespace-only also rejected

    $this->actingAs($admin)->get('/admin/quarter-end?quarter=1&year=2026')
        ->assertInertia(fn ($p) => $p
            ->component('admin/QuarterEnd/Index')
            ->where('teacherTickets', function ($tickets) {
                $names = collect($tickets)->pluck('applicant_name')->all();
                return in_array('Helen Hodgkiss', $names, true)
                    && ! in_array('', $names, true)
                    && ! in_array('   ', $names, true);
            })
        );
});
