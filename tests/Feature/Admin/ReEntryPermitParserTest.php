<?php

// tests/Feature/Admin/ReEntryPermitParserTest.php
//
// Parsing a Trinity Re-entry Permit.
//
// ⚠️ The text below is NOT prettied up. It is byte-for-byte what
// smalot/pdfparser returns for Sam Dobie's real permit (Rock and Pop Guitar
// Grade 4, withdrawn, voucher issued 14 July 2026) — captured by running the
// parser over the actual PDF on 3 Aug 2026.
//
// This matters. The permit is laid out by absolute position, so Trinity emits
// BOTH candidate labels before EITHER value:
//
//     Candidate Name:
//     Candidate Id:
//     Sam Dobie
//     1-17563392237
//
// The first version of this test used `pdftotext -layout` style aligned
// columns, which look like what a human sees but never reach the parser in
// production. It passed while all three real permits were rejected as
// "not a permit". Do not tidy this fixture.

use App\Services\ReEntryPermitParser;

function permitText(array $overrides = []): string
{
    $f = array_merge([
        'issued' => '14/07/2026',
        'name' => 'Sam Dobie',
        'id' => '1-17563392237',
        'subject' => 'Rock and Pop',
        'exam' => 'Rock and Pop Guitar Grade 4',
        'until' => '14/07/2027',
        'status' => 'Valid',
        'code' => '1-18154879067',
    ], $overrides);

    return implode("\n", [
        'Re-entry Permit',
        "Date of issue:  {$f['issued']}",
        'Candidate Name:',
        'Candidate Id: ',
        $f['name'],
        $f['id'],
        "Subject:                {$f['subject']}",
        "Exam:\t{$f['exam']}",
        "Valid Until:            {$f['until']}",
        "Status:                   {$f['status']}",
        'This candidate is permitted to re-enter for the examination until the "Valid Until" date shown above.',
        "Code: {$f['code']}",
        'Credit Discount: 100%',
        'This form can be used online (UK Music Only) using the code provided or sent to the centre at which re-entry is made, with a ',
        'completed entry form and the fee (as appropriate).',
        'Issued by Central Operations',
    ]);
}

test('every field is pulled off a real permit', function () {
    $p = (new ReEntryPermitParser())->parseText(permitText());

    expect($p['is_permit'])->toBeTrue()
        ->and($p['candidate_name'])->toBe('Sam Dobie')
        ->and($p['candidate_number'])->toBe('1-17563392237')
        ->and($p['subject'])->toBe('Rock and Pop')
        ->and($p['exam'])->toBe('Rock and Pop Guitar Grade 4')
        ->and($p['code'])->toBe('1-18154879067')
        ->and($p['status'])->toBe('Valid')
        ->and($p['issued_at'])->toBe('2026-07-14')
        ->and($p['valid_until'])->toBe('2027-07-14')
        ->and($p['credit_discount'])->toBe('100%');
});

test('a run of bare labels is paired with the run of values that follows', function () {
    // The whole bug in one assertion. Read in stream order, the line after
    // "Candidate Name:" is "Candidate Id: ", not the name — so a
    // label-adjacent regex hands back a label as if it were a value.
    $p = (new ReEntryPermitParser())->parseText(permitText());

    expect($p['candidate_name'])->not->toContain('Candidate')
        ->and($p['candidate_name'])->not->toContain(':')
        ->and($p['candidate_number'])->not->toContain('Candidate');
});

test('the voucher code is never confused with the candidate id', function () {
    // Both are 1-XXXXXXXXX. Taking the first match in the document would
    // stamp the candidate's own number on as their voucher code.
    $p = (new ReEntryPermitParser())->parseText(permitText([
        'id' => '1-17572882641',
        'code' => '1-18155075732',
        'name' => 'Keane Branch-Curtis',
        'exam' => 'Rock and Pop Guitar Grade 1',
    ]));

    expect($p['candidate_number'])->toBe('1-17572882641')
        ->and($p['code'])->toBe('1-18155075732')
        ->and($p['candidate_name'])->toBe('Keane Branch-Curtis')
        ->and($p['candidate_number'])->not->toBe($p['code']);
});

test('a PDF that is not a permit is rejected rather than half-read', function () {
    $p = (new ReEntryPermitParser())->parseText('Remittance Advice
        6 December 2025  1-6053029943  CET000447  Liverpool  GBP 124.25 124.25');

    expect($p['is_permit'])->toBeFalse()
        ->and($p['code'])->toBeNull();
});

test('a permit with an unreadable date does not blow up', function () {
    $p = (new ReEntryPermitParser())->parseText(permitText(['issued' => 'not a date']));

    expect($p['issued_at'])->toBeNull()
        ->and($p['candidate_number'])->toBe('1-17563392237');
});
