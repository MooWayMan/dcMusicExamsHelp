<?php

// tests/Feature/Admin/QuarterEndTopScorersTest.php

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────────────────
// Quarter End — Top Scorer awards (Initial–5 vs 6–8)
// ──────────────────────────────────────────────────────────────────────────
// The public Awards banner promises FOUR awards each quarter:
//   • Highest Distinction — Initial–5
//   • Highest Distinction — 6–8
//   • Highest Merit       — Initial–5
//   • Highest Merit       — 6–8
//
// The /admin/quarter-end page calculates these (when no results are pending)
// and exposes them as `summary.top_scorers.{initial_5|6_8}.{distinction|merit}`,
// each leaf an array of all candidates tied at the top score in that bucket.
//
// Bands match ShowHallOfFame: Distinction ≥ 87, Merit 75–86, Pass 60–74.
// Test data anchored on 1 May 2026 (a Q2 day) but most entries are placed
// in Q1 (Jan–Mar 2026) and queried via ?quarter=1&year=2026 so dates are
// unambiguous regardless of when the suite runs.

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * Build an ExamEntry directly (no orders factory) so each test reads as a
 * single declarative table of (grade, score, name). Order is shared per
 * call so all entries fall in the same quarter window.
 */
function tsEntry(array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-TEST-'.uniqid('', true),
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
    ], $attrs));
}

// Helper: every test GETs the same URL — Q1 2026, where all our entries live.
const TS_URL = '/admin/quarter-end?quarter=1&year=2026';

// ── Empty / pending short-circuit ─────────────────────────────────────────

test('with no entries: no awards, no pending, controller renders cleanly', function () {
    $this->actingAs($this->admin)->get(TS_URL)
        ->assertStatus(200)
        ->assertInertia(fn ($p) => $p
            ->component('admin/QuarterEnd/Index')
            ->where('summary.has_pending', false)
            ->where('summary.showstopper', null)
            ->where('summary.centre_stage', null)
            ->where('summary.top_scorers.initial_5.distinction', [])
            ->where('summary.top_scorers.initial_5.merit', [])
            ->where('summary.top_scorers.6_8.distinction', [])
            ->where('summary.top_scorers.6_8.merit', []));
});

test('when any result is pending, top_scorers are empty (deliberate short-circuit)', function () {
    // One scored entry that WOULD be a Distinction…
    tsEntry(['candidate_name' => 'Would-be Winner', 'grade' => '3', 'score' => 92, 'result' => 'Distinction']);
    // …but a sibling entry has no score yet, so the page hides all awards
    // until results are complete (avoids announcing a "winner" prematurely).
    tsEntry(['candidate_name' => 'Still Waiting', 'grade' => '4', 'score' => null, 'result' => null]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.has_pending', true)
        ->where('summary.top_scorers.initial_5.distinction', [])
        ->where('summary.top_scorers.initial_5.merit', [])
        ->where('summary.top_scorers.6_8.distinction', [])
        ->where('summary.top_scorers.6_8.merit', []));
});

// ── Group classification ──────────────────────────────────────────────────

test('Initial grade is classified as Initial-5', function () {
    tsEntry(['candidate_name' => 'Anna Martin', 'grade' => 'Initial', 'score' => 91, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Anna Martin')
        ->where('summary.top_scorers.initial_5.distinction.0.score', 91)
        ->where('summary.top_scorers.initial_5.distinction.0.grade', 'Initial')
        ->where('summary.top_scorers.6_8.distinction', []));
});

test('grades 1 through 5 land in Initial-5', function () {
    foreach (['1', '2', '3', '4', '5'] as $grade) {
        tsEntry(['candidate_name' => "G$grade Top", 'grade' => $grade, 'score' => 88, 'result' => 'Distinction']);
    }
    // Non-tied: pick the unique top with one slightly higher score
    tsEntry(['candidate_name' => 'Clear Winner', 'grade' => '3', 'score' => 95, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.initial_5.distinction', 1)
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Clear Winner')
        ->where('summary.top_scorers.6_8.distinction', []));
});

test('grades 6 through 8 land in the 6-8 group', function () {
    tsEntry(['candidate_name' => 'Six',   'grade' => '6', 'score' => 90, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Seven', 'grade' => '7', 'score' => 91, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Eight', 'grade' => '8', 'score' => 95, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.6_8.distinction', 1)
        ->where('summary.top_scorers.6_8.distinction.0.full_name', 'Eight')
        ->where('summary.top_scorers.6_8.distinction.0.score', 95)
        ->where('summary.top_scorers.initial_5.distinction', []));
});

// ── Bands ─────────────────────────────────────────────────────────────────

test('only Distinctions (>=87) go into the distinction bucket', function () {
    tsEntry(['candidate_name' => 'Edge Distinction', 'grade' => '2', 'score' => 87, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Top Merit',        'grade' => '2', 'score' => 86, 'result' => 'Merit']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Edge Distinction')
        ->where('summary.top_scorers.initial_5.merit.0.full_name', 'Top Merit'));
});

test('Merits in the 75-86 window go into the merit bucket; Pass scores are ignored', function () {
    tsEntry(['candidate_name' => 'High Merit', 'grade' => '4', 'score' => 86, 'result' => 'Merit']);
    tsEntry(['candidate_name' => 'Low Merit',  'grade' => '4', 'score' => 75, 'result' => 'Merit']);
    tsEntry(['candidate_name' => 'Just Pass',  'grade' => '4', 'score' => 74, 'result' => 'Pass']); // ignored

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.initial_5.merit', 1)
        ->where('summary.top_scorers.initial_5.merit.0.full_name', 'High Merit')
        ->where('summary.top_scorers.initial_5.merit.0.score', 86)
        ->where('summary.top_scorers.initial_5.distinction', []));
});

test('Pass-only quarter produces no awards but does not error', function () {
    tsEntry(['candidate_name' => 'Pass One', 'grade' => '2', 'score' => 70, 'result' => 'Pass']);
    tsEntry(['candidate_name' => 'Pass Two', 'grade' => '7', 'score' => 65, 'result' => 'Pass']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.has_pending', false)
        ->where('summary.top_scorers.initial_5.distinction', [])
        ->where('summary.top_scorers.initial_5.merit', [])
        ->where('summary.top_scorers.6_8.distinction', [])
        ->where('summary.top_scorers.6_8.merit', []));
});

// ── Ties ──────────────────────────────────────────────────────────────────

test('two-way tie at the top of Initial-5 Merit returns BOTH winners', function () {
    tsEntry(['candidate_name' => 'Maya Parkinson',         'grade' => '1',       'score' => 83, 'result' => 'Merit']);
    tsEntry(['candidate_name' => 'Teddy Thompson-Davies',  'grade' => 'Initial', 'score' => 83, 'result' => 'Merit']);
    tsEntry(['candidate_name' => 'Imogen Hughes',          'grade' => '3',       'score' => 82, 'result' => 'Merit']); // not tied

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.initial_5.merit', 2)
        ->where('summary.top_scorers.initial_5.merit.0.score', 83)
        ->where('summary.top_scorers.initial_5.merit.1.score', 83));
});

test('three-way tie at the top of 6-8 Distinction returns ALL three winners', function () {
    tsEntry(['candidate_name' => 'A', 'grade' => '6', 'score' => 95, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'B', 'grade' => '7', 'score' => 95, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'C', 'grade' => '8', 'score' => 95, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Lower', 'grade' => '8', 'score' => 90, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.6_8.distinction', 3)
        ->where('summary.top_scorers.6_8.distinction.0.score', 95)
        ->where('summary.top_scorers.6_8.distinction.1.score', 95)
        ->where('summary.top_scorers.6_8.distinction.2.score', 95));
});

test('only candidates tied at the very top score are returned (others are dropped)', function () {
    tsEntry(['candidate_name' => 'Top A',  'grade' => '2', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Top B',  'grade' => '2', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Almost', 'grade' => '2', 'score' => 91, 'result' => 'Distinction']); // dropped

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.initial_5.distinction', 2));
});

// ── Group independence ────────────────────────────────────────────────────

test('a higher 6-8 Distinction does not displace the Initial-5 Distinction winner', function () {
    tsEntry(['candidate_name' => 'Init5 Best', 'grade' => '3', 'score' => 88, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => '6_8 Best',   'grade' => '8', 'score' => 95, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Init5 Best')
        ->where('summary.top_scorers.6_8.distinction.0.full_name', '6_8 Best'));
});

test('mixed quarter: every group/band has a winner', function () {
    // Initial-5
    tsEntry(['candidate_name' => 'I5 Distinction', 'grade' => '2', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'I5 Merit',       'grade' => '4', 'score' => 83, 'result' => 'Merit']);
    // 6-8
    tsEntry(['candidate_name' => '68 Distinction', 'grade' => '7', 'score' => 90, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => '68 Merit',       'grade' => '6', 'score' => 81, 'result' => 'Merit']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'I5 Distinction')
        ->where('summary.top_scorers.initial_5.merit.0.full_name',       'I5 Merit')
        ->where('summary.top_scorers.6_8.distinction.0.full_name',       '68 Distinction')
        ->where('summary.top_scorers.6_8.merit.0.full_name',             '68 Merit'));
});

// ── Legacy compatibility ──────────────────────────────────────────────────

test('showstopper trophy returns ALL tied winners when more than one candidate scored the top', function () {
    // Bug previously: trophy stat tile silently picked the first
    // alphabetical winner when two candidates tied at the absolute top
    // distinction. Should expose every tied winner in the `winners[]`
    // array so the Vue tile can list each name.
    tsEntry(['candidate_name' => 'Seth Barraclough', 'grade' => '8', 'score' => 93, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'James Jones',      'grade' => '7', 'score' => 93, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Lower One',        'grade' => '6', 'score' => 90, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.showstopper.score', 93)
        ->has('summary.showstopper.winners', 2)
        ->where('summary.showstopper.winners.0.full_name', 'Seth Barraclough')
        ->where('summary.showstopper.winners.1.full_name', 'James Jones'));
});

test('showstopper.winners has exactly one entry when there is no tie', function () {
    tsEntry(['candidate_name' => 'Sole Winner', 'grade' => '8', 'score' => 95, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Lower',       'grade' => '7', 'score' => 90, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.showstopper.winners', 1)
        ->where('summary.showstopper.winners.0.full_name', 'Sole Winner'));
});

test('legacy showstopper/centre_stage still point to the overall single top across both groups', function () {
    // 6-8 wins the absolute top Distinction (95 > 92), so showstopper picks
    // up the 6-8 winner — legacy field is the OVERALL top, not per group.
    tsEntry(['candidate_name' => 'I5 Top D', 'grade' => '2', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => '68 Top D', 'grade' => '8', 'score' => 95, 'result' => 'Distinction']);
    // Initial-5 wins the absolute top Merit (84 > 81)
    tsEntry(['candidate_name' => 'I5 Top M', 'grade' => '3', 'score' => 84, 'result' => 'Merit']);
    tsEntry(['candidate_name' => '68 Top M', 'grade' => '7', 'score' => 81, 'result' => 'Merit']);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.showstopper.full_name', '68 Top D')
        ->where('summary.showstopper.score', 95)
        ->where('summary.centre_stage.full_name', 'I5 Top M')
        ->where('summary.centre_stage.score', 84));
});

// ── Quarter scoping ───────────────────────────────────────────────────────

test('a higher score outside the queried quarter is ignored', function () {
    // Q1 winner — what we expect to surface
    tsEntry([
        'candidate_name' => 'Q1 Winner',
        'grade' => '3',
        'score' => 88,
        'result' => 'Distinction',
        'exam_date' => Carbon::create(2026, 2, 15),
    ]);
    // Q2 ringer scoring higher — must NOT appear when querying Q1
    tsEntry([
        'candidate_name' => 'Q2 Ringer',
        'grade' => '3',
        'score' => 99,
        'result' => 'Distinction',
        'exam_date' => Carbon::create(2026, 4, 15),
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->has('summary.top_scorers.initial_5.distinction', 1)
        ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Q1 Winner'));
});

// ── Auth ──────────────────────────────────────────────────────────────────

test('non-admin cannot reach quarter-end', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $this->actingAs($teacher)->get('/admin/quarter-end')->assertStatus(403);
});

test('guests are redirected to login', function () {
    $this->get('/admin/quarter-end')->assertRedirect('/login');
});

// ── Finalise override ─────────────────────────────────────────────────────
// `?finalise=1` lets Paul lock in the awards even when results are still
// pending — used when the remaining stragglers are very unlikely to top
// the current leaders, and Paul's happy to honour any after-the-fact tie
// by topping up the gift token.

test('finalise=1 reveals awards even when results are pending', function () {
    tsEntry(['candidate_name' => 'Anna Martin',  'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Still Waiting', 'grade' => '4', 'score' => null, 'result' => null]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=1&year=2026&finalise=1')
        ->assertInertia(fn ($p) => $p
            ->where('summary.has_pending', true)
            ->where('summary.finalised_with_pending', true)
            ->where('summary.top_scorers.initial_5.distinction.0.full_name', 'Anna Martin')
            ->where('summary.top_scorers.initial_5.distinction.0.score', 92));
});

test('without finalise=1 the awards stay hidden when pending', function () {
    tsEntry(['candidate_name' => 'Anna Martin',  'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tsEntry(['candidate_name' => 'Still Waiting', 'grade' => '4', 'score' => null, 'result' => null]);

    $this->actingAs($this->admin)
        ->get(TS_URL)
        ->assertInertia(fn ($p) => $p
            ->where('summary.has_pending', true)
            ->where('summary.finalised_with_pending', false)
            ->where('summary.top_scorers.initial_5.distinction', []));
});

test('finalised_with_pending is false when there are no pending results', function () {
    // Even if finalise=1 is sent, the flag should only be true when there
    // were ACTUALLY pending results — otherwise the provisional warning
    // banner would show on a fully-resolved quarter.
    tsEntry(['candidate_name' => 'Anna Martin', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=1&year=2026&finalise=1')
        ->assertInertia(fn ($p) => $p
            ->where('summary.has_pending', false)
            ->where('summary.finalised_with_pending', false));
});

// ── Winner shape (drives the per-winner email button) ─────────────────────

test('each winner row includes teacher_name and teacher_email so the email button can route', function () {
    // Order's applicant_email serves as the fallback when no parent/self
    // contact is linked — this is the most common case.
    $date = Carbon::create(2026, 2, 15);
    $order = \App\Models\Order::create([
        'trinity_order_number' => '1-TEST-'.uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
        'applicant_email' => 'sarah@example.com',
        'applicant_name' => 'Sarah Mitchell',
    ]);
    $piano = \App\Models\Instrument::firstOrCreate(['name' => 'Piano']);
    \App\Models\ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Maya Parkinson',
        'instrument_id' => $piano->id,
        'grade' => '1',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => $date,
        'result' => 'Distinction',
        'score' => 92,
        'teacher_name' => 'Sarah Mitchell',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.teacher_name', 'Sarah Mitchell')
        ->where('summary.top_scorers.initial_5.distinction.0.teacher_email', 'sarah@example.com')
        ->where('summary.top_scorers.initial_5.distinction.0.is_parent_booking', false)
        ->where('summary.top_scorers.initial_5.distinction.0.booking_role', null));
});

test('shortName uses the first given name + surname initial (matches public Recognition page)', function () {
    // Regression: shortName previously took the LAST given name before the
    // surname as the "name they go by" (so "Alice Jun Mei Khoo" → "Mei K").
    // That misfires for anyone who actually goes by their first given name —
    // most candidates — and silently disagreed with ThankYouController which
    // always uses the first given name. Both should produce "Alice K" now.
    tsEntry([
        'candidate_name' => 'Alice Jun Mei Khoo',
        'grade' => '6',
        'score' => 81,
        'result' => 'Merit',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.6_8.merit.0.full_name', 'Alice Jun Mei Khoo')
        ->where('summary.top_scorers.6_8.merit.0.name', 'Alice K'));
});

test('shortName handles two-part names correctly', function () {
    tsEntry([
        'candidate_name' => 'Anna Martin',
        'grade' => '1',
        'score' => 92,
        'result' => 'Distinction',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.name', 'Anna M'));
});

test('shortName falls back to the full name if it is a single word', function () {
    tsEntry([
        'candidate_name' => 'Madonna',
        'grade' => '3',
        'score' => 90,
        'result' => 'Distinction',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.initial_5.distinction.0.name', 'Madonna'));
});

test('shortName uppercases a lowercase surname initial', function () {
    tsEntry([
        'candidate_name' => 'James van der Berg',
        'grade' => '7',
        'score' => 90,
        'result' => 'Distinction',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.6_8.distinction.0.name', 'James B'));
});

test('winner with a parent-booking teacher_name is flagged is_parent_booking', function () {
    $parent = \App\Models\ExamContact::create(['name' => 'Mrs Khoo']);
    $parent->addType('parent');

    tsEntry([
        'candidate_name' => 'Alice Khoo',
        'grade' => '6',
        'score' => 95,
        'result' => 'Distinction',
        'teacher_name' => 'Mrs Khoo',
    ]);

    $this->actingAs($this->admin)->get(TS_URL)->assertInertia(fn ($p) => $p
        ->where('summary.top_scorers.6_8.distinction.0.teacher_name', 'Mrs Khoo')
        ->where('summary.top_scorers.6_8.distinction.0.is_parent_booking', true)
        ->where('summary.top_scorers.6_8.distinction.0.booking_role', 'parent'));
});
