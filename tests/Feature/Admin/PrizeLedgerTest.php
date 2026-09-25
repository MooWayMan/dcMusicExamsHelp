<?php

// tests/Feature/Admin/PrizeLedgerTest.php
//
// /admin/prizes lists every gift-token prize from every quarter and where it
// has got to, so Paul never has to open each Quarter End. The stage and the
// run-out date come from App\Services\PrizeLedger.

use App\Models\PrizeDraw;
use App\Models\PrizeWorkflow;
use App\Models\TopScorerPublication;
use App\Models\User;
use App\Services\PrizeLedger;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function prizeLedgerDraw(string $type, int $quarter, int $year, string $winner, string $drawnAt, int $by): PrizeDraw
{
    $draw = PrizeDraw::create([
        'type' => $type,
        'quarter' => $quarter,
        'year' => $year,
        'winner_name' => $winner,
        'total_tickets' => 10,
        'drawn_by' => $by,
    ]);
    $draw->forceFill(['created_at' => Carbon::parse($drawnAt)])->save();

    return $draw;
}

function prizeLedgerTicks(int $quarter, int $year, string $awardKey, string $winner, array $ticks, ?string $sentAt = null): void
{
    PrizeWorkflow::create(array_merge([
        'quarter' => $quarter,
        'year' => $year,
        'award_key' => $awardKey,
        'winner_full_name' => $winner,
        'sent_at' => $sentAt ? Carbon::parse($sentAt) : null,
    ], $ticks));
}

function prizeLedgerRow(string $winner, string $today = '2026-09-25'): array
{
    return app(PrizeLedger::class)->rows(Carbon::parse($today))->firstWhere('winner', $winner);
}

it('puts a draw nobody has emailed yet in to do, with no run-out date', function (): void {
    prizeLedgerDraw('student', 3, 2026, 'Jim Hazell', '2026-09-20', $this->admin->id);

    $row = prizeLedgerRow('Jim Hazell');

    expect($row['stage'])->toBe('email_not_sent')
        ->and($row['expires_label'])->toBe('—')
        ->and($row['amount_label'])->toBe('£50')
        ->and($row['quarter_label'])->toBe('3rd Quarter 2026')
        ->and($row['prize'])->toBe('Student draw');
});

it('runs the 12 months from the day the email went out, not the draw', function (): void {
    prizeLedgerDraw('student', 2, 2026, 'Isabella White', '2026-07-10', $this->admin->id);
    prizeLedgerTicks(2, 2026, 'student_draw', 'Isabella White', ['sent' => true], '2026-09-25 20:00');

    $row = prizeLedgerRow('Isabella White');

    expect($row['stage'])->toBe('waiting_for_claim')
        ->and($row['expires_label'])->toBe('25 Sep 2027');
});

it('sends an unclaimed prize back to the fund once its 12 months are up', function (): void {
    prizeLedgerDraw('student', 3, 2025, 'Old Winner', '2025-09-20', $this->admin->id);
    prizeLedgerTicks(3, 2025, 'student_draw', 'Old Winner', ['sent' => true], '2025-09-21 09:00');

    expect(prizeLedgerRow('Old Winner')['stage'])->toBe('unclaimed');
});

it('keeps a claimed prize in to do until the card is sent', function (): void {
    prizeLedgerDraw('student', 2, 2026, 'Isabella White', '2026-07-10', $this->admin->id);
    prizeLedgerTicks(2, 2026, 'student_draw', 'Isabella White', ['sent' => true, 'claimed' => true], '2026-09-25 20:00');

    expect(prizeLedgerRow('Isabella White')['stage'])->toBe('send_card');

    PrizeWorkflow::first()->forceFill(['bought' => true, 'card_sent' => true])->save();

    expect(prizeLedgerRow('Isabella White')['stage'])->toBe('not_used');
});

it('flags a teacher draw card that has not been used in 12 months, and clears it once used', function (): void {
    prizeLedgerDraw('teacher', 3, 2025, 'Jenny Capstick', '2025-09-20', $this->admin->id);
    prizeLedgerTicks(3, 2025, 'teacher_draw', 'Jenny Capstick', ['bought' => true, 'sent' => true], '2025-09-21 09:00');

    expect(prizeLedgerRow('Jenny Capstick')['stage'])->toBe('ran_out');

    PrizeWorkflow::first()->forceFill(['used' => true])->save();

    $row = prizeLedgerRow('Jenny Capstick');
    expect($row['stage'])->toBe('done')
        ->and($row['expires_label'])->toBe('—');
});

it('turns each published top scorer into a prize with their share of the token', function (): void {
    TopScorerPublication::create([
        'quarter' => 2,
        'year' => 2026,
        'winners' => [
            'initial_5' => ['distinction' => [
                ['name' => 'Anna M', 'full_name' => 'Anna Martin'],
                ['name' => 'Maya P', 'full_name' => 'Maya Parkinson'],
            ], 'merit' => []],
            '6_8' => ['distinction' => [], 'merit' => []],
        ],
        'finalised_with_pending' => false,
        'pending_count' => 0,
        'published_by' => $this->admin->id,
        'published_at' => Carbon::parse('2026-09-01'),
    ]);
    prizeLedgerTicks(2, 2026, 'initial_5_distinction', 'Anna Martin', ['sent' => true], '2026-09-02 10:00');

    $anna = prizeLedgerRow('Anna Martin');
    $maya = prizeLedgerRow('Maya Parkinson');

    expect($anna['prize'])->toBe('Showstopper (Initial–5)')
        ->and($anna['amount_label'])->toBe('£10')
        ->and($anna['stage'])->toBe('waiting_for_claim')
        ->and($maya['stage'])->toBe('email_not_sent');
});

it('records the day Email sent is ticked, and clears it when unticked', function (): void {
    Carbon::setTestNow('2026-09-25 21:30:00');

    $tick = fn (bool $value) => $this->actingAs($this->admin)->postJson('/admin/quarter-end/toggle-workflow', [
        'quarter' => 2, 'year' => 2026,
        'award_key' => 'student_draw',
        'winner_full_name' => 'Isabella White',
        'step' => 'sent', 'value' => $value,
    ])->assertOk();

    $tick(true);
    expect(PrizeWorkflow::first()->sent_at->toDateString())->toBe('2026-09-25');

    $tick(false);
    expect(PrizeWorkflow::first()->sent_at)->toBeNull();

    Carbon::setTestNow();
});

it('shows an admin every prize on the Prizes page', function (): void {
    prizeLedgerDraw('student', 2, 2026, 'Isabella White', '2026-07-10', $this->admin->id);
    prizeLedgerDraw('teacher', 2, 2026, 'Chris Barlow', '2026-07-10', $this->admin->id);

    $this->actingAs($this->admin)
        ->get('/admin/prizes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Prizes/Index')
            ->has('prizes', 2)
        );
});

it('keeps the Prizes page from anyone signed out', function (): void {
    $this->get('/admin/prizes')->assertRedirect('/login');
});
