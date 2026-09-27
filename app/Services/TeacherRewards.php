<?php

// app/Services/TeacherRewards.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Support\EntryCredit;
use App\Support\PublicName;
use App\Support\QuarterLabel;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What a teacher has won, quarter by quarter, for the Rewards card on their
 * dashboard: their appreciation badge (with the certificate to download),
 * and every gift-token prize for them or their own pupils, with where it has
 * got to. Built 27 Sep 2026 so the Quarter End email's "see your awards"
 * is true.
 *
 * It only reads what is already recorded. The badge is the batch's rule
 * (QuarterCertificateBatch::teacherTierFor), so the badge shown is the
 * certificate given; prizes come from PrizeLedger, the admin Prizes list.
 * Only this teacher's own pupils appear, and a pupil's name is first name
 * plus initial unless they opted in to their full name.
 */
final class TeacherRewards
{
    /** Where a prize has got to, in the teacher's words (PrizeLedger stages). */
    public const STATUS = [
        'email_not_sent' => 'We will email you about this soon',
        'waiting_for_claim' => 'Reply to our email to claim it',
        'send_card' => 'Claimed: your gift card is on its way',
        'not_used' => 'Gift card sent',
        'unclaimed' => 'Not claimed in time',
        'ran_out' => 'Not used in time',
        'done' => 'Used',
    ];

    public function __construct(
        private readonly TeacherEntries $teacherEntries,
        private readonly QuarterCertificateBatch $batch,
        private readonly PrizeLedger $ledger,
    ) {}

    /**
     * Newest quarter first; only quarters with a badge or a prize.
     *
     * @return list<array{quarter: int, year: int, label: string, badge: ?string, certificate: ?string, prizes: list<array<string, string>>}>
     */
    public function forContact(?ExamContact $contact, ?CarbonInterface $today = null): array
    {
        if (! $contact) {
            return [];
        }

        $today ??= now();
        $byQuarter = $this->ownEntriesByQuarter($contact);
        $prizes = $this->prizesByQuarter($contact, $byQuarter);

        $keys = collect($byQuarter->keys())->merge($prizes->keys())->unique();

        return $keys
            ->map(function (string $key) use ($contact, $prizes, $today) {
                [$year, $quarter] = array_map('intval', explode('-', $key));
                $tier = $this->quarterEnded($quarter, $year, $today) ? $this->tier($contact, $quarter, $year) : null;

                return [
                    'quarter' => $quarter,
                    'year' => $year,
                    'label' => QuarterLabel::for($quarter, $year),
                    'badge' => $tier ? str_replace(' Appreciation Certificate', '', $tier) : null,
                    'certificate' => $tier,
                    'prizes' => $prizes->get($key, []),
                ];
            })
            ->filter(fn (array $q) => $q['badge'] !== null || $q['prizes'] !== [])
            ->sortByDesc(fn (array $q) => $q['year'] * 10 + $q['quarter'])
            ->values()
            ->all();
    }

    /** The certificate this teacher earned for the quarter, or null. */
    public function certificateFor(ExamContact $contact, int $quarter, int $year): ?string
    {
        foreach ($this->forContact($contact) as $row) {
            if ($row['quarter'] === $quarter && $row['year'] === $year) {
                return $row['certificate'];
            }
        }

        return null;
    }

    /** @return Collection<string, Collection<int, ExamEntry>> keyed "year-quarter" */
    private function ownEntriesByQuarter(ExamContact $contact): Collection
    {
        $ids = $this->teacherEntries
            ->forContact($contact, Carbon::parse(TeacherEntries::HISTORY_START), now()->addYear())
            ->pluck('exam_entries.id');

        return ExamEntry::with('order:id,requested_start_date')
            ->whereIn('id', $ids)
            ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '!=', 'CANCELLED'))
            ->get()
            ->filter(fn (ExamEntry $e) => $e->exam_date ?? $e->order?->requested_start_date)
            ->groupBy(function (ExamEntry $e) {
                $date = $e->exam_date ?? $e->order->requested_start_date;

                return $date->year.'-'.(int) ceil($date->month / 3);
            });
    }

    private function tier(ExamContact $contact, int $quarter, int $year): ?string
    {
        $entries = $this->batch->entries($quarter, $year);
        $submitters = EntryCredit::submitterNames($entries);
        $name = mb_strtolower(trim((string) $contact->name));

        $scored = $entries
            ->filter(fn (ExamEntry $e) => mb_strtolower(trim(EntryCredit::nameFor($e, $submitters))) === $name)
            ->count();

        return $this->batch->teacherTierFor($quarter, $year, (string) $contact->name, $scored);
    }

    /**
     * @param  Collection<string, Collection<int, ExamEntry>>  $byQuarter
     * @return Collection<string, list<array<string, string>>>
     */
    private function prizesByQuarter(ExamContact $contact, Collection $byQuarter): Collection
    {
        $mine = collect([$contact->name])
            ->merge($contact->isSchoolAdmin() ? $contact->schools()->pluck('name') : [])
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->filter()
            ->all();

        return $this->ledger->rows()
            ->map(function (array $row) use ($mine, $byQuarter) {
                $winner = mb_strtolower(trim((string) $row['winner']));

                if ($row['award_key'] === 'teacher_draw') {
                    $who = in_array($winner, $mine, true) ? 'You' : null;
                } else {
                    $pupil = $byQuarter->get("{$row['year']}-{$row['quarter']}", collect())
                        ->first(fn (ExamEntry $e) => mb_strtolower(trim((string) $e->candidate_name)) === $winner);
                    $who = $pupil ? ($pupil->show_full_name ? $pupil->candidate_name : PublicName::short($pupil->candidate_name)) : null;
                }

                return $who === null ? null : [
                    'key' => "{$row['year']}-{$row['quarter']}",
                    'prize' => [
                        'id' => $row['id'],
                        'who' => $who,
                        'prize' => $row['prize'],
                        'amount' => $row['amount_label'],
                        'status' => self::STATUS[$row['stage']],
                        'use_by' => $row['stage'] === 'not_used' ? $row['expires_label'] : '',
                    ],
                ];
            })
            ->filter()
            ->groupBy('key')
            ->map(fn (Collection $rows) => $rows->pluck('prize')->values()->all());
    }

    private function quarterEnded(int $quarter, int $year, CarbonInterface $today): bool
    {
        return Carbon::create($year, $quarter * 3, 1)->endOfMonth()->lessThan($today);
    }
}
