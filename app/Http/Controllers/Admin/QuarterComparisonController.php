<?php

// app/Http/Controllers/Admin/QuarterComparisonController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Quarter Comparison" — side-by-side quarterly performance so Paul can see
 * Q1 vs Q2 (vs older) at a glance, which the single-quarter Orders / Quarter
 * End pages can't do.
 *
 * Per quarter we report:
 *   • Digital (DG) vs Face-to-Face (F2F) candidate counts
 *   • Total fees and total commission
 *       - Digital     → 20%   (DigitalTheory → 12.5%)
 *       - Face-to-Face → 28%  GROSS (before venue/tuning/steward costs)
 *   • Number of crediting teachers
 *   • Instrument-count pills (R&P instruments are already distinct at the
 *     instrument-name level: "Guitar (Rock/Pop)" ≠ "Guitar", etc.)
 *   • Four exam-type pills: C&J DG, C&J F2F, R&P DG, R&P F2F
 *
 * Grouped by year: pick a year to see its four quarters (Q1–Q4), or
 * "All years" for every quarter that has data. Defaults to the current year.
 *
 * Read-only + display-only: nothing here writes to the DB or feeds the
 * prize draw / recognition — it's a reporting view over existing entries.
 */
class QuarterComparisonController extends Controller
{
    /** F2F commission is taken at 28% GROSS per Paul's LAR terms. */
    private const F2F_GROSS_RATE = 0.28;

    public function index(Request $request): Response
    {
        $currentQuarter = (int) ceil(now()->month / 3);
        $currentYear = (int) now()->year;
        $now = now()->endOfDay();

        // Non-cancelled entries only (CANCELLED = refunded, earned nothing).
        // NO_SHOW stays in — the booking happened, fee + commission were real.
        $entries = ExamEntry::with(['instrument:id,name', 'order:id,requested_start_date,delivery_method'])
            ->where(function ($query) {
                $query->whereNull('notes')->orWhere('notes', '!=', ExamEntry::NOTE_CANCELLED);
            })
            ->get();

        // Years that actually have (non-future) entries, plus the current year
        // so the toggle always offers "this year" even before any exams land.
        // Built with a plain map to sidestep Eloquent Collection::unique()
        // (which would try to call getKey() on the scalar year values).
        $yearMap = [$currentYear => $currentYear];
        foreach ($entries as $entry) {
            $date = $entry->exam_date ?? $entry->order?->requested_start_date;
            if ($date && (int) $date->year <= $currentYear) {
                $yearMap[(int) $date->year] = (int) $date->year;
            }
        }
        ksort($yearMap);
        $availableYears = array_values($yearMap);

        // Resolve the selection: a specific year (default = current), or 'all'.
        $yearParam = strtolower((string) $request->query('year', (string) $currentYear));
        $isAll = $yearParam === 'all';
        $selectedYear = $isAll
            ? 'all'
            : (is_numeric($yearParam) ? (int) $yearParam : $currentYear);

        // Build the (year, quarter) buckets in ascending order (Q1 → Q4).
        $buckets = [];
        if ($isAll) {
            $minYear = ! empty($availableYears) ? min($availableYears) : $currentYear;
            for ($y = $minYear; $y <= $currentYear; $y++) {
                $maxQ = $y === $currentYear ? $currentQuarter : 4;
                for ($q = 1; $q <= $maxQ; $q++) {
                    $buckets["{$y}-{$q}"] = $this->emptyBucket($y, $q);
                }
            }
        } else {
            for ($q = 1; $q <= 4; $q++) {
                $buckets["{$selectedYear}-{$q}"] = $this->emptyBucket((int) $selectedYear, $q);
            }
        }

        foreach ($entries as $entry) {
            $date = $entry->exam_date ?? $entry->order?->requested_start_date;
            if (! $date || $date->gt($now)) {
                continue;
            }

            $bucketQuarter = (int) ceil($date->month / 3);
            $key = "{$date->year}-{$bucketQuarter}";
            if (! isset($buckets[$key])) {
                continue;
            }

            $method = $entry->delivery_method ?? $entry->order?->delivery_method ?? '';
            $isDigital = str_starts_with(strtolower((string) $method), 'digital');
            $fee = (float) ($entry->fee ?? 0);
            $rate = $this->commissionRate($method);
            $isRockPop = str_contains(strtolower((string) $entry->subject_area), 'rock and pop');

            $b = &$buckets[$key];
            $b['total_fees'] += $fee;
            $b['total_commission'] += $fee * $rate;

            if ($isDigital) {
                $b['dg_candidates']++;
            } else {
                $b['f2f_candidates']++;
            }

            // Four exam-type pills.
            $typeKey = ($isRockPop ? 'rp_' : 'cj_') . ($isDigital ? 'dg' : 'f2f');
            $b['exam_types'][$typeKey]++;

            // Instrument pills.
            $instrument = $entry->instrument?->name ?? 'Unknown';
            $b['instruments'][$instrument] = ($b['instruments'][$instrument] ?? 0) + 1;

            // Teacher tally (distinct non-empty teacher_name).
            $teacher = trim((string) $entry->teacher_name);
            if ($teacher !== '') {
                $b['teacher_set'][strtolower($teacher)] = true;
            }

            unset($b);
        }

        // Shape for the front-end.
        $quarters = collect($buckets)->map(function ($b) {
            arsort($b['instruments']);

            return [
                'label' => $this->quarterLabel($b['quarter'], $b['year']),
                'short_label' => "Q{$b['quarter']} {$b['year']}",
                'year' => $b['year'],
                'quarter' => $b['quarter'],
                'dg_candidates' => $b['dg_candidates'],
                'f2f_candidates' => $b['f2f_candidates'],
                'total_candidates' => $b['dg_candidates'] + $b['f2f_candidates'],
                'total_fees' => round($b['total_fees'], 2),
                'total_commission' => round($b['total_commission'], 2),
                'teacher_count' => count($b['teacher_set']),
                'exam_types' => $b['exam_types'],
                'instruments' => collect($b['instruments'])
                    ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return Inertia::render('admin/QuarterComparison/Index', [
            'quarters' => $quarters,
            'year' => $isAll ? 'all' : (int) $selectedYear,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Commission rate for a delivery method. Digital practical 20%, digital
     * theory 12.5%, everything else (Face-to-Face / "Default") 28% gross.
     */
    private function commissionRate(string $method): float
    {
        return match (strtolower($method)) {
            'digital' => 0.20,
            'digitaltheory' => 0.125,
            default => self::F2F_GROSS_RATE,
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyBucket(int $year, int $quarter): array
    {
        return [
            'year' => $year,
            'quarter' => $quarter,
            'dg_candidates' => 0,
            'f2f_candidates' => 0,
            'total_fees' => 0.0,
            'total_commission' => 0.0,
            'teacher_set' => [],
            'instruments' => [],
            'exam_types' => [
                'cj_dg' => 0,
                'cj_f2f' => 0,
                'rp_dg' => 0,
                'rp_f2f' => 0,
            ],
        ];
    }

    private function quarterLabel(int $quarter, int $year): string
    {
        $suffix = match ($quarter) {
            1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th',
            default => '?',
        };

        return "{$suffix} Quarter {$year}";
    }
}
