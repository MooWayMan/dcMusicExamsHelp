<?php

// app/Services/MinimumWage.php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The UK National Living Wage (aged 21 and over), looked up on GOV.UK.
 *
 * rates() checks GOV.UK's "Current rates" table at most once a day (an hour
 * after a failed check), writes what it finds into minimum_wage_rates, and
 * returns every rate it knows. If GOV.UK cannot be reached or its page changes
 * shape, the last rates found are used, so the edit page never waits on it or
 * breaks because of it.
 */
class MinimumWage
{
    public const SOURCE = 'https://www.gov.uk/national-minimum-wage-rates';

    private const CHECKED_KEY = 'minimum_wage.checked_gov_uk';

    /** @return list<array{from: string, rate: string}> oldest first */
    public function rates(): array
    {
        if (! Cache::has(self::CHECKED_KEY)) {
            $found = $this->refresh();
            Cache::put(self::CHECKED_KEY, true, $found > 0 ? now()->addDay() : now()->addHour());
        }

        return DB::table('minimum_wage_rates')
            ->orderBy('starts_on')
            ->get(['starts_on', 'rate'])
            ->map(fn ($row) => [
                'from' => Carbon::parse($row->starts_on)->format('Y-m-d'),
                'rate' => number_format((float) $row->rate, 2, '.', ''),
            ])
            ->values()
            ->all();
    }

    /** Read GOV.UK and save its rates. Returns how many rates it found (0 = nothing usable). */
    public function refresh(): int
    {
        try {
            $response = Http::timeout(5)->get(self::SOURCE);
        } catch (\Throwable) {
            return 0;
        }

        if (! $response->successful()) {
            return 0;
        }

        $rates = $this->parse($response->body());

        foreach ($rates as $startsOn => $rate) {
            DB::table('minimum_wage_rates')->updateOrInsert(
                ['starts_on' => $startsOn],
                ['rate' => $rate, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        return count($rates);
    }

    /**
     * The "21 and over" column of every rates table on the page, keyed by the
     * date the rate starts: a row headed "April 2026" becomes 2026-04-01.
     *
     * @return array<string, string>
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $rates = [];

        foreach ($xpath->query('//table') as $table) {
            $rows = $xpath->query('.//tr', $table);
            if ($rows->length < 2) {
                continue;
            }

            $column = null;
            foreach ($this->cells($xpath, $rows->item(0)) as $i => $heading) {
                if (stripos($heading, '21 and over') !== false) {
                    $column = $i;
                    break;
                }
            }
            if ($column === null) {
                continue;
            }

            for ($r = 1; $r < $rows->length; $r++) {
                $cells = $this->cells($xpath, $rows->item($r));
                if (! isset($cells[0], $cells[$column])) {
                    continue;
                }
                if (! preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})$/i', $cells[0], $when)) {
                    continue;
                }
                if (! preg_match('/£\s*(\d+(?:\.\d{1,2})?)/u', $cells[$column], $money)) {
                    continue;
                }

                $startsOn = Carbon::parse("1 {$when[1]} {$when[2]}")->format('Y-m-d');
                $rates[$startsOn] = number_format((float) $money[1], 2, '.', '');
            }
        }

        ksort($rates);

        return $rates;
    }

    /** @return list<string> */
    private function cells(DOMXPath $xpath, DOMElement $row): array
    {
        $cells = [];
        foreach ($xpath->query('./th|./td', $row) as $cell) {
            $cells[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
        }

        return $cells;
    }
}
