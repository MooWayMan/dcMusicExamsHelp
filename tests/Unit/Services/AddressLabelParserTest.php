<?php

// tests/Unit/Services/AddressLabelParserTest.php

use App\Services\AddressLabelParser;

// ──────────────────────────────────────────────────────────────────
// clean() — the per-address tidy-up. Pure, no PDF needed. Every case
// below is a real junk pattern taken from Paul's Trinity sample PDFs.
// ──────────────────────────────────────────────────────────────────

test('clean strips the trailing United Kingdom line', function () {
    $out = (new AddressLabelParser())->clean([
        'Philip Goodwin', '19 Lyttelton Road', 'Liverpool', 'L17 0AS', 'United Kingdom',
    ]);

    expect($out)->toBe(['Philip Goodwin', '19 Lyttelton Road', 'Liverpool', 'L17 0AS'])
        ->not->toContain('United Kingdom');
});

test('clean collapses a duplicated town/county line', function () {
    // Trinity repeats the town as the county: "Liverpool" then "Liverpool".
    $out = (new AddressLabelParser())->clean([
        'Nina Cox', '56 Queens Drive', 'Mossley Hill', 'Liverpool', 'Liverpool', 'L18 0HF', 'United Kingdom',
    ]);

    expect($out)->toBe(['Nina Cox', '56 Queens Drive', 'Mossley Hill', 'Liverpool', 'L18 0HF']);
});

test('clean removes a repeated street line', function () {
    // Anthony Bearon has "186 Prescot Road" printed twice.
    $out = (new AddressLabelParser())->clean([
        'Anthony John Bearon', '186 Prescot Road', '186 Prescot Road', 'Aughton', 'L39 5AG', 'United Kingdom',
    ]);

    expect($out)->toBe(['Anthony John Bearon', '186 Prescot Road', 'Aughton', 'L39 5AG']);
});

test('clean splits a combined line and drops the split repeat', function () {
    // "Willow Cottage, 4 The Ridgeway" is split, and Trinity's separate
    // "4 The Ridgeway" line collapses into it — one part per line.
    $out = (new AddressLabelParser())->clean([
        'Jennifer KENT', 'Willow Cottage, 4 The Ridgeway', '4 The Ridgeway', 'Heswall', 'Wirral', 'CH60 8NB', 'United Kingdom',
    ]);

    expect($out)->toBe(['Jennifer KENT', 'Willow Cottage', '4 The Ridgeway', 'Heswall', 'Wirral', 'CH60 8NB']);
});

test('clean breaks up a long multi-part combined line so it fits a label', function () {
    // "Woodhollow, 7 Low Wood Grove, Wirral" → one part per line; Trinity's
    // separate split lines collapse in.
    $out = (new AddressLabelParser())->clean([
        'Kaan Aslan Yalcinkaya', 'Woodhollow, 7 Low Wood Grove, Wirral', 'Woodhollow', '7 Low Wood Grove', 'Wirral', 'CH61 1AN', 'United Kingdom',
    ]);

    expect($out)->toBe(['Kaan Aslan Yalcinkaya', 'Woodhollow', '7 Low Wood Grove', 'Wirral', 'CH61 1AN']);
});

// ──────────────────────────────────────────────────────────────────
// postcode()
// ──────────────────────────────────────────────────────────────────

test('postcode extracts and normalises a UK postcode', function () {
    expect((new AddressLabelParser())->postcode(['Someone', '1 A Road', 'Town', 'L18 4QZ']))->toBe('L184QZ');
});

test('postcode reads a postcode printed without a space', function () {
    // rp_am has "L255HP".
    expect((new AddressLabelParser())->postcode(['CHRISTOPER JONES', '21 Monks Way', 'L255HP']))->toBe('L255HP');
});

// ──────────────────────────────────────────────────────────────────
// dedupe() — cross-file merge. Exact dupes vanish, near dupes are
// KEPT and flagged for Paul to eyeball.
// ──────────────────────────────────────────────────────────────────

function lbl(string $name, array $lines, string $source): array
{
    $p = new AddressLabelParser();

    return ['name' => $name, 'lines' => $lines, 'postcode' => $p->postcode($lines), 'source' => $source, 'flag' => ''];
}

test('dedupe drops an exact duplicate silently', function () {
    $a = lbl('Jennifer KENT', ['Jennifer KENT', 'Willow Cottage, 4 The Ridgeway', 'Heswall', 'Wirral', 'CH60 8NB'], 'day1.pdf');
    $b = lbl('Jennifer KENT', ['Jennifer KENT', 'Willow Cottage, 4 The Ridgeway', 'Heswall', 'Wirral', 'CH60 8NB'], 'day2.pdf');

    $out = (new AddressLabelParser())->dedupe([$a, $b]);

    expect($out)->toHaveCount(1);
    expect($out[0]['flag'])->toBe('');
});

test('dedupe keeps but flags a near-duplicate with a typo name', function () {
    $a = lbl('Christopher Jones', ['Christopher Jones', '21 Monks Way', 'Liverpool', 'L25 5HP'], 'cj.pdf');
    $b = lbl('CHRISTOPER JONES', ['CHRISTOPER JONES', '21 Monks Way', 'Woolton', 'L255HP'], 'rp.pdf');

    $out = (new AddressLabelParser())->dedupe([$a, $b]);

    expect($out)->toHaveCount(2);
    expect($out[1]['flag'])->toContain('Christopher Jones');
    // Both share a group key so the grid can highlight them together.
    expect($out[0]['dupeKey'])->not->toBe('');
    expect($out[1]['dupeKey'])->toBe($out[0]['dupeKey']);
});

test('dedupe flags an inserted middle name as a possible duplicate', function () {
    $a = lbl('Roxanne Twomey', ['Roxanne Twomey', '24 Hornspit Lane', 'Liverpool', 'L12 5LT'], 'am.pdf');
    $b = lbl('Roxanne Kathleen Twomey', ['Roxanne Kathleen Twomey', 'School Of Rox', 'Liverpool', 'L12 7LG'], 'pm.pdf');

    $out = (new AddressLabelParser())->dedupe([$a, $b]);

    expect($out)->toHaveCount(2);
    expect($out[1]['flag'])->toContain('Roxanne Twomey');
});

test('dedupe leaves genuinely different people untouched', function () {
    $a = lbl('Nina Cox', ['Nina Cox', '56 Queens Drive', 'L18 0HF'], 'x.pdf');
    $b = lbl('Fiona Shore', ['Fiona Shore', 'Near Howe', 'CA11 0SH'], 'x.pdf');

    $out = (new AddressLabelParser())->dedupe([$a, $b]);

    expect($out)->toHaveCount(2);
    expect($out[0]['flag'])->toBe('');
    expect($out[1]['flag'])->toBe('');
});

// ──────────────────────────────────────────────────────────────────
// parseCsv()
// ──────────────────────────────────────────────────────────────────

test('parseCsv turns one row into one label, cleaning as it goes', function () {
    $csv = "Philip Goodwin,19 Lyttelton Road,Liverpool,L17 0AS,United Kingdom\n"
        . "Fiona Shore,Near Howe,Troutbeck,CA11 0SH";

    $out = (new AddressLabelParser())->parseCsv($csv, 'my.csv');

    expect($out)->toHaveCount(2);
    expect($out[0]['lines'])->toBe(['Philip Goodwin', '19 Lyttelton Road', 'Liverpool', 'L17 0AS']);
    expect($out[0]['source'])->toBe('my.csv');
});
