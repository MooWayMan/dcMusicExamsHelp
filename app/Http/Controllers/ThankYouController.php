<?php

// app/Http/Controllers/ThankYouController.php

namespace App\Http\Controllers;

use App\Models\ExamEntry;
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

        $entries = ExamEntry::with('instrument')
            ->whereNotNull('score')
            ->whereNotNull('exam_date')
            ->where('show_on_thank_you', true)
            ->where('exam_date', '>=', $start)
            ->where('exam_date', '<=', $end)
            ->orderByDesc('score')
            ->get();

        // Top scorers — highest distinction and highest merit
        $topDistinction = $entries->where('score', '>=', 87)->first();
        $topMerit = $entries->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->first();

        $hallOfFameEntries = collect();
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

        // All entries — grouped by band then alphabetical
        $bandOrder = ['Distinction' => 1, 'Merit' => 2, 'Pass' => 3, 'Below Pass' => 4];

        $thankYouEntries = $entries->map(fn (ExamEntry $e) => [
            'name' => $this->displayName($e),
            'instrument' => $e->instrument?->name ?? '—',
            'grade' => $e->grade,
            'result' => $e->result_band,
            'certificate' => $e->certificate_name,
            '_sortBand' => $bandOrder[$e->result_band] ?? 5,
        ])->sortBy([
            ['_sortBand', 'asc'],
            ['name', 'asc'],
        ])->map(fn ($e) => collect($e)->except('_sortBand')->toArray())
        ->values()->toArray();

        // Summary counts
        $distinctions = $entries->where('score', '>=', 87)->count();
        $merits = $entries->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();

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

        // Build list of quarters that have data
        $quartersWithData = ExamEntry::whereNotNull('score')
            ->whereNotNull('exam_date')
            ->selectRaw("EXTRACT(YEAR FROM exam_date)::int as y, CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0)::int as q")
            ->groupByRaw("EXTRACT(YEAR FROM exam_date), CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0)")
            ->orderByRaw("EXTRACT(YEAR FROM exam_date) ASC, CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0) ASC")
            ->get()
            ->map(fn ($row) => ['quarter' => (int) $row->q, 'year' => (int) $row->y])
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
