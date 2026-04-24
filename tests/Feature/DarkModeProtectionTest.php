<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Dark-mode regression guard
// ──────────────────────────────────────────
//
// Context: 2026-04-23. A student's dad opened musicexams.help on a
// Google Pixel. Chrome's "Auto dark theme" feature force-inverted the
// whole page into dark mode, making the hero text invisible. The fix
// was to declare `color-scheme: light` in two places — a <meta> tag
// and an inline <style> — which tells Chrome to stop force-darking.
//
// These tests can't *reproduce* Chrome's force-dark behaviour (that's
// browser-level; we'd need a real Chrome with the flag on). What they
// DO is guard the fix across every public page: if anyone removes the
// opt-outs or flips a page onto a layout that skips them, the next
// push catches it before the Pixel bug re-ships.

dataset('public_pages', [
    '/',
    '/faq',
    '/about',
    '/contact',
    '/privacy',
    '/cookies',
    '/terms',
    '/books',
    '/for-teachers',
    '/for-teachers/awards',
    '/for-parents',
    '/for-students',
    '/incentives',
    '/recognition',
    '/exam-fees',
    '/exam-guide',
    '/exam-guide/ucas-points',
    '/exam-guide/what-to-expect',
    '/exam-guide/digital-exams',
    '/exam-guide/grades-explained',
    '/exam-guide/syllabuses',
]);

test('public page declares color-scheme=light via meta tag', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertSee('<meta name="color-scheme" content="light">', false);
})->with('public_pages');

test('public page declares color-scheme=light via inline CSS', function (string $url) {
    // The inline <style> block in app.blade.php sets `color-scheme: light`
    // on the <html> element. This is what actually stops Chrome's
    // "force dark mode" on Pixel/Android.
    $this->get($url)
        ->assertOk()
        ->assertSee('color-scheme: light;', false);
})->with('public_pages');

test('public page does not render with the dark class by default', function (string $url) {
    // If $appearance defaults to 'system', Chrome inherits OS-level dark
    // preference and force-darks the hero background. The fix pins the
    // default to 'light' in the blade template.
    $this->get($url)
        ->assertOk()
        ->assertDontSee('<html lang="en"  class="dark">', false);
})->with('public_pages');
