<?php

// tests/Feature/TextConstructorSlotsTest.php
//
// Ported from Music Register, 26 Sep 2026. There, 33 call sites passed
// <template #myBody> to MyTextConstructor, a slot it has never had. Vue drops
// an unknown named slot silently, so every one of those paragraphs was
// invisible, and a missing sentence does not look like a bug.
//
// vue-tsc does not check slot names in this project's config, so this does. It
// reads the slot list the constructor DECLARES (its defineSlots block) and
// fails on any page that hands a MyTextConstructor a name outside it. Keyed on
// the act — a slot passed to this component — not on any particular word.

function tcsDeclaredSlots(): array
{
    $source = file_get_contents(resource_path('js/components/reusables/MyTextConstructor.vue'));

    preg_match('/defineSlots<\{(.*?)\}>\(\)/s', $source, $block);
    preg_match_all('/^\s*(\w+)\??\s*:/m', $block[1] ?? '', $names);

    return $names[1];
}

test('MyTextConstructor declares its slots, including myPara', function () {
    expect(tcsDeclaredSlots())->toEqualCanonicalizing(['myEyebrow', 'myTitle', 'mySubTitle', 'myPara', 'default']);
});

test('no page passes MyTextConstructor a slot it does not have', function () {
    $allowed = tcsDeclaredSlots();
    $root = resource_path('js');
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'vue') {
            continue;
        }

        $source = preg_replace('#<!--.*?-->#s', '', file_get_contents($file->getPathname()));

        preg_match_all('#<MyTextConstructor\b[^>]*(?<!/)>(.*?)</MyTextConstructor>#s', $source, $blocks);

        foreach ($blocks[1] as $inner) {
            preg_match_all('/(?:#|v-slot:)([A-Za-z_]\w*)/', $inner, $used);

            foreach (array_diff($used[1], $allowed) as $bad) {
                $offenders[] = str_replace($root.'/', '', $file->getPathname()).' #'.$bad;
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe(
        [],
        'MyTextConstructor only renders: '.implode(', ', $allowed).'. Any other slot name is dropped by Vue and the text never appears.',
    );
});
