<?php

// tests/Unit/Services/ResultsScanParserTest.php

use App\Services\ResultsScanParser;

// The parser is pure (no DB, no PDF), so these run without RefreshDatabase.

function scanParser(): ResultsScanParser
{
    return new ResultsScanParser();
}

/** A clean, correctly-added C&J candidate. */
function goodCandidate(array $overrides = []): array
{
    return array_merge([
        'subject' => 'Piano',
        'grade' => 'Grade 2',
        'candidate_name' => 'Chloe Roberts',
        'candidate_id' => '1823317',
        'order_number' => '1-16044465651',
        'exam_date' => '2026-07-09',
        'sections' => [
            ['label' => 'Piece 1', 'mark' => 19, 'max' => 22],
            ['label' => 'Piece 2', 'mark' => 15, 'max' => 22],
            ['label' => 'Piece 3', 'mark' => 15, 'max' => 22],
            ['label' => 'Technical Work', 'mark' => 10, 'max' => 14],
            ['label' => 'Test 1', 'mark' => 6, 'max' => 10],
            ['label' => 'Test 2', 'mark' => 8, 'max' => 10],
        ],
        'examiner_total' => 73,
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────────
// Exam family + instrument mapping (the C&J vs R&P disambiguation)
// ──────────────────────────────────────────────────────────────────

test('detects the exam family from the subject', function () {
    expect(ResultsScanParser::detectFamily('Piano'))->toBe('C&J');
    expect(ResultsScanParser::detectFamily('Singing'))->toBe('C&J');
    expect(ResultsScanParser::detectFamily('Clarinet'))->toBe('C&J');
    expect(ResultsScanParser::detectFamily('Rock & Pop Guitar'))->toBe('R&P');
    expect(ResultsScanParser::detectFamily('Rock and Pop Vocals'))->toBe('R&P');
});

test('maps instruments family-aware so singing and guitar resolve correctly', function () {
    // Singing splits by family.
    expect(ResultsScanParser::mapInstrument('Singing', 'C&J'))->toBe('Singing (Classical)');
    expect(ResultsScanParser::mapInstrument('Rock & Pop Vocals', 'R&P'))->toBe('Singing (Rock/Pop)');

    // Guitar splits by family.
    expect(ResultsScanParser::mapInstrument('Guitar', 'C&J'))->toBe('Guitar (Classical)');
    expect(ResultsScanParser::mapInstrument('Rock & Pop Guitar', 'R&P'))->toBe('Guitar (Rock/Pop)');

    // Unambiguous instruments fall through the shared Trinity map.
    expect(ResultsScanParser::mapInstrument('Piano', 'C&J'))->toBe('Piano');
    expect(ResultsScanParser::mapInstrument('Rock & Pop Drums', 'R&P'))->toBe('Drum Kit');
});

// ──────────────────────────────────────────────────────────────────
// The core checks
// ──────────────────────────────────────────────────────────────────

test('a correctly-added candidate passes with no flags', function () {
    $c = scanParser()->checkCandidate(goodCandidate());

    expect($c['section_sum'])->toBe(73);
    expect($c['examiner_total'])->toBe(73);
    expect($c['verified_total'])->toBe(73);
    expect($c['flags'])->toBe([]);
    expect($c['checks_pass'])->toBeTrue();
    expect($c['band'])->toBe('Pass');
    expect($c['family'])->toBe('C&J');
    expect($c['grade'])->toBe('2');
    expect($c['instrument'])->toBe('Piano');
});

test('flags an examiner addition error (sum != written total)', function () {
    // Sections still add to 73, but the examiner wrote 75.
    $c = scanParser()->checkCandidate(goodCandidate(['examiner_total' => 75]));

    expect($c['section_sum'])->toBe(73);
    expect($c['checks_pass'])->toBeFalse();
    expect($c['flags'])->toHaveCount(1);
    expect($c['flags'][0])->toContain('73')->toContain('75');
    // The section sum is the computed truth when they disagree.
    expect($c['verified_total'])->toBe(73);
});

test('flags a mark that exceeds its section maximum', function () {
    $sections = goodCandidate()['sections'];
    $sections[0]['mark'] = 28; // out of 22
    $c = scanParser()->checkCandidate(goodCandidate([
        'sections' => $sections,
        'examiner_total' => 82,
    ]));

    expect(collect($c['flags'])->filter(fn ($f) => str_contains($f, 'out of 22')))->not->toBeEmpty();
});

test('flags an unreadable (null) mark', function () {
    $sections = goodCandidate()['sections'];
    $sections[4]['mark'] = null; // Test 1 couldn't be read
    $c = scanParser()->checkCandidate(goodCandidate(['sections' => $sections]));

    expect(collect($c['flags'])->filter(fn ($f) => str_contains($f, "Couldn't read")))->not->toBeEmpty();
    expect($c['checks_pass'])->toBeFalse();
});

test('uses a third total from Trinity as an extra cross-check', function () {
    $c = scanParser()->checkCandidate(goodCandidate(['tol_total' => 70]));

    expect(collect($c['flags'])->filter(fn ($f) => str_contains($f, 'Trinity')))->not->toBeEmpty();
});

test('carries the piece name and examiner comment through untouched', function () {
    $sections = goodCandidate()['sections'];
    $sections[0]['comment'] = 'Neat and stylish throughout.';
    $c = scanParser()->checkCandidate(goodCandidate([
        'sections' => $sections,
        'general_comments' => 'A confident exam.',
    ]));

    expect($c['sections'][0]['label'])->toBe('Piece 1');
    expect($c['sections'][0]['comment'])->toBe('Neat and stylish throughout.');
    expect($c['general_comments'])->toBe('A confident exam.');
});

test('bands follow the Trinity thresholds', function () {
    expect(ResultsScanParser::band(59))->toBe('Below Pass');
    expect(ResultsScanParser::band(60))->toBe('Pass');
    expect(ResultsScanParser::band(74))->toBe('Pass');
    expect(ResultsScanParser::band(75))->toBe('Merit');
    expect(ResultsScanParser::band(86))->toBe('Merit');
    expect(ResultsScanParser::band(87))->toBe('Distinction');
});

// ──────────────────────────────────────────────────────────────────
// Integration — the real transcribed sample batch
// ──────────────────────────────────────────────────────────────────

test('the transcribed sample batch is fully consistent', function () {
    $json = file_get_contents(dirname(__DIR__, 2).'/fixtures/results-scan/sample-batch.json');
    $checked = scanParser()->parse(json_decode($json, true));

    expect($checked)->toHaveCount(6);

    // Every examiner in the sample added up correctly, so nothing is flagged.
    foreach ($checked as $c) {
        expect($c['flags'])->toBe([]);
        expect($c['checks_pass'])->toBeTrue();
        expect($c['section_sum'])->toBe($c['examiner_total']);
    }

    // Spot-check the singing candidate maps to Classical and bands as Merit,
    // and that its piece names + examiner comments survived.
    $maya = collect($checked)->firstWhere('candidate_name', 'Maya Ghali');
    expect($maya['instrument'])->toBe('Singing (Classical)');
    expect($maya['band'])->toBe('Merit');
    expect($maya['sections'][0]['label'])->toBe('Pigs Could Fly');
    expect($maya['sections'][0]['comment'])->toContain('diction');

    // And the R&P guitarist is read as R&P with the right instrument.
    $kelson = collect($checked)->firstWhere('candidate_name', 'Kelson Kiafuca');
    expect($kelson['family'])->toBe('R&P');
    expect($kelson['instrument'])->toBe('Guitar (Rock/Pop)');
    expect($kelson['section_sum'])->toBe(67);
});
