<?php

// app/Support/TopScorers.php

namespace App\Support;

use App\Models\ExamEntry;
use Illuminate\Support\Collection;

/**
 * Top-Scorer Awards — single source of truth.
 *
 * The public Awards banner promises FOUR awards per quarter:
 *   • Highest Distinction — Initial-5
 *   • Highest Distinction — 6-8
 *   • Highest Merit       — Initial-5
 *   • Highest Merit       — 6-8
 *
 * Used by:
 *   - Admin\QuarterEndController       (renders the awards panel + drives
 *                                        per-winner email buttons)
 *   - Admin\CertificateController      (generates Showstopper / Centre Stage
 *                                        certificate PDFs alongside the
 *                                        per-student Bravo / Take a Bow /
 *                                        Standing Ovation pipeline)
 *
 * Bands match ShowHallOfFame: Distinction ≥ 87, Merit 75–86.
 *
 * The returned shape is shaped for direct use as Inertia props:
 *
 *   [
 *     'initial_5' => [
 *       'distinction' => [ ['name' => 'Anna M', ...], ... ],
 *       'merit'       => [ ... ],
 *     ],
 *     '6_8' => [
 *       'distinction' => [ ... ],
 *       'merit'       => [ ... ],
 *     ],
 *   ]
 *
 * Each leaf is an array of ALL candidates tied at the top score in that
 * (group, band) bucket — so a 2-way tie returns two rows, a 3-way tie
 * returns three. The empty array means nobody hit that band in that group.
 */
class TopScorers
{
    /**
     * Calculate top scorers for a quarter.
     *
     * @param  Collection<int, ExamEntry>  $entries  Already-scoped to a quarter.
     * @param  callable                    $shapeWinner  Closure returning an
     *                                                   array shape per winner.
     *                                                   Receives the ExamEntry.
     * @return array{initial_5: array{distinction: array, merit: array}, '6_8': array{distinction: array, merit: array}}
     */
    public static function calculate(Collection $entries, callable $shapeWinner): array
    {
        $withScores = $entries->filter(fn ($e) => $e->score !== null);

        $result = [
            'initial_5' => ['distinction' => [], 'merit' => []],
            '6_8'       => ['distinction' => [], 'merit' => []],
        ];

        foreach (['initial_5', '6_8'] as $group) {
            foreach (['distinction', 'merit'] as $band) {
                $bucket = $withScores->filter(function ($e) use ($group, $band) {
                    return self::groupOf($e->grade) === $group
                        && self::bandOf($e->score) === $band;
                });

                if ($bucket->isEmpty()) {
                    continue;
                }

                $topScore = $bucket->max('score');
                $result[$group][$band] = $bucket
                    ->where('score', $topScore)
                    ->map($shapeWinner)
                    ->values()
                    ->toArray();
            }
        }

        return $result;
    }

    /**
     * Flatten the calculate() result into a flat list of (winner, awardKey)
     * pairs — handy for cert generation where we just want to iterate every
     * winner once with their award type.
     *
     * @return array<int, array{winner: array, group: string, band: string, certificate: string}>
     */
    public static function flatten(array $topScorers): array
    {
        $out = [];
        foreach (['initial_5', '6_8'] as $group) {
            foreach (['distinction', 'merit'] as $band) {
                foreach ($topScorers[$group][$band] ?? [] as $winner) {
                    $out[] = [
                        'winner'      => $winner,
                        'group'       => $group,
                        'band'        => $band,
                        'certificate' => $band === 'distinction' ? 'Showstopper' : 'Centre Stage',
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Grade → group classifier. Initial + Grades 1-5 vs Grades 6-8.
     * Diplomas / certificates / anything else returns null and is excluded
     * from the quarterly award pool.
     */
    public static function groupOf(?string $grade): ?string
    {
        if ($grade === null) {
            return null;
        }
        if ($grade === 'Initial' || in_array((string) $grade, ['1', '2', '3', '4', '5'], true)) {
            return 'initial_5';
        }
        if (in_array((string) $grade, ['6', '7', '8'], true)) {
            return '6_8';
        }
        return null;
    }

    /**
     * Score → award band. Distinction ≥ 87, Merit 75-86. Pass scores (and
     * fails) return null and don't qualify for an award.
     */
    public static function bandOf(?int $score): ?string
    {
        if ($score === null)              return null;
        if ($score >= 87)                 return 'distinction';
        if ($score >= 75 && $score < 87)  return 'merit';
        return null;
    }

    /**
     * Gift token split. Paul's rule (matches public Recognition page):
     *   1 → £20, 2 → £10 each, 3+ → £5 each (minimum £5).
     */
    public static function tokenSplit(int $tieCount): int
    {
        if ($tieCount <= 0) return 0;
        if ($tieCount === 1) return 20;
        if ($tieCount === 2) return 10;
        return 5;
    }
}
