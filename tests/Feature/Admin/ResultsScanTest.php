<?php

// tests/Feature/Admin/ResultsScanTest.php

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** One transcribed candidate on the given order. */
function scanCandidate(string $orderNumber, array $overrides = []): array
{
    return array_merge([
        'subject' => 'Piano',
        'grade' => 'Grade 2',
        'candidate_name' => 'Chloe Roberts',
        'candidate_id' => '1823317',
        'order_number' => $orderNumber,
        'exam_date' => '2026-07-09',
        'sections' => [
            ['label' => 'Piece 1', 'mark' => 19, 'max' => 22],
            ['label' => 'Piece 2', 'mark' => 15, 'max' => 22],
            ['label' => 'Piece 3', 'mark' => 15, 'max' => 22],
            ['label' => 'Technical Work', 'mark' => 10, 'max' => 14],
            ['label' => 'Test 1', 'mark' => 6, 'max' => 10],
            ['label' => 'Test 2', 'mark' => 8, 'max' => 10],
        ],
        'examiner_total' => 73,
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────────
// Route guarding
// ──────────────────────────────────────────────────────────────────

test('GET /admin/results-scan returns 200 for an authenticated admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/results-scan')->assertOk();
});

test('GET /admin/results-scan is forbidden for a non-admin', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)->get('/admin/results-scan')->assertForbidden();
});

// ──────────────────────────────────────────────────────────────────
// preview — checks + match status
// ──────────────────────────────────────────────────────────────────

test('preview runs the checks and reports match status', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $res = $this->actingAs($admin)->postJson('/admin/results-scan/preview', [
        'candidates' => [scanCandidate('NOPE-000')],
    ]);

    $res->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('candidates.0.section_sum', 73)
        ->assertJsonPath('candidates.0.checks_pass', true)
        ->assertJsonPath('candidates.0.match.order_found', false);
});

// ──────────────────────────────────────────────────────────────────
// commit — fills, creates, and is non-destructive
// ──────────────────────────────────────────────────────────────────

test('commit fills the score onto a matching entry with an empty result', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->faceToFace()->create(['trinity_order_number' => 'ORD-1']);
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1823317',
        'candidate_name' => 'Chloe Roberts',
        'score' => null,
    ]);

    $res = $this->actingAs($admin)->postJson('/admin/results-scan/commit', [
        'candidates' => [scanCandidate('ORD-1')],
    ]);

    $res->assertOk()->assertJsonPath('updated', 1);

    $entry->refresh();
    expect($entry->score)->toBe(73);
    expect($entry->result)->toBe('Pass');
    expect($entry->exam_date?->toDateString())->toBe('2026-07-09');
});

test('commit stores the full report (piece names + comments) on the entry', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->faceToFace()->create(['trinity_order_number' => 'ORD-R']);
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1823317',
        'candidate_name' => 'Chloe Roberts',
        'score' => null,
    ]);

    $candidate = scanCandidate('ORD-R');
    $candidate['sections'][0]['comment'] = 'Hand independence was well managed.';
    $candidate['general_comments'] = 'A promising exam.';

    $this->actingAs($admin)->postJson('/admin/results-scan/commit', [
        'candidates' => [$candidate],
    ])->assertOk()->assertJsonPath('updated', 1);

    $entry->refresh();
    expect($entry->report)->toBeArray();
    expect($entry->report['band'])->toBe('Pass');
    expect($entry->report['general_comments'])->toBe('A promising exam.');
    expect($entry->report['sections'][0]['label'])->toBe('Piece 1');
    expect($entry->report['sections'][0]['comment'])->toBe('Hand independence was well managed.');
});

test('commit creates a minimal entry when the order exists but the candidate does not', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Order::factory()->faceToFace()->create(['trinity_order_number' => 'ORD-2']);

    $res = $this->actingAs($admin)->postJson('/admin/results-scan/commit', [
        'candidates' => [scanCandidate('ORD-2', ['candidate_name' => 'New Person', 'candidate_id' => '999'])],
    ]);

    $res->assertOk()->assertJsonPath('created', 1);

    $entry = ExamEntry::where('candidate_name', 'New Person')->first();
    expect($entry)->not->toBeNull();
    expect($entry->score)->toBe(73);
    expect($entry->source)->toBe('f2f_results_scan');
});

test('commit never overwrites an entry that already has a result', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->faceToFace()->create(['trinity_order_number' => 'ORD-3']);
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1823317',
        'candidate_name' => 'Chloe Roberts',
        'score' => 90,
        'result' => 'Distinction',
    ]);

    $res = $this->actingAs($admin)->postJson('/admin/results-scan/commit', [
        'candidates' => [scanCandidate('ORD-3')],
    ]);

    $res->assertOk()->assertJsonPath('skipped', 1)->assertJsonPath('updated', 0);

    $entry->refresh();
    expect($entry->score)->toBe(90);
    expect($entry->result)->toBe('Distinction');
});

test('commit reports an order it cannot find and writes nothing', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $res = $this->actingAs($admin)->postJson('/admin/results-scan/commit', [
        'candidates' => [scanCandidate('MISSING-1')],
    ]);

    $res->assertOk()
        ->assertJsonPath('updated', 0)
        ->assertJsonPath('created', 0)
        ->assertJsonPath('skipped', 1);

    expect(ExamEntry::count())->toBe(0);
    expect($res->json('warnings'))->not->toBeEmpty();
});
