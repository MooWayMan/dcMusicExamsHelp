<?php

// tests/Feature/PublicNameTest.php
//
// App\Support\PublicName is the one place a person's name is shortened to
// first name + initial (GDPR). There were six copies of that shortening
// until 27 Sep 2026. Keyed on the act, the upper-cased first letter of the
// surname, with comments stripped by guardSources() in tests/Pest.php.

use App\Support\PublicName;

test('a name is shortened to first name and surname initial', function () {
    expect(PublicName::short('Megan Roberts'))->toBe('Megan R')
        ->and(PublicName::short('Aria Maddison Chambers'))->toBe('Aria C')
        ->and(PublicName::short('  megan   roberts '))->toBe('megan R')
        ->and(PublicName::short('Cher'))->toBe('Cher')
        ->and(PublicName::short(null))->toBe('');
});

test('the name shortening exists once, in PublicName', function () {
    expect(guardOffenders('/mb_strtoupper\(\s*mb_substr\(/', ['app/Support/PublicName.php']))->toBe([]);
});
