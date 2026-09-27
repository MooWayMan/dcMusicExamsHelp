<?php

// tests/Feature/EntryCertificatesTest.php
//
// An entry becomes its certificate in App\Services\EntryCertificates only,
// and "3rd Quarter 2026" is written by App\Support\QuarterLabel only. Both
// had copies until 27 Sep 2026 (two, and seven counting the browser's).
// The browser keeps one of its own, resources/js/lib/quarterLabel.ts. Keyed on the act, comments
// stripped by guardSources() in tests/Pest.php.

use App\Support\QuarterLabel;
use Carbon\Carbon;

test('an entry\'s certificate template is looked up in one place', function () {
    expect(guardOffenders('/STUDENT_TEMPLATES\[\s*\$entry->certificate_name/', ['app/Services/EntryCertificates.php']))->toBe([]);
});

test('the quarter label is written once', function () {
    expect(guardOffenders("/['\"]2nd['\"]/", ['app/Support/QuarterLabel.php', 'resources/js/lib/quarterLabel.ts']))->toBe([]);
});

test('the quarter label reads as it always has', function () {
    expect(QuarterLabel::for(3, 2026))->toBe('3rd Quarter 2026')
        ->and(QuarterLabel::forDate(Carbon::create(2026, 11, 2)))->toBe('4th Quarter 2026');
});
