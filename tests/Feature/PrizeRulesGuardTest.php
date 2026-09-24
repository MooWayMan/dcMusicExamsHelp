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
        ->and(substr_count($sources['resources/js/pages/admin/QuarterEnd/Index.vue'], '${GIFT_TOKEN_REDEEM_RULE}'))->toBe(5);
});

