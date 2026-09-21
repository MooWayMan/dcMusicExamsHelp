<?php

// tests/Feature/SiteStatsGuardTest.php
//
// Things the site-stats work made shared, each of which must exist once.
// Keyed on the act (reading the cookie, writing the table, listing the
// pages), with comments stripped by guardSources() in tests/Pest.php.

function guardOffenders(string $pattern, array $allowed): array
{
    return collect(guardSources())
        ->filter(fn ($code) => preg_match($pattern, $code) === 1)
        ->keys()
        ->reject(fn ($path) => in_array($path, $allowed, true)
            || str_starts_with($path, 'resources/js/actions/')
            || str_starts_with($path, 'resources/js/routes/')
            || str_starts_with($path, 'resources/js/wayfinder/'))
        ->values()
        ->all();
}

test('the site_stats table is written and read only by SiteStats', function () {
    expect(guardOffenders("/['\"]site_stats['\"]/", ['app/Services/SiteStats.php']))->toBe([]);
});

test('only useSiteStats posts to the stats endpoint', function () {
    expect(guardOffenders('/siteStatsStore|[\'"]\/stats[\'"]/', [
        'resources/js/composables/useSiteStats.ts',
        'routes/web.php',
    ]))->toBe([]);
});

test('the XSRF cookie is read in one place', function () {
    expect(guardOffenders('/XSRF-TOKEN=/', ['resources/js/lib/utils.ts']))->toBe([]);
});

test('the list of public pages is written down once', function () {
    expect(guardOffenders("/'ExamGuideSyllabuses',\s*'ExamFees'/", ['resources/js/lib/publicPages.ts']))->toBe([]);
});

test('the rounded bar shapes are drawn by one helper', function () {
    expect(guardOffenders('/function\s+(topRounded|rightRounded)\s*\(/', ['resources/js/lib/chartShapes.ts']))->toBe([]);
});

test('client-side paging lives in usePagination only', function () {
    expect(guardOffenders('/function\s+usePagination\s*[<(]/', ['resources/js/composables/usePagination.ts']))->toBe([]);
});

test('chart tooltips are positioned by useChartTip only', function () {
    expect(guardOffenders('/clientX\s*-\s*\w+\.left/', ['resources/js/composables/useChartTip.ts']))->toBe([]);
});
