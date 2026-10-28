<?php

// tests/Feature/FaqSearchGuardTest.php
//
// Every page FAQ lives in resources/js/data/faqs/, the page draws it from
// there and site search reads the same file. These guards stop a second copy
// of an FAQ list, or a hand-typed link to an answer, from coming back.

test('no page writes its FAQ list inline', function () {
    expect(guardOffenders('/\bconst\s+(faqs|faqItems)\b[^=\n]*=\s*\[/', []))->toBe([]);
});

test('search never hand-writes a link to an FAQ answer', function () {
    expect(guardSources()['resources/js/data/searchIndex.ts'])->not->toMatch('/#faq-\d/');
});

test('every FAQ file is in the list site search reads', function () {
    $index = file_get_contents(resource_path('js/data/faqs/index.ts'));

    $files = collect(glob(resource_path('js/data/faqs/*.ts')))
        ->map(fn ($path) => basename($path, '.ts'))
        ->reject(fn ($name) => $name === 'index');

    expect($files)->not->toBeEmpty();
    foreach ($files as $name) {
        expect($index)->toContain("import {$name} from '@/data/faqs/{$name}'")
            ->and($index)->toMatch('/^\s+'.preg_quote($name, '/').',$/m');
    }
});

test('every page with an FAQ draws it from its file and opens #faq-N links', function () {
    $pages = collect(guardSources())
        ->filter(fn ($code, $path) => str_starts_with($path, 'resources/js/pages/') && str_contains($code, "from '@/data/faqs/"));

    expect($pages)->toHaveCount(15);
    foreach ($pages as $path => $code) {
        expect($code)->toContain('faqAccordionItems(faqPage)')
            ->and($code)->toContain('useAccordionHashOpen()');
    }
});

test('the landing page FAQ has at most four questions', function () {
    $welcome = file_get_contents(resource_path('js/data/faqs/welcome.ts'));

    expect(preg_match_all('/^\s+question:/m', $welcome))->toBeLessThanOrEqual(4);
});
