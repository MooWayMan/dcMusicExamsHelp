<?php

// tests/Feature/CardConstructorGuardTest.php
//
// A page section's card is MyCardConstructor. Before 27 Sep 2026 every page
// hand-typed the same class string for it; the ones still hand-typed are
// listed below as a baseline that may only go down. Moving one onto the
// constructor means lowering its number here (the test says so when a count
// drops), and a new hand-rolled card anywhere fails.
//
// Keyed on the act: a <div> or <section> whose classes are exactly the card
// (rounded-xl border border-brand-border bg-brand-surface) plus nothing but
// spacing, text-center or shadow-sm. Inputs, link tiles and tinted notices
// carry other classes and are not counted. Comments are stripped first.

function cardGuardCounts(): array
{
    $core = ['rounded-xl', 'border', 'border-brand-border', 'bg-brand-surface'];
    $extra = '/^((sm|md|lg):)?(p|px|py|pt|pb|m|mb|mt|my)-\d+(\.\d)?$|^text-center$|^shadow-sm$/';
    $root = resource_path('js').'/';
    $counts = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'vue') {
            continue;
        }

        $relative = str_replace($root, '', $file->getPathname());

        if ($relative === 'components/reusables/MyCardConstructor.vue') {
            continue;
        }

        $source = preg_replace('#<!--.*?-->#s', '', file_get_contents($file->getPathname()));
        preg_match_all('/<(?:div|section)\b[^>]*?\sclass="([^"]*)"/s', $source, $matches);

        $n = 0;
        foreach ($matches[1] as $classString) {
            $classes = preg_split('/\s+/', trim($classString));

            if (array_diff($core, $classes) !== []) {
                continue;
            }

            $others = array_diff($classes, $core);

            if (collect($others)->every(fn ($c) => preg_match($extra, $c) === 1)) {
                $n++;
            }
        }

        if ($n > 0) {
            $counts[$relative] = $n;
        }
    }

    ksort($counts);

    return $counts;
}

test('no new hand-rolled cards: a page section card is MyCardConstructor', function () {
    $baseline = [
        'pages/admin/Contacts/Show.vue' => 3,
        'pages/admin/Dashboard.vue' => 8,
        'pages/admin/Imports/Index.vue' => 4,
        'pages/admin/Labels/Index.vue' => 1,
        'pages/admin/Orders/Create.vue' => 5,
        'pages/admin/Orders/Edit.vue' => 5,
        'pages/admin/Orders/Show.vue' => 3,
        'pages/admin/PendingResults/Index.vue' => 1,
        'pages/admin/QuarterComparison/Index.vue' => 3,
        'pages/admin/QuarterEnd/Index.vue' => 4,
        'pages/admin/ReEntryPermits/Index.vue' => 1,
        'pages/admin/Reconciliation/Index.vue' => 2,
        'pages/admin/ResultsScan/Index.vue' => 1,
        'pages/admin/Schools/Create.vue' => 1,
        'pages/admin/Schools/Edit.vue' => 1,
        'pages/admin/Schools/Show.vue' => 1,
        'pages/admin/Tasks/Index.vue' => 4,
        'pages/admin/Users/Show.vue' => 2,
    ];

    $counts = cardGuardCounts();

    foreach ($counts as $file => $n) {
        expect($n)->toBeLessThanOrEqual(
            $baseline[$file] ?? 0,
            "{$file} has {$n} hand-rolled card(s), baseline ".($baseline[$file] ?? 0).'. Use MyCardConstructor.',
        );
    }

    foreach ($baseline as $file => $n) {
        expect($counts[$file] ?? 0)->toBe(
            $n,
            "{$file} is down to ".($counts[$file] ?? 0)." hand-rolled card(s). Lower its baseline in this test to match, so it cannot creep back.",
        );
    }
});
