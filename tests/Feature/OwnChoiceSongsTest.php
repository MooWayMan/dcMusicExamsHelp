<?php

// tests/Feature/OwnChoiceSongsTest.php
//
// Trinity's Rock & Pop own-choice rules and parameters live once, in
// resources/js/data/ownChoiceSongs.ts. The page and its FAQ read them from
// there. tests/js/ownChoice.test.ts loads the data for real.

use Symfony\Component\Process\Process;

test('the own-choice data holds every instrument and grade', function () {
    $process = new Process(
        ['node', '--import', './tests/js/register-alias.mjs', '--test', 'tests/js/ownChoice.test.ts'],
        base_path(),
    );
    $process->setTimeout(120);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});

test('the own-choice song lengths are written down once', function () {
    expect(guardOffenders('/45 seconds – 2 minutes|3\.5 – 5 minutes/u', ['resources/js/data/ownChoiceSongs.ts']))->toBe([]);
});

test('the page draws everything from the shared data', function () {
    $page = guardSources()['resources/js/pages/ExamGuideOwnChoice.vue'];

    expect($page)->toContain('OWN_CHOICE_INSTRUMENTS')
        ->and($page)->toContain('gradeRows(')
        ->and($page)->toContain('GRADE_OVERVIEW')
        ->and($page)->toContain('OWN_CHOICE_PAPERWORK')
        ->and($page)->toContain('faqAccordionItems(faqPage)');
});
