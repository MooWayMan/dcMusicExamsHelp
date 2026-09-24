<?php

// tests/Feature/QuarterEndEmailsGuardTest.php
//
// The Quarter End results emails (teacher, parent, self-applicant) are built
// in resources/js/lib/quarterEndEmails.ts, with the "Sending late" option.
// They used to be inline in the 1,900-line Quarter End page.

test('the results emails are written in one place', function () {
    expect(guardOffenders('/Your Students Did Brilliantly/', ['resources/js/lib/quarterEndEmails.ts']))->toBe([]);
});

test('the greeting name is worked out by one function', function () {
    expect(guardOffenders('/function\s+recipientGreetingName\s*\(/', ['resources/js/lib/quarterEndEmails.ts']))->toBe([]);
});

test('the late apology is available in all three results emails', function () {
    $code = guardSources()['resources/js/lib/quarterEndEmails.ts'];

    expect(substr_count($code, "I'm sorry this is late"))->toBe(3)
        ->and($code)->toContain("(sorry it's late!)")
        ->not->toContain('musicexams.help/register to check if you won')
        ->not->toContain("I've moved to a new email address");
});
