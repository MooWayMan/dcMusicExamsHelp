<?php

// app/Http/Controllers/ThankYouController.php

namespace App\Http\Controllers;

use App\Models\ExamEntry;
use App\Models\PrizeDraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThankYouController extends Controller
{
    /**
     * GDPR-safe display name: "Seth B" unless parent has opted in.
     */
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

        // Top scorers — only shown once the quarter has been finalised (real draw exists)
        $quarterFinalised = PrizeDraw::where('quarter', $quarter)
            ->where('year', $year)
            ->exists();

        $hallOfFameEntries = collect();

        if ($quarterFinalised) {
            $topDistinction = $scoredEntries->where('score', '>=', 87)->first();
            $topMerit = $scoredEntries->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->first();

            if ($topDistinction) {
                $hallOfFameEntries->push([
                    'name' => $this->displayName($topDistinction),
                    'instrument' => $topDistinction->instrument?->name ?? '—',
                    'grade' => $topDistinction->grade,
                    'score' => $topDistinction->score,
                    'result' => 'Distinction',
                    'award' => 'Showstopper',
                    'certificate' => 'Showstopper Certificate + Gift Token',
                ]);
            }
            if ($topMerit) {
                $hallOfFameEntries->push([
                    'name' => $this->displayName($topMerit),
                    'instrument' => $topMerit->instrument?->name ?? '—',
                    'grade' => $topMerit->grade,
                    'score' => $topMerit->score,
                    'result' => 'Merit',
                    'award' => 'Centre Stage',
                    'certificate' => 'Centre Stage Certificate + Gift Token',
                ]);
            }
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

        return [
            'quarter' => $quarter,
            'year' => $year,
            'label' => "Q{$quarter} {$year}",
            'hallOfFameEntries' => $hallOfFameEntries->toArray(),
            'thankYouEntries' => $thankYouEntries,
            'summary' => [
                'distinctions' => $distinctions,
                'merits' => $merits,
                'total' => $entries->count(),
            ],
        ];
    }

    public function __invoke(Request $request)
    {
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        // Build list of quarters that have data (using exam_date OR order's requested_start_date)
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
            ->unique(fn ($q) => "{$q['quarter']}-{$q['year']}")
            ->sortBy([['year', 'asc'], ['quarter', 'asc']])
            ->values()
            ->toArray();

        // Always include the current quarter in the tabs even if empty
        $currentExists = collect($quartersWithData)->contains(fn ($q) => $q['quarter'] === $currentQuarter && $q['year'] === $currentYear);
        if (! $currentExists) {
            array_unshift($quartersWithData, ['quarter' => $currentQuarter, 'year' => $currentYear]);
        }

        // Build data for ALL quarters so the frontend can switch without page reload
        $allQuartersData = collect($quartersWithData)
            ->map(fn ($q) => $this->buildQuarterData($q['quarter'], $q['year']))
            ->values()
            ->toArray();

        // Default to current quarter
        $defaultQuarter = $currentQuarter;
        $defaultYear = $currentYear;

        return Inertia::render('ThankYou', [
            'defaultQuarter' => $defaultQuarter,
            'defaultYear' => $defaultYear,
            'availableQuarters' => $quartersWithData,
            'allQuartersData' => $allQuartersData,
        ]);
    }
}
