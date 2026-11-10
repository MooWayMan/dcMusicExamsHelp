<?php

// tests/Feature/SpecialNeedsTest.php
//
// Trinity's special needs (SEN) process lives once, in
// resources/js/data/specialNeeds.ts. The Special needs page, its FAQ and the
// What to Expect page read it from there; teachers already mix the forms up,
// so two versions drifting apart is the thing to stop.

test('Trinity\'s special needs email and the three form links are written down once', function () {
    expect(guardOffenders('/music-csn@trinitycollege|resource\/\?id=1066[457]/', ['resources/js/data/specialNeeds.ts']))->toBe([]);
});

test('the special needs pages draw from the shared data', function () {
    $sources = guardSources();

    expect($sources['resources/js/pages/ExamGuideSpecialNeeds.vue'])->toContain('SEN_WHICH_FORM')
        ->and($sources['resources/js/pages/ExamGuideSpecialNeeds.vue'])->toContain('SEN_NOT_TO_CONFUSE')
        ->and($sources['resources/js/pages/ExamGuideSpecialNeeds.vue'])->toContain('faqAccordionItems(faqPage)')
        ->and($sources['resources/js/pages/ExamGuideExpect.vue'])->toContain('SEN_FACE_TO_FACE_DEADLINE')
        ->and($sources['resources/js/pages/ExamGuideExpect.vue'])->not->toContain('qualifications/music/special-needs');
});
