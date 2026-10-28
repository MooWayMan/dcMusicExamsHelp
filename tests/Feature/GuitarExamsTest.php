<?php

// tests/Feature/GuitarExamsTest.php
//
// Trinity has three guitar exams on two booking systems. The difference, the
// Rock & Pop instrument rule and the three syllabus links live once, in
// lib/guitarExams.ts, and the Syllabuses page, the FAQ and site search read
// them from there. That every FAQ link opens the right answer is now proved
// by tests/js/search.test.ts (SearchBehaviourTest).

test('the Rock & Pop guitar instrument rule is written down once', function () {
    expect(guardOffenders('/From Grade 3 it must be electric/', ['resources/js/lib/guitarExams.ts']))->toBe([]);
});

test('the three guitar syllabus links are written down once', function () {
    $pattern = '#trinitycollege\.com/qualifications/music/(grade-exams/(classical|acoustic)-guitar|rock-and-pop/grades)#';

    expect(guardOffenders($pattern, ['resources/js/lib/guitarExams.ts']))->toBe([]);
});

test('the Syllabuses page, the FAQ and search all use the shared guitar exams', function () {
    $sources = guardSources();
    $syllabuses = $sources['resources/js/pages/ExamGuideSyllabuses.vue'];

    expect($syllabuses)->toContain('GUITAR_EXAMS.map(')
        ->and($syllabuses)->toContain('id="guitar"')
        ->and($syllabuses)->toContain('<MyGlassCardConstructor :cards="guitarCards"')
        ->and($sources['resources/js/data/faqs/faq.ts'])->toContain('${ROCK_POP_ACOUSTIC_RULE}')
        ->and($sources['resources/js/data/faqs/faq.ts'])->toContain('/exam-guide/syllabuses?from=faq#guitar')
        ->and($sources['resources/js/data/searchIndex.ts'])->toContain("url: GUITAR_EXAMS_ANCHOR,")
        ->and($sources['resources/js/data/searchIndex.ts'])->toContain("'acoustic guitar'");
});
