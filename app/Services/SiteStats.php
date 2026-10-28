<?php

// app/Services/SiteStats.php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The site's own page-open and button-press counter.
 *
 * Every hit adds 1 to a daily total and nothing else is kept: no IP, no
 * browser, no user id, no cookie. That is what lets it count the visitors
 * who decline the cookie banner, whom Google Analytics never sees.
 *
 * Admins (and an admin impersonating a teacher) are never counted, and
 * nothing under /admin is ever recorded.
 */
class SiteStats
{
    public const KIND_PAGE = 'page';

    public const KIND_EVENT = 'event';

    public const RANGES = [7, 28, 90, 365];

    public const DEFAULT_RANGE = 28;

    // A site search, counted by the words searched for (28 Oct 2026), so Paul
    // can see what people look for and, above all, what finds nothing.
    public const EVENT_SEARCH = 'search';

    public const EVENT_SEARCH_NO_RESULTS = 'search_no_results';

    // Words of the site's own that are often capitalised in a search, so a
    // pair of them ("Rock Pop", "Learn Music") is not mistaken for a name.
    private const SITE_WORDS = [
        'trinity', 'mytrinity', 'rock', 'pop', 'classical', 'jazz', 'grade', 'grades', 'initial',
        'digital', 'theory', 'acoustic', 'electric', 'guitar', 'piano', 'drums', 'bass', 'vocals',
        'keyboard', 'singing', 'violin', 'trumpet', 'flute', 'clarinet', 'saxophone', 'centre',
        'liverpool', 'wirral', 'learn', 'music', 'ucas', 'abrsm', 'lcm', 'rsl', 'faq', 'exam',
        'exams', 'face', 'to', 'certificate', 'hall', 'fame', 'top', 'ten', 'school', 'tafelmusik',
    ];

    public function __construct(private Impersonation $impersonation) {}

    public function shouldCount(Request $request): bool
    {
        if ($request->user()?->isAdmin()) {
            return false;
        }

        return ! $this->impersonation->isImpersonating();
    }

    /**
     * Reduce a URL to a countable path, or null when it must not be counted:
     * not a real GET page, an admin page, or a page whose URL carries a
     * value (a reset token, a record id) rather than being one fixed page.
     */
    public function normalisePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = '/'.trim($path, '/');

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return null;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpException) {
            return null;
        }

        if ($route->parameterNames() !== [] || str_starts_with((string) $route->getName(), 'admin.')) {
            return null;
        }

        return $path;
    }

    /**
     * Count one hit reported by the browser. Returns false when it was not
     * counted (an admin, an impersonation, or a URL that must not be
     * counted), so callers never have to repeat those rules.
     */
    public function recordHit(Request $request, string $kind, string $url, ?string $event = null, ?string $detail = null): bool
    {
        $path = $this->normalisePath($url);

        if ($path === null || ! $this->shouldCount($request)) {
            return false;
        }

        $isEvent = $kind === self::KIND_EVENT;

        if ($isEvent && in_array($event, [self::EVENT_SEARCH, self::EVENT_SEARCH_NO_RESULTS], true)) {
            $detail = $this->cleanSearchTerm((string) $detail);

            if ($detail === null) {
                return false;
            }
        }

        $this->record(
            $kind,
            $path,
            $isEvent ? (string) $event : '',
            $isEvent ? mb_substr(trim((string) preg_replace('/\s+/u', ' ', (string) $detail)), 0, 100) : '',
        );

        return true;
    }

    /**
     * What is kept of a search: trimmed, lower case, at most 60 characters.
     * Null (not kept at all) when it may identify someone: an email address,
     * a run of digits (a phone or candidate number), or two capitalised
     * words in a row that are not the site's own words, which is how a
     * person's name is usually typed. A name typed all in lower case cannot
     * be told apart from ordinary words and would be kept.
     */
    public function cleanSearchTerm(string $raw): ?string
    {
        $term = trim((string) preg_replace('/\s+/u', ' ', $raw));

        if ($term === '' || str_contains($term, '@') || preg_match('/\d[\d\s\-]{4,}\d/', $term)) {
            return null;
        }

        $words = explode(' ', $term);
        for ($i = 0; $i < count($words) - 1; $i++) {
            if ($this->isUnknownCapitalised($words[$i]) && $this->isUnknownCapitalised($words[$i + 1])) {
                return null;
            }
        }

        return mb_substr(mb_strtolower($term), 0, 60);
    }

    private function isUnknownCapitalised(string $word): bool
    {
        $word = trim($word, " \t.,!?'\"()");

        return preg_match('/^\p{Lu}\p{Ll}+$/u', $word) === 1
            && ! in_array(mb_strtolower($word), self::SITE_WORDS, true);
    }

    public function record(string $kind, string $path, string $event = '', string $detail = '', ?CarbonInterface $day = null): void
    {
        DB::table('site_stats')->upsert(
            [[
                'day' => ($day ?? now())->toDateString(),
                'kind' => $kind,
                'path' => $path,
                'event' => $event,
                'detail' => $detail,
                'hits' => 1,
            ]],
            ['day', 'kind', 'path', 'event', 'detail'],
            ['hits' => DB::raw('site_stats.hits + 1')],
        );
    }

    /**
     * Everything the admin Site stats page shows, for the last $days days
     * including today.
     *
     * @return array{
     *     days: int,
     *     from: string,
     *     totals: array{pages: int, events: int},
     *     pages: array<int, array{path: string, hits: int}>,
     *     events: array<int, array{path: string, event: string, detail: string, hits: int}>,
     *     searches: array<int, array{term: string, hits: int, noResults: int}>,
     *     daily: array<int, array{day: string, pages: int, events: int}>,
     *     chart: array{unit: string, bars: array<int, array{label: string, full: string, pages: int, events: int}>}
     * }
     */
    public function summary(int $days): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : self::DEFAULT_RANGE;
        $from = now()->subDays($days - 1)->toDateString();

        $base = fn () => DB::table('site_stats')->where('day', '>=', $from);

        $pages = $base()
            ->where('kind', self::KIND_PAGE)
            ->groupBy('path')
            ->selectRaw('path, SUM(hits) AS hits')
            ->orderByDesc('hits')
            ->orderBy('path')
            ->get()
            ->map(fn ($row) => ['path' => $row->path, 'hits' => (int) $row->hits])
            ->all();

        $searchEvents = [self::EVENT_SEARCH, self::EVENT_SEARCH_NO_RESULTS];

        $events = $base()
            ->where('kind', self::KIND_EVENT)
            ->whereNotIn('event', $searchEvents)
            ->groupBy('path', 'event', 'detail')
            ->selectRaw('path, event, detail, SUM(hits) AS hits')
            ->orderByDesc('hits')
            ->orderBy('path')
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'event' => $row->event,
                'detail' => $row->detail,
                'hits' => (int) $row->hits,
            ])
            ->all();

        // Searches get their own list: what was searched for, how often, and
        // how often it found nothing. Those that found nothing come first,
        // because they are the answers the site is missing.
        $searches = $base()
            ->where('kind', self::KIND_EVENT)
            ->whereIn('event', $searchEvents)
            ->groupBy('detail', 'event')
            ->selectRaw('detail, event, SUM(hits) AS hits')
            ->get()
            ->groupBy('detail')
            ->map(fn ($rows, $term) => [
                'term' => (string) $term,
                'hits' => (int) $rows->sum('hits'),
                'noResults' => (int) $rows->where('event', self::EVENT_SEARCH_NO_RESULTS)->sum('hits'),
            ])
            ->sortBy([['noResults', 'desc'], ['hits', 'desc'], ['term', 'asc']])
            ->values()
            ->all();

        $byDay = $base()
            ->groupBy('day', 'kind')
            ->selectRaw('day, kind, SUM(hits) AS hits')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $daily = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $rows = $byDay->get($day, collect());
            $daily[] = [
                'day' => $day,
                'pages' => (int) $rows->where('kind', self::KIND_PAGE)->sum('hits'),
                'events' => (int) $rows->where('kind', self::KIND_EVENT)->sum('hits'),
            ];
        }

        return [
            'days' => $days,
            'from' => $from,
            'totals' => [
                'pages' => array_sum(array_column($pages, 'hits')),
                'events' => array_sum(array_column($events, 'hits')) + array_sum(array_column($searches, 'hits')),
            ],
            'pages' => $pages,
            'events' => $events,
            'searches' => $searches,
            'daily' => $daily,
            'chart' => $this->chartBuckets($daily, $days),
        ];
    }

    /**
     * The daily totals grouped for the chart: by day for a week or a month,
     * by week (Monday start) for 90 days, by month for a year, so the chart
     * never has to draw hundreds of hairline bars.
     *
     * @param  array<int, array{day: string, pages: int, events: int}>  $daily
     * @return array{unit: string, bars: array<int, array{label: string, full: string, pages: int, events: int}>}
     */
    public function chartBuckets(array $daily, int $days): array
    {
        $unit = match (true) {
            $days <= 28 => 'day',
            $days <= 90 => 'week',
            default => 'month',
        };

        $bars = [];
        foreach ($daily as $row) {
            $date = Carbon::parse($row['day']);
            $monday = $date->copy()->startOfWeek(CarbonInterface::MONDAY);

            [$key, $label, $full] = match ($unit) {
                'day' => [$row['day'], $date->format('j'), $date->format('D j M')],
                'week' => [$monday->toDateString(), $monday->format('j M'), 'Week of '.$monday->format('j M')],
                default => [$date->format('Y-m'), $date->format('M'), $date->format('F Y')],
            };

            $bars[$key] ??= ['label' => $label, 'full' => $full, 'pages' => 0, 'events' => 0];
            $bars[$key]['pages'] += $row['pages'];
            $bars[$key]['events'] += $row['events'];
        }

        return ['unit' => $unit, 'bars' => array_values($bars)];
    }
}
