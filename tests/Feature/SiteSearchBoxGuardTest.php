<?php

// tests/Feature/SiteSearchBoxGuardTest.php
//
// There is one site search box (components/SiteSearchBox.vue) and one place
// that builds a search address (composables/useSiteSearch.ts). Before 28 Oct
// 2026 there were four hand-built copies (FAQ, search page, phone menu twice
// over the submit code), each with its own styling.

test('every search box on the site is SiteSearchBox', function () {
    expect(guardOffenders('/role="search"/', ['resources/js/components/SiteSearchBox.vue']))->toBe([]);
});

test('a search address is built in one place', function () {
    expect(guardOffenders("#'/search'\s*\+#", ['resources/js/composables/useSiteSearch.ts']))->toBe([]);
});

test('the home page, FAQ, search page and phone menu all use it', function () {
    $sources = guardSources();

    foreach (['resources/js/pages/Welcome.vue', 'resources/js/pages/Faq.vue', 'resources/js/pages/Search.vue', 'resources/js/components/layouts/Navbar.vue'] as $path) {
        expect($sources[$path])->toContain('<SiteSearchBox');
    }
});
