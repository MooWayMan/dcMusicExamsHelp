<?php

// tests/Feature/SearchBehaviourTest.php
//
// Runs site search for real. Node loads the same TypeScript the browser does
// (tests/js/search.test.ts): every FAQ answer can be found by its question,
// every FAQ link opens the answer it names, and "ill" finds illness, not "will".

use Symfony\Component\Process\Process;

test('site search finds every FAQ answer and opens the right one', function () {
    $process = new Process(
        ['node', '--import', './tests/js/register-alias.mjs', '--test', 'tests/js/search.test.ts'],
        base_path(),
    );
    $process->setTimeout(120);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});
