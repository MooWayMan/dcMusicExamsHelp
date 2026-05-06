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
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
            ->get()
            ->filter(function ($entry) use ($start, $end) {
                $date = $entry->exam_date ?? $entry->order?->requested_start_date;
                return $date && Carbon::parse($date)->between($start, $end);
            })
            ->sortByDesc('score');

        // Scored entries only (for top scorers + summary counts)
        $scoredEntries = $entries->filter(fn ($e) => $e->score !== null);

        // Top scorers — TWO data sources, in this order of preference:
        //
        //   1. A TopScorerPublication snapshot (from /admin/quarter-end's
        //      "Publish top-scorer awards" button). This is the canonical
        //      published list — stable, doesn't shuffle when late scores
        //      come in.
        //
        //   2. Live calculation from this quarter's entries — only used
        //      when no snapshot exists AND the quarter is "naturally"
        //      finalised (prize draw run + no pending). Bands match
        //      ShowHallOfFame: Distinction ≥ 87, Merit 75-86.
        //
        // Returns the four-award structure: { initial_5, '6_8' } each with
        // { distinction[], merit[] }, where each leaf is an array of tied
        // winners. Empty arrays mean nobody hit that band in that group.
        $hasPending = $entries->contains(fn ($e) => $e->score === null);
        $quarterFinalised = PrizeDraw::where('quarter', $quarter)
            ->where('year', $year)
            ->exists();

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
        } elseif ($quarterFinalised && ! $hasPending) {
            // No publication, but quarter is naturally finalised — live calc.
            $shapeWinner = fn (ExamEntry $e) => [
                'name'           => $this->displayName($e),
                'full_name'      => $e->show_full_name ? $e->candidate_name : null,
                'show_full_name' => (bool) $e->show_full_name,
                'score'          => $e->score,
                'instrument'     => $e->instrument?->name,
                'grade'          => $e->grade,
            ];
            $topScorers = \App\Support\TopScorers::calculate($scoredEntries, $shapeWinner);
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
                'instrument' => $e->instrument?->name ?? '—',
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
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED');
            })
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
                'instrument' => $d->winner_instrument,
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
