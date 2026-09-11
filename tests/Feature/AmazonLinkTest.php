<?php

// tests/Feature/AmazonLinkTest.php
//
// The Amazon Associates tag was baked into ~2,480 stored links, so changing it
// on 4 Aug 2026 meant rewriting six seed files and three database columns, twice
// in one afternoon, and a single wrong character had already cost the first
// Associates account. Since 11 Sep 2026 the data holds bare ASINs, the tag lives
// in config('services.amazon.associates_tag'), and App\Support\AmazonLink is the
// only code that builds a link.

use App\Models\SyllabusPiece;
use App\Support\AmazonLink;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the Associates tag is musicexams-21', function () {
    expect(config('services.amazon.associates_tag'))->toBe('musicexams-21');
});

test('the Piece Finder builds both buy links from stored ASINs with the configured tag', function () {
    config()->set('services.amazon.associates_tag', 'roundtrip-21');

    SyllabusPiece::factory()->create([
        'title' => 'Jupiter Storm Roundtrip',
        'buy_kind' => 'exact',
        'buy_asin' => '1804903388',
        'buy_alt_asin' => '1804903140',
    ]);
    SyllabusPiece::factory()->create([
        'title' => 'Jupiter Storm No Link',
        'buy_kind' => 'none',
    ]);

    $pieces = collect($this->get('/syllabus?q=Jupiter%20Storm')->viewData('page')['props']['pieces'])->keyBy('title');

    expect($pieces)->toHaveCount(2)
        ->and($pieces['Jupiter Storm Roundtrip']['buy_url'])->toBe('https://www.amazon.co.uk/dp/1804903388?tag=roundtrip-21')
        ->and($pieces['Jupiter Storm Roundtrip']['buy_alt_url'])->toBe('https://www.amazon.co.uk/dp/1804903140?tag=roundtrip-21')
        ->and($pieces['Jupiter Storm No Link']['buy_url'])->toBeNull()
        ->and($pieces['Jupiter Storm No Link']['buy_alt_url'])->toBeNull();
});

test('a missing ASIN gives no link rather than a broken one', function () {
    expect(AmazonLink::forAsin(null))->toBeNull()
        ->and(AmazonLink::forAsin(''))->toBeNull();
});

/**
 * Every file that could plausibly carry a hand-written Amazon link, with
 * comments removed. Migrations are excluded on purpose: a migration is a
 * frozen snapshot that must not call app code, so the one that converted the
 * links to ASINs has to spell a link out in its down().
 *
 * @return array<string, string> relative path => comment-free contents
 */
function amazonGuardSources(): array
{
    $roots = ['app', 'config', 'routes', 'resources/js', 'resources/views', 'database/seeders', 'database/factories'];
    $sources = [];

    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($root), FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $relative = ltrim(str_replace(base_path(), '', $path), '/');
            $ext = $file->getExtension();

            if (! in_array($ext, ['php', 'ts', 'js', 'vue', 'json'], true)) {
                continue;
            }

            $code = file_get_contents($path);

            if ($ext === 'php') {
                $code = collect(token_get_all($code))
                    ->reject(fn ($t) => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))
                    ->map(fn ($t) => is_array($t) ? $t[1] : $t)
                    ->implode('');
            } elseif ($ext !== 'json') {
                $code = preg_replace(['#/\*.*?\*/#s', '#<!--.*?-->#s', '#^\s*//.*$#m'], '', $code);
            }

            $sources[$relative] = $code;
        }
    }

    return $sources;
}

test('no Amazon product link is written out anywhere except AmazonLink', function () {
    $offenders = collect(amazonGuardSources())
        ->filter(fn ($code) => str_contains($code, 'amazon.co.uk/dp/'))
        ->keys()
        ->reject(fn ($path) => $path === 'app/Support/AmazonLink.php')
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

test('the Associates tag is written down only in config/services.php', function () {
    $offenders = collect(amazonGuardSources())
        ->filter(fn ($code) => str_contains($code, 'musicexams-21'))
        ->keys()
        ->reject(fn ($path) => $path === 'config/services.php')
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
