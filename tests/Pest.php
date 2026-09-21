<?php

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function (): void {
        // Rate limiter state persists in the array cache across tests in
        // a single process. Without a flush, any Feature test that fires
        // its 6th+ request to a throttled endpoint trips the limit and
        // fails — even though the test isn't trying to test throttling.
        // Flushing here keeps every Feature test independent.
        Cache::flush();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Every app, config, route, front-end, view, seeder and factory file, with
 * comments removed, for the guard tests that fail when a second copy of
 * something appears (AmazonLinkTest, SiteStatsGuardTest). Comments are
 * stripped so a guard never fires on prose explaining the thing it guards.
 * Migrations are excluded on purpose: a migration is a frozen snapshot that
 * must not call app code, so the one that converted Amazon links to ASINs
 * has to spell a link out in its down().
 *
 * @return array<string, string> relative path => comment-free contents
 */
function guardSources(): array
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
