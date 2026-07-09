<?php

// tests/Feature/Admin/QuarterComparisonTest.php

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Quarter Comparison — per-quarter aggregates
// ──────────────────────────────────────────
// Anchored on 1 May 2026 (Q2) so "current quarter" is deterministic. Default
// view = current year (2026), quarters ascending → quarters.0 = Q1 2026.

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function comparisonEntry(array $attrs): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15); // Q1 2026
    $order = Order::create([
        'trinity_order_number' => '1-QC-' . uniqid('', true),
        'delivery_method' => $attrs['delivery_method'] ?? 'Digital',
        'subject_area' => $attrs['subject_area'] ?? 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Test Candidate',
        'grade' => 'Grade 1',
        'exam_date' => $date,
    ], $attrs));
}

test('digital commission is 20% and F2F is 28% gross', function () {
    comparisonEntry(['delivery_method' => 'Digital', 'subject_area' => 'Classical and Jazz Technical', 'fee' => 100]);
    comparisonEntry(['delivery_method' => 'Default', 'subject_area' => 'Music', 'fee' => 100]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->component('admin/QuarterComparison/Index')
            ->where('quarters.0.short_label', 'Q1 2026')
            ->where('quarters.0.dg_candidates', 1)
            ->where('quarters.0.f2f_candidates', 1)
            ->where('quarters.0.total_fees', fn ($v) => (float) $v === 200.0)
            // 20% of 100 + 28% of 100 = 48.00
            ->where('quarters.0.total_commission', fn ($v) => (float) $v === 48.0));
});

test('digital theory commission is 12.5% and counts as DG', function () {
    comparisonEntry(['delivery_method' => 'DigitalTheory', 'subject_area' => 'Music Theory', 'fee' => 40]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('quarters.0.dg_candidates', 1)
            ->where('quarters.0.f2f_candidates', 0)
            ->where('quarters.0.total_commission', fn ($v) => (float) $v === 5.0)); // 12.5% of 40
});

test('the four exam-type pills bucket by type and delivery', function () {
    comparisonEntry(['delivery_method' => 'Digital', 'subject_area' => 'Classical and Jazz Technical', 'fee' => 10]);
    comparisonEntry(['delivery_method' => 'Default', 'subject_area' => 'Classical and Jazz', 'fee' => 10]);
    comparisonEntry(['delivery_method' => 'Digital', 'subject_area' => 'Rock and Pop', 'fee' => 10]);
    comparisonEntry(['delivery_method' => 'Default', 'subject_area' => 'Rock and Pop', 'fee' => 10]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('quarters.0.exam_types.cj_dg', 1)
            ->where('quarters.0.exam_types.cj_f2f', 1)
            ->where('quarters.0.exam_types.rp_dg', 1)
            ->where('quarters.0.exam_types.rp_f2f', 1));
});

test('instrument pills keep R&P and classical guitar separate', function () {
    $classicalGuitar = Instrument::create(['name' => 'Guitar', 'family' => 'Strings']);
    $rpGuitar = Instrument::create(['name' => 'Guitar (Rock/Pop)', 'family' => 'Strings']);

    comparisonEntry(['instrument_id' => $classicalGuitar->id, 'subject_area' => 'Classical and Jazz', 'fee' => 10]);
    comparisonEntry(['instrument_id' => $rpGuitar->id, 'subject_area' => 'Rock and Pop', 'delivery_method' => 'Default', 'fee' => 10]);
    comparisonEntry(['instrument_id' => $rpGuitar->id, 'subject_area' => 'Rock and Pop', 'delivery_method' => 'Default', 'fee' => 10]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            // Sorted by count desc → R&P guitar (2) first, classical guitar (1) second.
            ->where('quarters.0.instruments.0.name', 'Guitar (Rock/Pop)')
            ->where('quarters.0.instruments.0.count', 2)
            ->where('quarters.0.instruments.1.name', 'Guitar')
            ->where('quarters.0.instruments.1.count', 1));
});

test('teacher_count is the distinct number of crediting teachers', function () {
    comparisonEntry(['teacher_name' => 'Megan Price', 'fee' => 10]);
    comparisonEntry(['teacher_name' => 'Megan Price', 'fee' => 10]);
    comparisonEntry(['teacher_name' => 'Paul Sheridan', 'fee' => 10]);
    comparisonEntry(['teacher_name' => null, 'fee' => 10]); // blank → not counted

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page->where('quarters.0.teacher_count', 2));
});

test('cancelled entries are excluded but no-shows are included', function () {
    comparisonEntry(['notes' => ExamEntry::NOTE_CANCELLED, 'fee' => 100, 'delivery_method' => 'Digital']);
    comparisonEntry(['notes' => ExamEntry::NOTE_NO_SHOW, 'fee' => 100, 'delivery_method' => 'Digital']);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('quarters.0.dg_candidates', 1) // only the NO_SHOW
            ->where('quarters.0.total_commission', fn ($v) => (float) $v === 20.0));
});

test('an entry lands in the correct quarter bucket', function () {
    comparisonEntry(['exam_date' => Carbon::create(2026, 4, 10), 'fee' => 10]); // Q2 2026

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('quarters.0.short_label', 'Q1 2026')
            ->where('quarters.0.total_candidates', 0)
            ->where('quarters.1.short_label', 'Q2 2026')
            ->where('quarters.1.total_candidates', 1));
});

test('year selector controls which quarters are returned', function () {
    // Default → current year (2026), all four quarters ascending.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('year', 2026)
            ->has('quarters', 4)
            ->where('quarters.0.short_label', 'Q1 2026')
            ->where('quarters.3.short_label', 'Q4 2026'));

    // A specific past year shows its four quarters even with no data.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison?year=2025')
        ->assertInertia(fn ($page) => $page
            ->where('year', 2025)
            ->has('quarters', 4)
            ->where('quarters.0.short_label', 'Q1 2025'));

    // "All years" runs from the earliest data year up to the current quarter
    // (no data here → just the current year, capped at Q2).
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison?year=all')
        ->assertInertia(fn ($page) => $page
            ->where('year', 'all')
            ->has('quarters', 2));
});

test('available years include data years plus the current year', function () {
    comparisonEntry(['exam_date' => Carbon::create(2025, 8, 10), 'fee' => 10]); // Q3 2025

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page->where('availableYears', [2025, 2026]));
});

test('the method filter restricts the comparison to one delivery method', function () {
    comparisonEntry(['delivery_method' => 'Digital', 'subject_area' => 'Music', 'fee' => 100]);
    comparisonEntry(['delivery_method' => 'Default', 'subject_area' => 'Music', 'fee' => 100]);

    // Default → both methods counted.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison')
        ->assertInertia(fn ($page) => $page
            ->where('method', '')
            ->where('quarters.0.dg_candidates', 1)
            ->where('quarters.0.f2f_candidates', 1));

    // Digital only → F2F dropped, commission = 20% of 100.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison?method=digital')
        ->assertInertia(fn ($page) => $page
            ->where('method', 'digital')
            ->where('quarters.0.dg_candidates', 1)
            ->where('quarters.0.f2f_candidates', 0)
            ->where('quarters.0.total_commission', fn ($v) => (float) $v === 20.0));

    // F2F only → Digital dropped, commission = 28% of 100.
    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison?method=f2f')
        ->assertInertia(fn ($page) => $page
            ->where('method', 'f2f')
            ->where('quarters.0.dg_candidates', 0)
            ->where('quarters.0.f2f_candidates', 1)
            ->where('quarters.0.total_commission', fn ($v) => (float) $v === 28.0));
});

test('an unknown method value falls back to all', function () {
    comparisonEntry(['delivery_method' => 'Digital', 'fee' => 10]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-comparison?method=banana')
        ->assertInertia(fn ($page) => $page
            ->where('method', '')
            ->where('quarters.0.dg_candidates', 1));
});

test('non-admin cannot reach quarter comparison', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/admin/quarter-comparison')
        ->assertStatus(403);
});
