<?php

// app/Http/Controllers/ThankYouController.php

namespace App\Http\Controllers;

use App\Models\ExamEntry;
use App\Models\PageMaintenance;
use App\Models\PrizeDraw;
use App\Models\TopScorerPublication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThankYouController extends Controller
{
    /**
     * GDPR-safe display name: "Seth B" unless parent has opted in.
     */
    private function shortDisplayName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));

        if (count($parts) <= 1) {
            return $fullName;
        }

        $firstName = $parts[0];
        $lastInitial = mb_strtoupper(mb_substr(end($parts), 0, 1));

        return "{$firstName} {$lastInitial}";
    }

    private function displayName(ExamEntry $entry): string
    {
        if (! $entry->candidate_name) {
            return 'Unknown';
        }

        if ($entry->show_full_name) {
            return $entry->candidate_name;
        }

        $parts = preg_split('/\s+/', trim($entry->candidate_name));

        if (count($parts) <= 1) {
            return $entry->candidate_name;
        }

        $firstName = $parts[0];
        $lastInitial = mb_strtoupper(mb_substr(end($parts), 0, 1));

        return "{$firstName} {$lastInitial}";
    }

    /**
     * Public-facing instrument label for the Recognition page. The five Rock &
     * Pop exams are tagged "(Rock/Pop)" for consistency, using Trinity's R&P
     * names (Bass, Vocals) even where the stored instrument differs — the
     * stored name for R&P vocals is "Singing (Rock/Pop)" and R&P bass is
     * "Bass Guitar". Classical & Jazz names (e.g. "Electronic Keyboard",
     * "Drum Kit") pass through unchanged. Display-only: stored instrument
     * names are untouched everywhere else in the app.
     */
    private function publicInstrumentLabel(?string $name): string
    {
        if ($name === null) {
            return '—';
        }

        return [
            'Drums' => 'Drums (Rock/Pop)',
            'Guitar (Rock/Pop)' => 'Guitar (Rock/Pop)',
            'Singing (Rock/Pop)' => 'Vocals (Rock/Pop)',
            'Keyboards' => 'Keyboards (Rock/Pop)',
            'Bass Guitar' => 'Bass (Rock/Pop)',
        ][$name] ?? $name;
    }

    /**
     * Build hall-of-fame + table entries + summary for a single quarter.
     */
    private function buildQuarterData(int $quarter, int $year): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(2)->endOfMonth()->endOfDay();

        // Get ALL entries for this quarter (with and without scores)
        $entries = ExamEntry::with(['instrument', 'order:id,requested_start_date'])
            ->where('show_on_thank_you', true)
            ->whereResultPossible()
            ->get()
            ->filter(function ($entry) use ($start, $end) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && Carbon::parse($date)->between($start, $end);
            })
            ->sortByDesc('score');

        // Scored entries only (for top scorers + summary counts)
        $scoredEntries = $entries->filter(fn ($e) => $e->score !== null);

        // Top scorers — ONE data source: the TopScorerPublication snapshot
        // written by the admin "Publish top-scorer awards" button on Step 3
        // of /admin/quarter-end. Explicit click required — no live-calc
        // fallback, no auto-publish based on "all results in + draw run"
        // gates. Paul has to actively decide a quarter's awards are ready
        // before the public page surfaces them. (Removed 7 May 2026 after
        // the auto-publish-on-CANCELLED-flip near-miss in Q1.)
        //
        // Returns the four-award structure: { initial_5, '6_8' } each with
        // { distinction[], merit[] }, where each leaf is an array of tied
        // winners. Empty arrays mean either nobody hit that band in that
        // group, OR the quarter hasn't been published yet.
        $publication = TopScorerPublication::forQuarter($quarter, $year);
        $topScorers = [
            'initial_5' => ['distinction' => [], 'merit' => []],
            '6_8'       => ['distinction' => [], 'merit' => []],
        ];
        $hallOfFameEntries = collect(); // legacy flat list for old consumers

        if ($publication) {
            // Use the snapshot. Apply GDPR display rule (first name + initial
            // unless show_full_name was true at publication time).
            $topScorers = $publication->winners ?? $topScorers;
            foreach (['initial_5', '6_8'] as $group) {
                foreach (['distinction', 'merit'] as $band) {
                    $topScorers[$group][$band] = collect($topScorers[$group][$band] ?? [])
                        ->map(function (array $w) {
                            $w['name'] = ($w['show_full_name'] ?? false)
                                ? ($w['full_name'] ?? $w['name'])
                                : ($w['name'] ?? $this->shortDisplayName($w['full_name'] ?? ''));
                            $w['instrument'] = $this->publicInstrumentLabel($w['instrument'] ?? null);
                            // Don't leak full_name to the public payload
                            // unless the candidate opted in.
                            if (! ($w['show_full_name'] ?? false)) {
                                unset($w['full_name']);
                            }
                            return $w;
                        })
                        ->values()
                        ->toArray();
                }
            }
            $hallOfFameEntries = collect(\App\Support\TopScorers::flatten($topScorers))
                ->map(fn ($award) => [
                    'name'        => $award['winner']['name'],
                    'instrument'  => $award['winner']['instrument'] ?? '—',
                    'grade'       => $award['winner']['grade'],
                    'score'       => $award['winner']['score'],
                    'result'      => $award['band'] === 'distinction' ? 'Distinction' : 'Merit',
                    'award'       => $award['certificate'],
                    'certificate' => "{$award['certificate']} Certificate + Gift Token",
                ]);
        }

        // All entries — grouped by band then alphabetical
        // Waiting sorts after Pass but before Below Pass
        $bandOrder = ['Distinction' => 1, 'Merit' => 2, 'Pass' => 3, 'Waiting' => 4, 'Below Pass' => 5];

        $thankYouEntries = $entries->map(function (ExamEntry $e) use ($bandOrder) {
            $result = $e->score !== null ? $e->result_band : 'Waiting';
            $certificate = $e->score !== null ? $e->certificate_name : 'Bravo Certificate';

            return [
                'name' => $this->displayName($e),
                'instrument' => $this->publicInstrumentLabel($e->instrument?->name),
                'grade' => $e->grade,
                'result' => $result,
                'certificate' => $certificate,
                '_sortBand' => $bandOrder[$result] ?? 6,
            ];
        })->sortBy([
            ['_sortBand', 'asc'],
            ['name', 'asc'],
        ])->map(fn ($e) => collect($e)->except('_sortBand')->toArray())
        ->values()->toArray();

        // Summary counts (scored entries only)
        $distinctions = $scoredEntries->where('score', '>=', 87)->count();
        $merits = $scoredEntries->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $labelStart = $monthNames[$startMonth - 1];
        $labelEnd = $monthNames[$startMonth + 1];

        return [
            'quarter' => $quarter,
            'year' => $year,
            'label' => "Q{$quarter} – {$labelStart} – {$labelEnd} {$year}",
            'hallOfFameEntries' => $hallOfFameEntries->toArray(), // legacy flat list
            // Four-award structure (Initial-5 + 6-8, distinction + merit,
            // with ties as arrays). The Vue page renders cards from this.
            'topScorers' => $topScorers,
            // Was this quarter published from a snapshot, or live-calc?
            // Drives a small "Awards announced on…" line in the UI.
            'topScorersPublishedAt' => $publication?->published_at?->toIso8601String(),
            'thankYouEntries' => $thankYouEntries,
            'summary' => [
                'distinctions' => $distinctions,
                'merits' => $merits,
                'total' => $entries->count(),
                'pending_count' => $entries->filter(fn ($e) => $e->score === null)->count(),
            ],
        ];
    }

    public function __invoke(Request $request)
    {
        // If page is in maintenance, skip all heavy queries and return empty data
        if (PageMaintenance::isDown('recognition')) {
            return Inertia::render('ThankYou', [
                'defaultQuarter' => (int) ceil(now()->month / 3),
                'defaultYear' => (int) now()->year,
                'availableQuarters' => [],
                'allQuartersData' => [],
                'prizeDrawWinners' => [],
            ]);
        }

        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        // Build list of quarters that have data (using exam_date OR order's requested_start_date).
        // Hide future quarters — future bookings exist in orders but shouldn't
        // appear on the public Recognition page until the exams have taken place.
        // Sort: current quarter first, then descending into the past — that way
        // the dropdown reads newest-on-top and scales cleanly as years accumulate.
        $quartersWithData = ExamEntry::with('order:id,requested_start_date')
            ->where('show_on_thank_you', true)
            ->whereResultPossible()
            ->get()
            ->map(function ($entry) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                if (! $date) return null;
                $d = Carbon::parse($date);
                return ['quarter' => (int) ceil($d->month / 3), 'year' => (int) $d->year];
            })
            ->filter()
            ->filter(fn ($q) => $q['year'] < $currentYear
                || ($q['year'] === $currentYear && $q['quarter'] <= $currentQuarter))
            ->unique(fn ($q) => "{$q['quarter']}-{$q['year']}")
            ->sortByDesc(fn ($q) => $q['year'] * 10 + $q['quarter'])
            ->values()
            ->toArray();

        // Build data for ALL quarters that have entries
        $allQuartersData = collect($quartersWithData)
            ->map(fn ($q) => $this->buildQuarterData($q['quarter'], $q['year']))
            ->values()
            ->toArray();

        // Default to the latest quarter with data (or current quarter as fallback)
        $latest = collect($quartersWithData)->last();
        $defaultQuarter = $latest ? $latest['quarter'] : $currentQuarter;
        $defaultYear = $latest ? $latest['year'] : $currentYear;

        // Prize draw winners for all quarters
        $prizeDrawWinners = PrizeDraw::where('type', 'student')
            ->get()
            ->mapWithKeys(fn ($d) => ["{$d->quarter}-{$d->year}" => [
                'name' => $this->shortDisplayName($d->winner_name),
                'instrument' => $this->publicInstrumentLabel($d->winner_instrument),
                'grade' => $d->winner_grade,
            ]])
            ->toArray();

        return Inertia::render('ThankYou', [
            'defaultQuarter' => $defaultQuarter,
            'defaultYear' => $defaultYear,
            'availableQuarters' => $quartersWithData,
            'allQuartersData' => $allQuartersData,
            'prizeDrawWinners' => $prizeDrawWinners,
        ]);
    }
}
