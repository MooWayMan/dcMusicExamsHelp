<?php

// tests/Feature/PrizeRulesGuardTest.php
//
// The gift token deadline is stated once, in lib/prizeRules.ts, and every page
// and winner email quotes that constant. A second copy of the wording would
// drift the day the rule changes.

test('the gift token deadline is written down once', function () {
    expect(guardOffenders('/redeemed within \d+ months/', ['resources/js/lib/prizeRules.ts']))->toBe([]);
});

test('the Incentives page and the winner emails use the shared rule', function () {
    $sources = guardSources();

    expect($sources['resources/js/pages/Incentives.vue'])->toContain('GIFT_TOKEN_REDEEM_RULE')
        ->and(substr_count($sources['resources/js/pages/admin/QuarterEnd/Index.vue'], '${GIFT_TOKEN_REDEEM_RULE}'))->toBe(6);
});

test('prizes that go through a teacher are claimed by email, not handed over as a code', function () {
    $page = guardSources()['resources/js/pages/admin/QuarterEnd/Index.vue'];

    expect($page)->not->toContain('[PASTE GIFT CARD CODE HERE]')
        ->and(substr_count($page, 'email me at musicexams@musicexams.help, and I\'ll send the gift card straight to them'))->toBe(2);
});

test('the reply-to-claim wording is written down once and used by every direct winner email', function () {
    expect(guardOffenders("/just reply to this email and I'll send the gift card/", ['resources/js/lib/prizeRules.ts']))->toBe([]);

    $page = guardSources()['resources/js/pages/admin/QuarterEnd/Index.vue'];

    expect(substr_count($page, '${REPLY_TO_CLAIM} ${GIFT_TOKEN_REDEEM_RULE}'))->toBe(3);
});

test('only the teacher draw hands over a gift card link without a claim', function () {
    $page = guardSources()['resources/js/pages/admin/QuarterEnd/Index.vue'];

    expect(substr_count($page, '[PASTE GIFT CARD LINK HERE]'))->toBe(1);
});
