<?php

// app/Http/Controllers/ThankYouController.php

namespace App\Http\Controllers;

use App\Models\ExamEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThankYouController extends Controller
{
    public function __invoke(Request $request)
    {
        // Default to the most recent quarter that has results
        // (we're often in a new quarter before digital scores are in)
        if (! $request->has('quarter') && ! $request->has('year')) {
            $latest = ExamEntry::whereNotNull('score')
                ->orderByDesc('exam_date')
                ->first();

            if ($latest && $latest->exam_date) {
                $quarter = (int) ceil($latest->exam_date->month / 3);
                $year = (int) $latest->exam_date->year;
            } else {
                $quarter = (int) ceil(now()->month / 3);
                $year = (int) now()->year;
            }
        } else {
            $quarter = (int) ($request->query('quarter', ceil(now()->month / 3)));
            $year = (int) ($request->query('year', now()->year));
        }

        $startMonth = ($quarter - 1) * 3 + 1;
        $start = \Carbon\Carbon::create($year, $startMonth, 1)->startOfDay();
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
                'name' => $topDistinction->candidate_name,
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
                'name' => $topMerit->candidate_name,
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
            'name' => $e->candidate_name,
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

        return Inertia::render('ThankYou', [
            'currentQuarter' => "Q{$quarter} {$year}",
            'hallOfFameEntries' => $hallOfFameEntries->toArray(),
            'thankYouEntries' => $thankYouEntries,
            'summary' => [
                'distinctions' => $distinctions,
                'merits' => $merits,
                'passes' => $passes,
                'total' => $entries->count(),
            ],
        ]);
    }
}
