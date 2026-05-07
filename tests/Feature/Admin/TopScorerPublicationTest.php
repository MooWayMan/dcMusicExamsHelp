<?php

// tests/Feature/Admin/TopScorerPublicationTest.php
//
// Covers the full publish flow:
//   • POST /admin/quarter-end/publish-top-scorers writes a snapshot
//   • Public /recognition reads from the snapshot regardless of pending
//   • Re-publishing overwrites
//   • Auth: only admins can publish

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\PrizeDraw;
use App\Models\TopScorerPublication;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function tspEntry(array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-TSP-'.uniqid('', true),
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
        'result' => 'Distinction',
        'score' => 90,
    ], $attrs));
}

// ── Publish endpoint ─────────────────────────────────────────────────────

test('admin can publish top-scorer awards for a quarter', function () {
    tspEntry(['candidate_name' => 'Anna Martin',     'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Maya Parkinson',  'grade' => '1', 'score' => 83, 'result' => 'Merit']);
    tspEntry(['candidate_name' => 'Seth Barraclough','grade' => '8', 'score' => 93, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Mia Mason',       'grade' => '7', 'score' => 81, 'result' => 'Merit']);

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/publish-top-scorers', [
            'quarter' => 1,
            'year' => 2026,
        ]);

    $response->assertStatus(200);
    expect($response->json('success'))->toBeTrue();
    expect($response->json('winner_count'))->toBe(4);

    $pub = TopScorerPublication::forQuarter(1, 2026);
    expect($pub)->not->toBeNull();
    expect($pub->winners['initial_5']['distinction'])->toHaveCount(1);
    expect($pub->winners['initial_5']['distinction'][0]['full_name'])->toBe('Anna Martin');
    expect($pub->winners['6_8']['distinction'][0]['full_name'])->toBe('Seth Barraclough');
    expect($pub->finalised_with_pending)->toBeFalse();
    expect($pub->pending_count)->toBe(0);
    expect($pub->published_by)->toBe($this->admin->id);
});

test('publishing while results are pending records the pending count and flag', function () {
    tspEntry(['candidate_name' => 'Anna Martin',  'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Otis Frieze',  'grade' => '4', 'score' => null, 'result' => null]);
    tspEntry(['candidate_name' => 'Oscar Cain',   'grade' => '2', 'score' => null, 'result' => null]);

    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/publish-top-scorers', [
            'quarter' => 1,
            'year' => 2026,
        ])
        ->assertStatus(200);

    $pub = TopScorerPublication::forQuarter(1, 2026);
    expect($pub->finalised_with_pending)->toBeTrue();
    expect($pub->pending_count)->toBe(2);
});

test('re-publishing overwrites the snapshot', function () {
    tspEntry(['candidate_name' => 'Anna Martin', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);

    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);
    $first = TopScorerPublication::forQuarter(1, 2026);
    expect($first->winners['initial_5']['distinction'][0]['full_name'])->toBe('Anna Martin');

    // Add a new top scorer and re-publish
    tspEntry(['candidate_name' => 'Aria Chambers', 'grade' => '1', 'score' => 95, 'result' => 'Distinction']);
    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);

    $second = TopScorerPublication::forQuarter(1, 2026);
    expect($second->id)->toBe($first->id); // same row, updateOrCreate
    expect($second->winners['initial_5']['distinction'][0]['full_name'])->toBe('Aria Chambers');
});

test('non-admin cannot publish', function () {
    tspEntry(['candidate_name' => 'Anna', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);

    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026])
        ->assertStatus(403);

    expect(TopScorerPublication::count())->toBe(0);
});

test('publish endpoint validates quarter and year', function () {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 5, 'year' => 2026])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['quarter']);
});

// ── Public Recognition page reads the snapshot ───────────────────────────

test('public Recognition page renders snapshot winners regardless of pending', function () {
    // Even with pending entries, once published, the public page must show
    // the awards. This is the whole point of the publish flow — Paul can
    // announce winners before every result is in.
    tspEntry(['candidate_name' => 'Anna Martin', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Otis Frieze',  'grade' => '4', 'score' => null]);

    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);

    // Public page (no auth needed)
    $this->get('/recognition')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('ThankYou')
            ->has('allQuartersData', fn ($d) => $d
                ->each(fn ($q) => $q->etc())));

    // Pull the prop tree directly to verify the structure
    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    expect($q1)->not->toBeNull();
    expect($q1['topScorers']['initial_5']['distinction'])->toHaveCount(1);
    expect($q1['topScorers']['initial_5']['distinction'][0]['name'])->toBe('Anna M');
    expect($q1['topScorersPublishedAt'])->not->toBeNull();
});

test('without a publication AND with pending results, public page shows no top scorers', function () {
    tspEntry(['candidate_name' => 'Anna Martin', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Otis Frieze', 'grade' => '4', 'score' => null]);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    expect($q1['topScorers']['initial_5']['distinction'])->toBe([]);
    expect($q1['topScorers']['6_8']['distinction'])->toBe([]);
});

test('without a publication, public top scorers stay hidden even with prize draw run AND no pending', function () {
    // Used to be a live-calc fallback path (publication absent + draw
    // run + no pending → surface winners). Removed 7 May 2026 — Paul
    // wanted publishing to be an explicit one-button decision per
    // quarter, not an implicit consequence of running a draw early.
    // This test guards against the live-calc fallback creeping back in.
    tspEntry(['candidate_name' => 'Anna Martin', 'grade' => '1', 'score' => 92, 'result' => 'Distinction']);
    tspEntry(['candidate_name' => 'Maya Parkinson', 'grade' => '1', 'score' => 83, 'result' => 'Merit']);

    PrizeDraw::create([
        'type' => 'student',
        'quarter' => 1,
        'year' => 2026,
        'winner_name' => 'Some Student',
        'total_tickets' => 10,
        'drawn_by' => $this->admin->id,
    ]);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    expect($q1['topScorers']['initial_5']['distinction'])->toBe([]);
    expect($q1['topScorers']['initial_5']['merit'])->toBe([]);
    expect($q1['topScorers']['6_8']['distinction'])->toBe([]);
    expect($q1['topScorers']['6_8']['merit'])->toBe([]);
});

test('snapshot respects show_full_name — public name stays shortened by default', function () {
    tspEntry([
        'candidate_name' => 'Anna Martin',
        'grade' => '1',
        'score' => 92,
        'result' => 'Distinction',
        'show_full_name' => false,
    ]);

    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);
    $winner = $q1['topScorers']['initial_5']['distinction'][0];

    expect($winner['name'])->toBe('Anna M');
    // full_name must NOT be in the public payload when show_full_name is false
    expect($winner)->not->toHaveKey('full_name');
});

test('snapshot with show_full_name=true reveals the full name to the public', function () {
    tspEntry([
        'candidate_name' => 'Anna Martin',
        'grade' => '1',
        'score' => 92,
        'result' => 'Distinction',
        'show_full_name' => true,
    ]);

    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);
    $winner = $q1['topScorers']['initial_5']['distinction'][0];

    expect($winner['name'])->toBe('Anna Martin');
});

test('a tied 2-way Initial-5 Merit publishes both winners', function () {
    tspEntry(['candidate_name' => 'Maya Parkinson',        'grade' => '1', 'score' => 83, 'result' => 'Merit']);
    tspEntry(['candidate_name' => 'Teddy Thompson-Davies', 'grade' => 'Initial', 'score' => 83, 'result' => 'Merit']);

    $this->actingAs($this->admin)->postJson('/admin/quarter-end/publish-top-scorers', ['quarter' => 1, 'year' => 2026]);

    $pub = TopScorerPublication::forQuarter(1, 2026);
    expect($pub->winners['initial_5']['merit'])->toHaveCount(2);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);
    expect($q1['topScorers']['initial_5']['merit'])->toHaveCount(2);
});
