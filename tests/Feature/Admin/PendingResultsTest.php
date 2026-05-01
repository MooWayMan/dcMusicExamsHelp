<?php

// tests/Feature/Admin/PendingResultsTest.php

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Pending Results — strict definition
// ──────────────────────────────────────────
// "Pending" means an exam whose date has passed but whose score isn't in.
// Future-dated F2F exams are SCHEDULED, not pending — they were inflating
// this list before because the original query only checked `whereNull(score)`.
//
// Selector mirrors QuarterEnd: ?quarter=&year= defaulting to current quarter.
// Test data is anchored on 1 May 2026 (Q2) so "past" / "future" / cross-quarter
// cases are all unambiguous regardless of when the suite is actually run.

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makePendingOrder(Carbon $date): Order
{
    return Order::create([
        'trinity_order_number' => '1-TEST-' . uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);
}

function makePendingEntry(array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 4, 15);
    $order = $attrs['order'] ?? makePendingOrder($date);
    unset($attrs['order']);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Test Candidate',
        'grade' => 'Grade 1',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => $date,
    ], $attrs));
}

test('past-dated unscored entries in the current quarter are pending', function () {
    makePendingEntry([
        'candidate_name' => 'Past Q2 Pending',
        'exam_date' => Carbon::create(2026, 4, 15),
        'score' => null,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/pending-results');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/PendingResults/Index')
        ->where('summary.pending', 1)
        ->where('entries.0.candidate_name', 'Past Q2 Pending'));
});

test('future-dated unscored entries are NOT pending (the bug we fixed)', function () {
    // Future F2F exam — the kind that was inflating the old list.
    makePendingEntry([
        'candidate_name' => 'Future F2F',
        'exam_date' => Carbon::create(2026, 7, 15),
        'score' => null,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/pending-results');

    $response->assertInertia(fn ($page) => $page
        ->where('summary.pending', 0)
        ->where('entries', []));
});

test('cancelled entries are NOT pending even if past-dated and unscored', function () {
    makePendingEntry([
        'candidate_name' => 'Cancelled Past',
        'exam_date' => Carbon::create(2026, 4, 15),
        'score' => null,
        'notes' => 'CANCELLED',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/pending-results');

    $response->assertInertia(fn ($page) => $page->where('summary.pending', 0));
});

test('scored entries are NOT pending', function () {
    makePendingEntry([
        'candidate_name' => 'Already Scored',
        'exam_date' => Carbon::create(2026, 4, 15),
        'score' => 85,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/pending-results');

    $response->assertInertia(fn ($page) => $page->where('summary.pending', 0));
});

test('quarter selector narrows the list to the selected quarter', function () {
    // Q1 entry — past, unscored. Should appear under ?quarter=1 but NOT Q2.
    makePendingEntry([
        'candidate_name' => 'Q1 Pending',
        'exam_date' => Carbon::create(2026, 2, 15),
    ]);
    // Q2 entry — past, unscored. Should appear under default (Q2).
    makePendingEntry([
        'candidate_name' => 'Q2 Pending',
        'exam_date' => Carbon::create(2026, 4, 15),
    ]);

    // Default → Q2
    $this->actingAs($this->admin)
        ->get('/admin/pending-results')
        ->assertInertia(fn ($page) => $page
            ->where('quarter', 2)
            ->where('summary.pending', 1)
            ->where('entries.0.candidate_name', 'Q2 Pending'));

    // ?quarter=1 → Q1 only
    $this->actingAs($this->admin)
        ->get('/admin/pending-results?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('quarter', 1)
            ->where('summary.pending', 1)
            ->where('entries.0.candidate_name', 'Q1 Pending'));
});

test('entries with null exam_date fall back to order requested_start_date', function () {
    // Legacy Q1 import pattern — exam_date never stamped, but the order
    // knows when it was meant to happen. Should still show as pending in Q1.
    $order = makePendingOrder(Carbon::create(2026, 2, 10));

    makePendingEntry([
        'candidate_name' => 'Legacy Q1',
        'exam_date' => null,
        'order' => $order,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/pending-results?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('summary.pending', 1)
            ->where('entries.0.candidate_name', 'Legacy Q1'));
});

test('non-admin cannot reach pending results', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/pending-results')
        ->assertStatus(403);
});

test('guests are redirected to login', function () {
    $this->get('/admin/pending-results')->assertRedirect('/login');
});
