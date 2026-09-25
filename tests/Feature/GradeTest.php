<?php

// tests/Feature/GradeTest.php
//
// Grades are stored as "Grade 4", "4" or "Initial" depending on the importer.
// App\Support\Grade and resources/js/lib/grades.ts are the only places that
// turn one into a label, so a certificate or email never says "Grade Grade 4".

use App\Support\Grade;
use App\Support\TopScorers;

test('every stored form of a grade prints the same label', function (?string $stored, string $label) {
    expect(Grade::label($stored))->toBe($label);
})->with([
    ['Grade 4', 'Grade 4'],
    ['4', 'Grade 4'],
    ['grade 4', 'Grade 4'],
    [' Grade 8 ', 'Grade 8'],
    ['Initial', 'Initial'],
    ['Grade Initial', 'Initial'],
    ['initial', 'Initial'],
    ['', ''],
    [null, ''],
]);

test('top scorer groups read the grade through the same helper', function () {
    expect(TopScorers::groupOf('Grade 8'))->toBe('6_8')
        ->and(TopScorers::groupOf('8'))->toBe('6_8')
        ->and(TopScorers::groupOf('Grade Initial'))->toBe('initial_5')
        ->and(TopScorers::groupOf('Grade 3'))->toBe('initial_5');
});

test('no code prefixes a stored grade with "Grade" by hand', function () {
    expect(guardOffenders('/Grade \{\$/', ['app/Support/Grade.php']))->toBe([])
        ->and(guardOffenders('/Grade \$\{/', ['resources/js/lib/grades.ts']))->toBe([]);
});

test('the grade label is written once for the front end', function () {
    expect(guardOffenders('/(const|function)\s+formatGrade\b/', ['resources/js/lib/grades.ts']))->toBe([]);
});
