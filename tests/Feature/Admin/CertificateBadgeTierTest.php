<?php

// tests/Feature/Admin/CertificateBadgeTierTest.php
//
// Teacher badge tier (Bronze/Silver/Gold/Top Award) on the appreciation
// certificate must use the same volume rule as /admin/quarter-end:
//
//   - CANCELLED entries excluded (refund issued, never counted anywhere)
//   - NO_SHOW entries INCLUDED (booking happened, teacher earned the tally)
//   - Fails INCLUDED (booking happened, teacher earned the tally)
//   - Pending (no score yet) INCLUDED (booking happened, score will arrive)
//
// Regression: Daniel Rogers Q1 2026 had 19 passing + 2 NO_SHOW + 1 Fail = 22
// non-cancelled entries → Silver. The cert generator was previously counting
// only passing scores (19 → Bronze), shipping a Bronze cert that disagreed
// with the email body's "22+ candidates / Silver" line.

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 8, 12, 0, 0));
    Storage::fake('local');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function badgeEntry(string $teacher, ?int $score, ?string $notes = null): ExamEntry
{
    $date = Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-BT-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => $date,
    ]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);

    return ExamEntry::create([
        'order_id' => $order->id,
        'instrument_id' => $piano->id,
        'candidate_number' => '1-BT-'.uniqid('', true),
        'candidate_name' => 'Anonymous Candidate',
        'grade' => 'Grade 3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => $date,
        'score' => $score,
        'notes' => $notes,
        'teacher_name' => $teacher,
        'fee' => 50,
    ]);
}

it('counts NO_SHOW + Fails toward badge tier alongside passing scores', function (): void {
    // Daniel Rogers — 19 passing + 2 NO_SHOW + 1 Fail = 22 → Silver
    for ($i = 0; $i < 19; $i++) {
        badgeEntry('Daniel Rogers', 70); // Pass
    }
    badgeEntry('Daniel Rogers', null, 'NO_SHOW');
    badgeEntry('Daniel Rogers', null, 'NO_SHOW');
    badgeEntry('Daniel Rogers', 0, null); // Fail (recorded as TOL had it)

    // batchGenerate redirects with flash on success (back()->with('success'))
    // — assert the redirect AND that no error flash leaked, which proves we
    // hit the cert-writing path rather than an early-return like "No entries
    // with results found for this quarter".
    $this->actingAs($this->admin)
        ->post('/admin/certificates/batch', ['quarter' => 1, 'year' => 2026])
        ->assertRedirect()
        ->assertSessionMissing('error');

    expect(Storage::disk('local')->exists('certificates/2026-Q1/Daniel_Rogers/Daniel_Rogers_Silver_Appreciation.pdf'))
        ->toBeTrue('Silver appreciation cert should exist for 22 non-cancelled entries');
    expect(Storage::disk('local')->exists('certificates/2026-Q1/Daniel_Rogers/Daniel_Rogers_Bronze_Appreciation.pdf'))
        ->toBeFalse('Bronze cert should NOT be generated when teacher qualifies for Silver');
});

it('does NOT count CANCELLED entries toward badge tier', function (): void {
    // Sarah — 19 passing + 5 CANCELLED = 19 effective entries → Bronze (not Silver)
    for ($i = 0; $i < 19; $i++) {
        badgeEntry('Sarah Smith', 70);
    }
    for ($i = 0; $i < 5; $i++) {
        badgeEntry('Sarah Smith', null, 'CANCELLED');
    }

    // batchGenerate redirects with flash on success (back()->with('success'))
    // — assert the redirect AND that no error flash leaked, which proves we
    // hit the cert-writing path rather than an early-return like "No entries
    // with results found for this quarter".
    $this->actingAs($this->admin)
        ->post('/admin/certificates/batch', ['quarter' => 1, 'year' => 2026])
        ->assertRedirect()
        ->assertSessionMissing('error');

    expect(Storage::disk('local')->exists('certificates/2026-Q1/Sarah_Smith/Sarah_Smith_Bronze_Appreciation.pdf'))
        ->toBeTrue('Bronze cert (19 entries) — CANCELLED rows must be excluded');
    expect(Storage::disk('local')->exists('certificates/2026-Q1/Sarah_Smith/Sarah_Smith_Silver_Appreciation.pdf'))
        ->toBeFalse('Silver cert must NOT be generated when CANCELLED entries are wrongly counted');
});

it('still gives a teacher with only passing scores the right tier', function (): void {
    // Tom — exactly 20 passes, no NO_SHOWs, no fails → Silver
    for ($i = 0; $i < 20; $i++) {
        badgeEntry('Tom Hardy', 75);
    }

    // batchGenerate redirects with flash on success (back()->with('success'))
    // — assert the redirect AND that no error flash leaked, which proves we
    // hit the cert-writing path rather than an early-return like "No entries
    // with results found for this quarter".
    $this->actingAs($this->admin)
        ->post('/admin/certificates/batch', ['quarter' => 1, 'year' => 2026])
        ->assertRedirect()
        ->assertSessionMissing('error');

    expect(Storage::disk('local')->exists('certificates/2026-Q1/Tom_Hardy/Tom_Hardy_Silver_Appreciation.pdf'))
        ->toBeTrue();
});

it('does not award any badge when total entries are below 10', function (): void {
    // Jenny — 5 passing + 1 NO_SHOW = 6 entries → no badge
    for ($i = 0; $i < 5; $i++) {
        badgeEntry('Jenny Capstick', 70);
    }
    badgeEntry('Jenny Capstick', null, 'NO_SHOW');

    // batchGenerate redirects with flash on success (back()->with('success'))
    // — assert the redirect AND that no error flash leaked, which proves we
    // hit the cert-writing path rather than an early-return like "No entries
    // with results found for this quarter".
    $this->actingAs($this->admin)
        ->post('/admin/certificates/batch', ['quarter' => 1, 'year' => 2026])
        ->assertRedirect()
        ->assertSessionMissing('error');

    foreach (['Bronze', 'Silver', 'Gold', 'Top_Award'] as $tier) {
        expect(Storage::disk('local')->exists("certificates/2026-Q1/Jenny_Capstick/Jenny_Capstick_{$tier}_Appreciation.pdf"))
            ->toBeFalse("No {$tier} cert should be generated for a teacher with 6 entries");
    }
});
