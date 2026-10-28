<?php

// tests/Feature/GuitarExamsTest.php
//
// Trinity has three guitar exams on two booking systems. The difference, the
// Rock & Pop instrument rule and the three syllabus links live once, in
// lib/guitarExams.ts, and the Syllabuses page, the FAQ and site search read
// them from there.

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
        ->and($sources['resources/js/pages/Faq.vue'])->toContain('${ROCK_POP_ACOUSTIC_RULE}')
        ->and($sources['resources/js/pages/Faq.vue'])->toContain('/exam-guide/syllabuses?from=faq#guitar')
        ->and($sources['resources/js/data/searchIndex.ts'])->toContain("url: GUITAR_EXAMS_ANCHOR,")
        ->and($sources['resources/js/data/searchIndex.ts'])->toContain("'acoustic guitar'");
});

test('every search result that opens a /faq answer opens the right one', function () {
    // The FAQ accordion numbers its answers by position, so adding or removing
    // a question moves every link after it. The illness answer was linked as
    // faq-17 while it sat 16th, and opened nothing.
    $faq = file_get_contents(resource_path('js/pages/Faq.vue'));
    $search = file_get_contents(resource_path('js/data/searchIndex.ts'));

    preg_match_all("/question:\s*'((?:[^'\\\\]|\\\\.)*)'/", $faq, $m);
    $questions = array_map('stripslashes', $m[1]);

    preg_match_all("#url: '/faq\#faq-(\d+)'#", $search, $links);
    foreach ($links[1] as $n) {
        expect((int) $n)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(count($questions));
    }

    preg_match_all("/id: 'faq-(\d+)',\s*title: '((?:[^'\\\\]|\\\\.)*)'/", $search, $entries, PREG_SET_ORDER);
    expect($entries)->not->toBeEmpty();
    foreach ($entries as [, $n, $title]) {
        expect($questions[(int) $n - 1] ?? null)->toBe(stripslashes($title));
    }
});
