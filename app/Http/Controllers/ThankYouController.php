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

    public function __invoke(Request $request)
    {
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        // Build list of quarters that have data
        $quartersWithData = ExamEntry::whereNotNull('score')
            ->selectRaw("EXTRACT(YEAR FROM exam_date)::int as y, CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0)::int as q")
            ->groupByRaw("EXTRACT(YEAR FROM exam_date), CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0)")
            ->orderByRaw("EXTRACT(YEAR FROM exam_date) DESC, CEIL(EXTRACT(MONTH FROM exam_date)::int / 3.0) DESC")
            ->get()
            ->map(fn ($row) => ['quarter' => (int) $row->q, 'year' => (int) $row->y])
            ->values()
            ->toArray();

        // Always include the current quarter in the tabs even if empty
        $currentExists = collect($quartersWithData)->contains(fn ($q) => $q['quarter'] === $currentQuarter && $q['year'] === $currentYear);
        if (! $currentExists) {
            array_unshift($quartersWithData, ['quarter' => $currentQuarter, 'year' => $currentYear]);
        }

        // Which quarter to display — default to current quarter
        $quarter = (int) ($request->query('quarter', $currentQuarter));
        $year = (int) ($request->query('year', $currentYear));

        // Query entries for the selected quarter
        $startMonth = ($quarter - 1) * 3 + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(2)->endOfMonth()->endOfDay();

        $entries = ExamEntry::with('instrument')
            ->whereNotNull('score')
            ->where('exam_date', '>=', $start)
            ->where('exam_date', '<=', $end)
            ->orderByDesc('score')
            ->get();

        // Top scorers — highest distinction and highest merit
        $topDistinction = $entries->where('score', '>=', 87)->first();
        $topMerit = $entries->where('score', '>=', 75)->where('score', '<', 87)->first();

        $hallOfFameEntries = collect();
        if ($topDistinction) {
            $hallOfFameEntries->push([
                'name' => $this->displayName($topDistinction),
                'instrument' => $topDistinction->instrument?->name ?? '—',
                'grade' => $topDistinction->grade,
                'score' => $topDistinction->score,
                'result' => 'Distinction',
                'award' => 'Highest Distinction',
                'certificate' => 'Standing Ovation Certificate + Gift Token',
            ]);
        }
        if ($topMerit) {
            $hallOfFameEntries->push([
                'name' => $this->displayName($topMerit),
                'instrument' => $topMerit->instrument?->name ?? '—',
                'grade' => $topMerit->grade,
                'score' => $topMerit->score,
                'result' => 'Merit',
                'award' => 'Highest Merit',
                'certificate' => 'Take a Bow Certificate + Gift Token',
            ]);
        }

        // All entries for the "Every entry counts" table
        $thankYouEntries = $entries->map(fn (ExamEntry $e) => [
            'name' => $this->displayName($e),
            'instrument' => $e->instrument?->name ?? '—',
            'grade' => $e->grade,
            'score' => $e->score,
            'result' => $e->result_band,
            'certificate' => $e->certificate_name,
        ])->values()->toArray();

        // Summary counts
        $distinctions = $entries->where('score', '>=', 87)->count();
        $merits = $entries->filter(fn ($e) => $e->score >= 75 && $e->score < 87)->count();
        $passes = $entries->filter(fn ($e) => $e->score < 75)->count();

        // Nudge to previous quarter if this one has < 10 entries
        $nudge = null;
        if ($entries->count() < 10) {
            // Find the previous quarter that has data
            $prevQuarter = collect($quartersWithData)->first(function ($q) use ($quarter, $year) {
                return ($q['year'] < $year) || ($q['year'] === $year && $q['quarter'] < $quarter);
            });

            if ($prevQuarter) {
                $nudge = [
                    'quarter' => $prevQuarter['quarter'],
                    'year' => $prevQuarter['year'],
                    'label' => "Q{$prevQuarter['quarter']} {$prevQuarter['year']}",
                ];
            }
        }

        return Inertia::render('ThankYou', [
            'currentQuarter' => "Q{$quarter} {$year}",
            'selectedQuarter' => $quarter,
            'selectedYear' => $year,
            'availableQuarters' => $quartersWithData,
            'hallOfFameEntries' => $hallOfFameEntries->toArray(),
            'thankYouEntries' => $thankYouEntries,
            'nudge' => $nudge,
            'summary' => [
                'distinctions' => $distinctions,
                'merits' => $merits,
                'passes' => $passes,
                'total' => $entries->count(),
            ],
        ]);
    }
}
