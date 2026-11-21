<?php

// tests/Feature/ExamFeesTest.php
//
// Trinity's fees live once, in resources/js/data/examFees.ts, one schedule per
// calendar year. The /exam-fees page and its FAQ read them from there.
// tests/js/examFees.test.ts loads the data for real.

use Symfony\Component\Process\Process;

test('the fee schedules load and switch year on 1 January', function () {
    $process = new Process(
        ['node', '--import', './tests/js/register-alias.mjs', '--test', 'tests/js/examFees.test.ts'],
        base_path(),
    );
    $process->setTimeout(120);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});

test('no fee table is written out anywhere but the shared data', function () {
    expect(guardOffenders("/fee:\s*'£\d/u", ['resources/js/data/examFees.ts']))->toBe([]);
});

test('the page and its FAQ draw from the shared data', function () {
    $sources = guardSources();
    $page = $sources['resources/js/pages/ExamFees.vue'];
    $feesTab = $sources['resources/js/components/examfees/ExamFeesTab.vue'];

    expect($page)->toContain("from '@/data/examFees'")
        ->and($page)->toContain('<ExamFeesTab')
        ->and($page)->toContain('<ExamDatesTab')
        ->and($feesTab)->toContain('gradeRows(props.fees.faceToFace)')
        ->and($feesTab)->toContain('diplomaRows(props.fees.diplomasFaceToFace)')
        ->and($sources['resources/js/data/faqs/examFees.ts'])->toContain('feesChangeAnswer(new Date())');
});

test('the new-year banner is wired to the year switch', function () {
    $sources = guardSources();

    expect($sources['resources/js/pages/ExamFees.vue'])->toContain('@action="showNextYear"')
        ->and($sources['resources/js/pages/ExamFees.vue'])->toContain("getElementById('fee-years')")
        ->and($sources['resources/js/components/examfees/FeeYearSwitch.vue'])->toContain('id="fee-years"')
        ->and($sources['resources/js/components/reusables/MyBanner.vue'])->toContain("emit('action')");
});

test('the 2027 link opens the fees page', function () {
    $this->get('/exam-fees?year=2027')->assertOk();
});

test('the wall poster card sits under the banner and reads its files from the shared data', function () {
    $sources = guardSources();
    $page = $sources['resources/js/pages/ExamFees.vue'];
    $card = $sources['resources/js/components/examfees/FeePosterDownload.vue'];

    expect($page)->toContain('posterOnShow(today)')
        ->and($page)->toContain('<FeePosterDownload :schedule="posterYear"')
        ->and($card)->toContain('props.schedule.poster.pdf')
        ->and($card)->toContain('props.schedule.poster.preview')
        ->and($card)->not->toContain('wall-poster-');
});
