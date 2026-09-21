<?php

// tests/Feature/SiteStatsTest.php
//
// The site's own anonymous counter (App\Services\SiteStats). Round trips:
// what the browser posts to /stats must come back out on /admin/site-stats,
// and the things that must never be counted must not appear there.

use App\Models\User;
use App\Services\Impersonation;
use App\Services\SiteStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function postStat(array $body)
{
    return test()->postJson('/stats', $body);
}

function statsPage(int $days = 28): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    return test()->actingAs($admin)
        ->get("/admin/site-stats?days={$days}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/SiteStats/Index')->has('stats')->has('ranges'))
        ->viewData('page')['props']['stats'];
}

test('a page open posted by a visitor comes back on the admin page', function () {
    postStat(['kind' => 'page', 'url' => '/exam-fees'])->assertNoContent();
    postStat(['kind' => 'page', 'url' => '/exam-fees?utm_source=poster'])->assertNoContent();
    postStat(['kind' => 'page', 'url' => '/'])->assertNoContent();

    $stats = statsPage();

    expect($stats['pages'])->toBe([
        ['path' => '/exam-fees', 'hits' => 2],
        ['path' => '/', 'hits' => 1],
    ])->and($stats['totals'])->toBe(['pages' => 3, 'events' => 0]);
});

test('a button press and a booking click come back with the page they happened on', function () {
    postStat(['kind' => 'event', 'url' => '/', 'event' => 'button', 'detail' => "  Book   an\nexam "])->assertNoContent();
    postStat(['kind' => 'event', 'url' => '/', 'event' => 'button', 'detail' => 'Book an exam'])->assertNoContent();
    postStat(['kind' => 'event', 'url' => '/exam-fees', 'event' => 'booking_click', 'detail' => 'digital'])->assertNoContent();

    $stats = statsPage();

    expect($stats['events'])->toBe([
        ['path' => '/', 'event' => 'button', 'detail' => 'Book an exam', 'hits' => 2],
        ['path' => '/exam-fees', 'event' => 'booking_click', 'detail' => 'digital', 'hits' => 1],
    ])->and($stats['totals']['events'])->toBe(3);
});

test('nothing about the visitor is stored', function () {
    expect(Schema::getColumnListing('site_stats'))
        ->toEqualCanonicalizing(['id', 'day', 'kind', 'path', 'event', 'detail', 'hits']);
});

test('an admin is never counted', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    postStat(['kind' => 'page', 'url' => '/exam-fees'])->assertNoContent();

    expect(DB::table('site_stats')->count())->toBe(0);
});

test('an admin impersonating a teacher is never counted', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->withSession([Impersonation::IMPERSONATOR_KEY => 1]);

    postStat(['kind' => 'page', 'url' => '/dashboard'])->assertNoContent();

    expect(DB::table('site_stats')->count())->toBe(0);
});

test('a signed-in teacher is counted like anyone else', function () {
    $this->actingAs(User::factory()->create(['role' => 'teacher']));

    postStat(['kind' => 'page', 'url' => '/dashboard'])->assertNoContent();

    expect(DB::table('site_stats')->where('path', '/dashboard')->value('hits'))->toBe(1);
});

test('admin pages, URLs carrying a value and made-up pages are never recorded', function (string $url) {
    postStat(['kind' => 'page', 'url' => $url])->assertNoContent();

    expect(DB::table('site_stats')->count())->toBe(0);
})->with([
    'admin root' => ['/admin'],
    'admin page' => ['/admin/contacts'],
    'reset token' => ['/reset-password/20ad76475dd8c4c0c8158ccdece27ca0d446974039a8fb74fabe08a402e66590'],
    'short link key' => ['/go/exam-day'],
    'no such page' => ['/wp-login.php'],
]);

test('an action must say what it was', function () {
    postStat(['kind' => 'event', 'url' => '/'])->assertStatus(422);
    postStat(['kind' => 'event', 'url' => '/', 'event' => 'Not Allowed!'])->assertStatus(422);
    postStat(['kind' => 'visit', 'url' => '/'])->assertStatus(422);
});

test('the admin page only counts days inside the chosen range', function () {
    $stats = app(SiteStats::class);
    $stats->record(SiteStats::KIND_PAGE, '/faq', day: now()->subDays(6));
    $stats->record(SiteStats::KIND_PAGE, '/faq', day: now()->subDays(7));

    $week = statsPage(7);

    expect($week['totals']['pages'])->toBe(1)
        ->and($week['daily'])->toHaveCount(7)
        ->and($week['daily'][0])->toBe(['day' => now()->subDays(6)->toDateString(), 'pages' => 1, 'events' => 0]);

    expect(statsPage(28)['totals']['pages'])->toBe(2);
});

test('an unknown range falls back to 28 days', function () {
    expect(statsPage(3)['days'])->toBe(SiteStats::DEFAULT_RANGE);
});

test('only an admin can open the site stats page', function () {
    $this->get('/admin/site-stats')->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->get('/admin/site-stats')
        ->assertForbidden();
});

test('the chart groups by day, then week, then month, and loses no hits doing it', function (int $days, string $unit, int $minBars, int $maxBars) {
    $stats = app(SiteStats::class);
    $stats->record(SiteStats::KIND_PAGE, '/faq', day: now());
    $stats->record(SiteStats::KIND_PAGE, '/faq', day: now()->subDays($days - 1));
    $stats->record(SiteStats::KIND_EVENT, '/', 'button', 'Book an exam', now()->subDays(intdiv($days, 2)));

    $summary = statsPage($days);
    $bars = collect($summary['chart']['bars']);

    expect($summary['chart']['unit'])->toBe($unit)
        ->and($bars->count())->toBeGreaterThanOrEqual($minBars)->toBeLessThanOrEqual($maxBars)
        ->and($bars->sum('pages'))->toBe($summary['totals']['pages'])
        ->and($bars->sum('events'))->toBe($summary['totals']['events'])
        ->and($summary['totals'])->toBe(['pages' => 2, 'events' => 1]);
})->with([
    'a week' => [7, 'day', 7, 7],
    'four weeks' => [28, 'day', 28, 28],
    'ninety days' => [90, 'week', 13, 14],
    'a year' => [365, 'month', 12, 13],
]);

test('a week bar starts on a Monday', function () {
    $bars = app(SiteStats::class)->chartBuckets([
        ['day' => '2026-09-20', 'pages' => 1, 'events' => 0],
        ['day' => '2026-09-21', 'pages' => 2, 'events' => 0],
    ], 90)['bars'];

    expect($bars)->toBe([
        ['label' => '14 Sep', 'full' => 'Week of 14 Sep', 'pages' => 1, 'events' => 0],
        ['label' => '21 Sep', 'full' => 'Week of 21 Sep', 'pages' => 2, 'events' => 0],
    ]);
});
