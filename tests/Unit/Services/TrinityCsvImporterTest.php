<?php

// tests/Unit/Services/TrinityCsvImporterTest.php

use App\Services\TrinityCsvImporter;

// ──────────────────────────────────────────────────────────────────
// Fixtures — Trinity CSV samples encoded as UTF-16 LE with BOM,
// matching the real exports Paul gets from MyTrinity.
// ──────────────────────────────────────────────────────────────────

/**
 * Build a UTF-16 LE BOM-prefixed CRLF CSV from a list of UTF-8 lines.
 */
function utf16Csv(array $lines): string
{
    $body = implode("\r\n", $lines) . "\r\n";
    return "\xFF\xFE" . mb_convert_encoding($body, 'UTF-16LE', 'UTF-8');
}

// ── decodeUtf16 ────────────────────────────────────────────────────

test('decodeUtf16 handles UTF-16 LE BOM input', function () {
    $raw = utf16Csv(['Name,Age', 'Alice,30']);
    $out = TrinityCsvImporter::decodeUtf16($raw);
    expect($out)->toContain('Name,Age')->toContain('Alice,30');
});

test('decodeUtf16 passes UTF-8 through unchanged (and strips BOM)', function () {
    $bom = "\xEF\xBB\xBF";
    $out = TrinityCsvImporter::decodeUtf16($bom . "hello");
    expect($out)->toBe('hello');
    expect(TrinityCsvImporter::decodeUtf16('plain'))->toBe('plain');
});

// ── parseGrade ─────────────────────────────────────────────────────

test('parseGrade parses Grade 2 (Digital)', function () {
    expect(TrinityCsvImporter::parseGrade('Classical and Jazz Technical Grade 2 (Digital)'))->toBe('2');
});

test('parseGrade parses Grade Initial', function () {
    expect(TrinityCsvImporter::parseGrade('Rock and Pop Grade Initial (Digital)'))->toBe('Initial');
});

test('parseGrade parses ATCL diploma', function () {
    expect(TrinityCsvImporter::parseGrade('Music Performers ATCL Diploma'))->toBe('ATCL');
    expect(TrinityCsvImporter::parseGrade('LTCL'))->toBe('LTCL');
});

test('parseGrade returns null when unrecognised', function () {
    expect(TrinityCsvImporter::parseGrade(''))->toBeNull();
    expect(TrinityCsvImporter::parseGrade('Random text'))->toBeNull();
});

// ── parseDeliveryMethod ────────────────────────────────────────────

test('parseDeliveryMethod recognises Digital / DigitalTheory / F2F', function () {
    expect(TrinityCsvImporter::parseDeliveryMethod('Grade 2 (Digital)'))->toBe('Digital');
    expect(TrinityCsvImporter::parseDeliveryMethod('Theory Grade 5 (Digital Theory)'))->toBe('DigitalTheory');
    expect(TrinityCsvImporter::parseDeliveryMethod('Grade 3 Music Performers'))->toBe('Default');
});

// ── parsePrice ────────────────────────────────────────────────────

test('parsePrice strips £ and parses', function () {
    expect(TrinityCsvImporter::parsePrice('£61.00'))->toBe(61.0);
});

test('parsePrice handles parens as negative (centre commission row)', function () {
    expect(TrinityCsvImporter::parsePrice('(£12.20)'))->toBe(-12.2);
});

test('parsePrice empty string is zero', function () {
    expect(TrinityCsvImporter::parsePrice(''))->toBe(0.0);
});

// ── instrumentMap ─────────────────────────────────────────────────

test('instrumentMap covers known Trinity names', function () {
    $map = TrinityCsvImporter::instrumentMap();
    expect($map)
        ->toHaveKey('Trumpet')
        ->toHaveKey('Trombone')
        ->toHaveKey('Cornet')
        ->toHaveKey('Tenor Horn')
        ->toHaveKey('Acoustic Guitar')
        ->toHaveKey('R&P Guitar')
        ->toHaveKey('R&P Vocals')
        ->toHaveKey('R&P Drums')
        ->toHaveKey('Drums')
        ->toHaveKey('Singing');
});

// ── parseMarksheet ────────────────────────────────────────────────

test('parseMarksheet sums the Mark column', function () {
    $csv = utf16Csv([
        'Section #,Mark,Section,Max',
        '1,18,Piece 1,',
        '2,17,Piece 2,',
        '3,18,Piece 3,',
        '4,11,Technical Work,',
        '5,7,Performance Delivery and Focus,',
        '6,7,Musical Awareness,',
    ]);
    expect((new TrinityCsvImporter())->parseMarksheet($csv))->toBe(78);
});

test('parseMarksheet treats blank Mark cells as zero', function () {
    $csv = utf16Csv([
        'Section #,Mark,Section,Max',
        '1,10,Piece 1,',
        '2,,Piece 2,',
        '3,15,Piece 3,',
    ]);
    expect((new TrinityCsvImporter())->parseMarksheet($csv))->toBe(25);
});

// ── parseEnrolment ────────────────────────────────────────────────

test('parseEnrolment skips the Centre Commission row and returns the candidate', function () {
    $csv = utf16Csv([
        'Examination,Subject,Candidate Number,Candidate Name,Enrolment Date,Price,Submitter Last Name,Submitter First Name,Submitter Email Address,Applicant Id,Applicant Last Name,Applicant First Name',
        'Classical and Jazz Technical Grade 2 (Digital),Trumpet,1-16043041094,Megan Roberts,08/04/2026 00:00:00,£61.00,Sheridan,Paul,madmusic6@hotmail.com,1-4781714763,Sheridan,Paul',
        'Centre Commission - Classical and Jazz (Digital),,, ,08/04/2026 14:58:53,(£12.20),,,,,,',
    ]);
    $row = (new TrinityCsvImporter())->parseEnrolment($csv);
    expect($row['candidate_number'])->toBe('1-16043041094');
    expect($row['candidate_name'])->toBe('Megan Roberts');
    expect($row['applicant_name'])->toBe('Paul Sheridan');
    expect($row['submitter_email'])->toBe('madmusic6@hotmail.com');
    expect($row['price'])->toBe(61.0);
});

// ── parseSummary ──────────────────────────────────────────────────

test('parseSummary returns the candidate row with order + result', function () {
    $csv = utf16Csv([
        'Subject Area,Syllabus,Examination Date,Examination,Candidate Number,Candidate,School,Teacher First Name,Teacher Last Name,Status,Result,Digital Certificate ID,Order Number,Examiner',
        'Music,Classical and Jazz (Digital),08/04/2026,Classical and Jazz Technical Grade 2 (Digital),1-16043041094,Megan Roberts,,,,Certificate Printed,Merit,19603896,1-16043046624,',
    ]);
    $row = (new TrinityCsvImporter())->parseSummary($csv);
    expect($row['candidate_number'])->toBe('1-16043041094');
    expect($row['order_number'])->toBe('1-16043046624');
    expect($row['result'])->toBe('Merit');
    expect($row['digital_certificate_id'])->toBe('19603896');
});

// ── Malformed CSV ─────────────────────────────────────────────────

test('extractRows throws when required headers are missing', function () {
    $csv = utf16Csv(['Foo,Bar', '1,2']);
    (new TrinityCsvImporter())->parseMarksheet($csv);
})->throws(RuntimeException::class, 'missing required columns');

// ── parseOrdersCsv ────────────────────────────────────────────────

test('parseOrdersCsv parses real bulk-orders sample', function () {
    $csv = utf16Csv([
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '05/05/2026 00:00:00,Digital,1-16786761424,Music,1,,Ready to Deliver',
        '10/07/2026 09:00:00,Default,1-16044465878,Music,11,Wirral School of Music,Processed',
    ]);
    $rows = (new TrinityCsvImporter())->parseOrdersCsv($csv);
    expect($rows)->toHaveCount(2);
    expect($rows[0]['order_number'])->toBe('1-16786761424');
    expect($rows[0]['delivery_method'])->toBe('Digital');
    expect($rows[0]['commission_rate'])->toBe(20.0);
    expect($rows[1]['order_number'])->toBe('1-16044465878');
    expect($rows[1]['delivery_method'])->toBe('Default');
    expect($rows[1]['commission_rate'])->toBe(10.0);
    expect($rows[1]['venue'])->toBe('Wirral School of Music');
    expect($rows[1]['candidates'])->toBe(11);
});
