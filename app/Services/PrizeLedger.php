<?php

// app/Services/PrizeLedger.php

namespace App\Services;

use App\Models\PrizeDraw;
use App\Models\PrizeWorkflow;
use App\Models\TopScorerPublication;
use App\Support\TopScorers;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Every gift-token prize from every quarter and where it has got to, for the
 * admin Prizes list — so Paul never has to open each Quarter End to see what
 * is still owed, still unused, or has run out.
 *
 * A prize exists once it is recorded: a real draw (PrizeDraw) or a published
 * top-scorer award (TopScorerPublication). Progress comes from the same
 * prize_workflow tick boxes Quarter End shows.
 *
 * The 12 months run from the day the winner email went out (sent_at), so a
 * winner told late is never backdated. Rows ticked before sent_at existed fall
 * back to the award date.
 */
class PrizeLedger
{
    public const CLAIM_MONTHS = 12;

    public const STAGE_LABELS = [
        'email_not_sent' => 'Email not sent',
        'send_card' => 'Claimed: buy and send the card',
        'waiting_for_claim' => 'Waiting for them to claim',
        'not_used' => 'Card sent, not used yet',
        'unclaimed' => 'Never claimed: back to the prize fund',
        'ran_out' => 'Not used in 12 months: reclaim the card',
        'done' => 'Used',
    ];

    private const STAGE_ORDER = ['ran_out', 'unclaimed', 'send_card', 'email_not_sent', 'not_used', 'waiting_for_claim', 'done'];

    private const GROUP_LABELS = ['initial_5' => 'Initial–5', '6_8' => 'Grades 6–8'];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(?CarbonInterface $today = null): Collection
    {
        $today ??= now();

        $ticks = PrizeWorkflow::all()->keyBy(
            fn (PrizeWorkflow $r) => self::key($r->quarter, $r->year, $r->award_key, $r->winner_full_name)
        );

        $steps = PrizeWorkflow::stepsForPage();

        return $this->prizes()
            ->map(function (array $prize) use ($ticks, $steps, $today) {
                $tick = $ticks->get(self::key($prize['quarter'], $prize['year'], $prize['award_key'], $prize['winner']));

                return $this->withProgress($prize, $tick, $steps[$prize['award_key']] ?? [], $today);
            })
            ->sortBy([
                fn ($a, $b) => array_search($a['stage'], self::STAGE_ORDER, true) <=> array_search($b['stage'], self::STAGE_ORDER, true),
                fn ($a, $b) => ($a['expires_on'] ?? '9999') <=> ($b['expires_on'] ?? '9999'),
                fn ($a, $b) => [$b['year'], $b['quarter']] <=> [$a['year'], $a['quarter']],
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{quarter: int, year: int, award_key: string, prize: string, winner: string, amount: int, awarded_at: CarbonInterface}>
     */
    private function prizes(): Collection
    {
        $draws = PrizeDraw::all()->map(fn (PrizeDraw $d) => [
            'quarter' => (int) $d->quarter,
            'year' => (int) $d->year,
            'award_key' => "{$d->type}_draw",
            'prize' => $d->type === 'teacher' ? 'Teacher draw' : 'Student draw',
            'winner' => $d->winner_name,
            'amount' => 50,
            'awarded_at' => $d->created_at,
        ]);

        $topScorers = TopScorerPublication::all()->flatMap(function (TopScorerPublication $pub) {
            return collect(TopScorers::flatten($pub->winners ?? []))->map(fn (array $f) => [
                'quarter' => (int) $pub->quarter,
                'year' => (int) $pub->year,
                'award_key' => "{$f['group']}_{$f['band']}",
                'prize' => "{$f['certificate']} (".self::GROUP_LABELS[$f['group']].')',
                'winner' => $f['winner']['full_name'] ?? $f['winner']['name'],
                'amount' => TopScorers::tokenSplit(count($pub->winners[$f['group']][$f['band']] ?? [])),
                'awarded_at' => $pub->published_at,
            ]);
        });

        return $draws->concat($topScorers);
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $steps
     * @return array<string, mixed>
     */
    private function withProgress(array $prize, ?PrizeWorkflow $tick, array $steps, CarbonInterface $today): array
    {
        $keys = array_column($steps, 'key');
        $status = $tick?->status() ?? array_fill_keys(array_keys(PrizeWorkflow::STEP_LABELS), false);

        $expires = null;

        if ($status['used']) {
            $stage = 'done';
        } elseif (! $status['sent']) {
            $stage = 'email_not_sent';
        } else {
            $expires = ($tick->sent_at ?? $prize['awarded_at'])->copy()->addMonthsNoOverflow(self::CLAIM_MONTHS);
            $expired = $today->greaterThan($expires);

            if (in_array('claimed', $keys, true) && ! $status['claimed']) {
                $stage = $expired ? 'unclaimed' : 'waiting_for_claim';
            } elseif (in_array('card_sent', $keys, true) && ! $status['card_sent']) {
                $stage = 'send_card';
            } else {
                $stage = $expired ? 'ran_out' : 'not_used';
            }
        }

        return [
            'id' => self::key($prize['quarter'], $prize['year'], $prize['award_key'], $prize['winner']),
            'quarter' => $prize['quarter'],
            'year' => $prize['year'],
            'quarter_label' => QuarterCertificateBatch::label($prize['quarter'], $prize['year']),
            'award_key' => $prize['award_key'],
            'prize' => $prize['prize'],
            'winner' => $prize['winner'],
            'amount' => $prize['amount'],
            'amount_label' => '£'.$prize['amount'],
            'steps' => $steps,
            'status' => $status,
            'stage' => $stage,
            'stage_label' => self::STAGE_LABELS[$stage],
            'expires_on' => $expires?->toDateString(),
            'expires_label' => $expires?->format('j M Y') ?? '—',
        ];
    }

    private static function key(int $quarter, int $year, string $awardKey, string $winner): string
    {
        return "{$year}|{$quarter}|{$awardKey}|{$winner}";
    }
}
