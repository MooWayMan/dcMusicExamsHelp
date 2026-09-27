<?php

// tests/Feature/EntryCreditGuardTest.php
//
// Who an entry is credited to (school, teacher, or the parent who submitted
// it) is decided in App\Services\EntryCredit and nowhere else. Three copies
// drifted before 27 Sep 2026. Keyed on the act: naming the unassigned
// bucket, and building the school-admin rollup. Comments are stripped by
// guardSources() in tests/Pest.php.

test('the unassigned group is named once, in EntryCredit', function () {
    expect(guardOffenders('/Parent Bookings \(no teacher assigned\)/', ['app/Services/EntryCredit.php']))->toBe([]);
});

test('the school-admin rollup is built once, in EntryCredit', function () {
    expect(guardOffenders("/withType\\('school_admin'\\)\\s*->with\\(\\['schools:/", ['app/Services/EntryCredit.php']))->toBe([]);
});
