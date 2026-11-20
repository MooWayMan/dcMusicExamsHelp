<?php

// tests/Feature/PhotoSectionGuardTest.php
//
// The darkened-photo section lives once, in MyPhotoSection. It was copied by
// hand into the public pages before the constructor existed; the pages below
// still carry those copies. The list may only get SHORTER: a page that moves
// onto MyPhotoSection comes off it, and no new page may add a copy.

const PHOTO_SECTION_BASELINE = [
    'resources/js/pages/ExamGuide.vue',
    'resources/js/pages/ExamGuideDigital.vue',
    'resources/js/pages/ExamGuideExpect.vue',
    'resources/js/pages/ExamGuideGrades.vue',
    'resources/js/pages/ExamGuideOwnChoice.vue',
    'resources/js/pages/ExamGuideSpecialNeeds.vue',
    'resources/js/pages/ExamGuideSyllabuses.vue',
    'resources/js/pages/ExamGuideUcas.vue',
    'resources/js/pages/TeacherAwards.vue',
    'resources/js/pages/ThankYou.vue',
    'resources/js/pages/Welcome.vue',
];

function photoSectionCopies(): array
{
    $copies = guardOffenders('#absolute inset-0 bg-black/50#', ['resources/js/components/reusables/MyPhotoSection.vue']);
    sort($copies);

    return $copies;
}

test('no new page copies the photo section by hand', function () {
    expect(array_values(array_diff(photoSectionCopies(), PHOTO_SECTION_BASELINE)))->toBe([]);
});

test('a page that moved onto MyPhotoSection comes off the baseline', function () {
    expect(array_values(array_diff(PHOTO_SECTION_BASELINE, photoSectionCopies())))->toBe([]);
});
