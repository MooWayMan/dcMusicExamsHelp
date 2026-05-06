<?php

// tests/Unit/Support/TopScorersTest.php
//
// Pure-PHP unit tests for the TopScorers helper. No DB, no HTTP — just the
// classification + bucketing rules used by both QuarterEnd and the cert
// generator. Locking these down means the two consumers stay in lockstep.

use App\Support\TopScorers;
use Illuminate\Support\Collection;

// Minimal stub mirroring the fields TopScorers reads on an ExamEntry. Using
// a stdClass keeps these tests fast and DB-free; the real ExamEntry model is
// covered by the feature tests.
function ts_entry(array $fields): object
{
    return (object) array_merge([
        'grade' => '1',
        'score' => 80,
    ], $fields);
}

// ── groupOf() ────────────────────────────────────────────────────────────

test('Initial maps to initial_5', function () {
    expect(TopScorers::groupOf('Initial'))->toBe('initial_5');
});

test('grades 1 through 5 map to initial_5', function () {
    foreach (['1', '2', '3', '4', '5'] as $g) {
        expect(TopScorers::groupOf($g))->toBe('initial_5');
    }
});

test('grades 6 through 8 map to 6_8', function () {
    foreach (['6', '7', '8'] as $g) {
        expect(TopScorers::groupOf($g))->toBe('6_8');
    }
});

test('null and unknown grades map to null (excluded from awards)', function () {
    expect(TopScorers::groupOf(null))->toBeNull();
    expect(TopScorers::groupOf('Diploma'))->toBeNull();
    expect(TopScorers::groupOf('FTCL'))->toBeNull();
    expect(TopScorers::groupOf('9'))->toBeNull();
});

test('prefixed "Grade X" format classifies the same as bare "X"', function () {
    // Production data uses "Grade 1" through "Grade 8" — must classify
    // identically to the bare-number form used in seeds and tests.
    expect(TopScorers::groupOf('Grade 1'))->toBe('initial_5');
    expect(TopScorers::groupOf('Grade 5'))->toBe('initial_5');
    expect(TopScorers::groupOf('Grade 6'))->toBe('6_8');
    expect(TopScorers::groupOf('Grade 8'))->toBe('6_8');
    // Case-insensitive prefix
    expect(TopScorers::groupOf('grade 7'))->toBe('6_8');
    expect(TopScorers::groupOf('GRADE 2'))->toBe('initial_5');
    // Whitespace tolerance
    expect(TopScorers::groupOf(' Grade 4 '))->toBe('initial_5');
});

// ── bandOf() ─────────────────────────────────────────────────────────────

test('scores 87+ are Distinction', function () {
    expect(TopScorers::bandOf(87))->toBe('distinction');
    expect(TopScorers::bandOf(99))->toBe('distinction');
});

test('scores 75 to 86 are Merit', function () {
    expect(TopScorers::bandOf(75))->toBe('merit');
    expect(TopScorers::bandOf(86))->toBe('merit');
    expect(TopScorers::bandOf(80))->toBe('merit');
});

test('scores below 75 are no award (Pass / fail)', function () {
    expect(TopScorers::bandOf(74))->toBeNull();
    expect(TopScorers::bandOf(0))->toBeNull();
});

test('null score returns null band', function () {
    expect(TopScorers::bandOf(null))->toBeNull();
});

// ── tokenSplit() ─────────────────────────────────────────────────────────

test('one winner takes the full £20', function () {
    expect(TopScorers::tokenSplit(1))->toBe(20);
});

test('two-way tie splits to £10 each', function () {
    expect(TopScorers::tokenSplit(2))->toBe(10);
});

test('three or more winners get £5 each (minimum £5 rule)', function () {
    expect(TopScorers::tokenSplit(3))->toBe(5);
    expect(TopScorers::tokenSplit(4))->toBe(5);
    expect(TopScorers::tokenSplit(10))->toBe(5);
});

test('zero or negative tie counts return zero', function () {
    expect(TopScorers::tokenSplit(0))->toBe(0);
    expect(TopScorers::tokenSplit(-1))->toBe(0);
});

// ── calculate() ──────────────────────────────────────────────────────────

test('empty entries returns empty buckets', function () {
    $result = TopScorers::calculate(collect(), fn ($e) => $e);

    expect($result['initial_5']['distinction'])->toBe([]);
    expect($result['initial_5']['merit'])->toBe([]);
    expect($result['6_8']['distinction'])->toBe([]);
    expect($result['6_8']['merit'])->toBe([]);
});

test('a sole Distinction in Initial-5 surfaces as the only winner', function () {
    $entries = collect([
        ts_entry(['grade' => '3', 'score' => 92]),
        ts_entry(['grade' => '3', 'score' => 80]), // Merit, lower bucket
    ]);

    $result = TopScorers::calculate($entries, fn ($e) => ['score' => $e->score]);

    expect($result['initial_5']['distinction'])->toHaveCount(1);
    expect($result['initial_5']['distinction'][0]['score'])->toBe(92);
});

test('ties at the top score keep all tied winners', function () {
    $entries = collect([
        ts_entry(['grade' => '2', 'score' => 90]),
        ts_entry(['grade' => '2', 'score' => 90]),
        ts_entry(['grade' => '2', 'score' => 88]),  // dropped
    ]);

    $result = TopScorers::calculate($entries, fn ($e) => ['score' => $e->score]);

    expect($result['initial_5']['distinction'])->toHaveCount(2);
});

test('groups are independent — a higher 6-8 doesnt touch the Initial-5 winner', function () {
    $entries = collect([
        ts_entry(['grade' => '3', 'score' => 88]),  // Initial-5 Distinction
        ts_entry(['grade' => '8', 'score' => 95]),  // 6-8 Distinction
    ]);

    $result = TopScorers::calculate($entries, fn ($e) => ['score' => $e->score, 'grade' => $e->grade]);

    expect($result['initial_5']['distinction'])->toHaveCount(1);
    expect($result['initial_5']['distinction'][0]['score'])->toBe(88);
    expect($result['6_8']['distinction'])->toHaveCount(1);
    expect($result['6_8']['distinction'][0]['score'])->toBe(95);
});

test('Pass-band scores never reach a winner bucket', function () {
    $entries = collect([
        ts_entry(['grade' => '4', 'score' => 70]),  // Pass — ignored
        ts_entry(['grade' => '4', 'score' => 65]),  // Pass — ignored
    ]);

    $result = TopScorers::calculate($entries, fn ($e) => ['score' => $e->score]);

    expect($result['initial_5']['merit'])->toBe([]);
    expect($result['initial_5']['distinction'])->toBe([]);
});

// ── flatten() ────────────────────────────────────────────────────────────

test('flatten produces one row per winner with correct certificate label', function () {
    $structured = [
        'initial_5' => [
            'distinction' => [['name' => 'A']],
            'merit'       => [['name' => 'B'], ['name' => 'C']], // tie
        ],
        '6_8' => [
            'distinction' => [['name' => 'D']],
            'merit'       => [],
        ],
    ];

    $flat = TopScorers::flatten($structured);

    expect($flat)->toHaveCount(4);
    // Distinctions → Showstopper, Merits → Centre Stage
    $a = collect($flat)->firstWhere('winner.name', 'A');
    expect($a['certificate'])->toBe('Showstopper');
    expect($a['group'])->toBe('initial_5');
    expect($a['band'])->toBe('distinction');

    $b = collect($flat)->firstWhere('winner.name', 'B');
    expect($b['certificate'])->toBe('Centre Stage');

    $d = collect($flat)->firstWhere('winner.name', 'D');
    expect($d['certificate'])->toBe('Showstopper');
    expect($d['group'])->toBe('6_8');
});

test('flatten of empty structure returns empty array', function () {
    $structured = [
        'initial_5' => ['distinction' => [], 'merit' => []],
        '6_8'       => ['distinction' => [], 'merit' => []],
    ];

    expect(TopScorers::flatten($structured))->toBe([]);
});
