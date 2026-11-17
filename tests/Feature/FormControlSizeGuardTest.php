<?php

// tests/Feature/FormControlSizeGuardTest.php
//
// The admin-form box size lives once, in resources/js/lib/formControlSize.ts,
// so a dropdown, a text box and a date box in one row always line up.

test('the compact form-box size is written in one place', function () {
    expect(guardOffenders('/px-3 py-2\.5 text-sm/', ['resources/js/lib/formControlSize.ts']))->toBe([]);
});

test('the select, input and textarea constructors all take it from there', function () {
    $sources = guardSources();

    foreach (['MySelectConstructor', 'MyInputConstructor', 'MyTextareaConstructor'] as $name) {
        expect($sources["resources/js/components/reusables/{$name}.vue"])->toContain('COMPACT_CONTROL');
    }
});
