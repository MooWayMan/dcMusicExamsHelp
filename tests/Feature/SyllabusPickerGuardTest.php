<?php

// tests/Feature/SyllabusPickerGuardTest.php
//
// Things the Piece tracker work made shared, each of which must exist once.
// Keyed on the act, with comments stripped by guardSources() in tests/Pest.php.

test('the grade order is written down once', function () {
    expect(guardOffenders("/'Initial',\s*'Grade 1',\s*'Grade 2'/", ['app/Services/SyllabusFacets.php']))->toBe([]);
});

test('the exam type → instrument → grade cascade lives in useSyllabusFacets only', function () {
    expect(guardOffenders('/streamInstruments\.filter\(/', ['resources/js/composables/useSyllabusFacets.ts']))->toBe([]);
});

test('no page builds its own syllabus dropdown', function () {
    expect(guardOffenders('/<select\b[^>]*v-model="(stream|instrument|grade)"/', []))->toBe([]);
});

test('pick-from-a-list search boxes are built by MySearchPickConstructor only', function () {
    expect(guardOffenders('/@mousedown\.prevent=/', ['resources/js/components/reusables/MySearchPickConstructor.vue']))->toBe([]);
});

test('which entries belong to a person is decided by TeacherEntries only', function () {
    expect(guardOffenders("/orWhereIn\('exam_entries\.applicant_email'|where\('exam_entries\.applicant_email',\s*\\\$user->email\)/", [
        'app/Services/TeacherEntries.php',
    ]))->toBe([]);
});

test('piece plans are read and written by PiecePlans only', function () {
    expect(guardOffenders('/PiecePlan(Item)?::(query|create|where|find)/', ['app/Services/PiecePlans.php']))->toBe([]);
});
