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
    expect(guardOffenders('/PiecePlan(Item|Rating)?::(query|create|where|find)/', ['app/Services/PiecePlans.php']))->toBe([]);
});

// Tick boxes: MyCheckboxConstructor, added 26 Sep 2026. These files drew
// their own before it existed. The list may only get shorter: moving one
// onto the constructor means deleting its line here.
const RAW_CHECKBOX_BASELINE = [
    'resources/js/components/LeadMagnetCapture.vue',
    'resources/js/components/PrizeWorkflowChecks.vue',
    'resources/js/pages/admin/Contacts/Edit.vue',
    'resources/js/pages/admin/ExamEntries/Index.vue',
    'resources/js/pages/admin/Imports/Index.vue',
    'resources/js/pages/auth/Register.vue',
    'resources/js/pages/settings/Profile.vue',
];

test('no new page draws its own tick box', function () {
    $offenders = guardOffenders('/type="checkbox"/', ['resources/js/components/reusables/MyCheckboxConstructor.vue']);

    expect(array_values(array_diff($offenders, RAW_CHECKBOX_BASELINE)))->toBe([]);
});

test('the tick-box baseline only lists files that still need moving', function () {
    $offenders = guardOffenders('/type="checkbox"/', ['resources/js/components/reusables/MyCheckboxConstructor.vue']);

    expect(array_values(array_diff(RAW_CHECKBOX_BASELINE, $offenders)))->toBe([]);
});
