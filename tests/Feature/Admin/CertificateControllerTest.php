<?php

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────

function certsAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * Quickly build an order + entry in a specific quarter, owned by a teacher_name.
 */
function makeCertEntry(string $teacherName, int $year, int $month, array $attrs = []): ExamEntry
{
    $date = Carbon::create($year, $month, 15);

    $order = Order::create([
        'trinity_order_number' => '1-CERTS-' . uniqid('', true),
        'delivery_method'      => 'F2F',
        'subject_area'         => 'Music',
        'candidates'           => 1,
        'order_status'         => 'Completed',
        'requested_start_date' => $date,
    ]);

    return ExamEntry::create(array_merge([
        'order_id'       => $order->id,
        'teacher_name'   => $teacherName,
        'candidate_name' => 'Student ' . uniqid(),
        'grade'          => '1',
        'subject_area'   => 'Music',
        'delivery_method' => 'F2F',
        'score'          => 80,
        'exam_date'      => $date,
    ], $attrs));
}

// ──────────────────────────────────────────
// Access control
// ──────────────────────────────────────────

test('guests cannot access the certificate generator', function () {
    $this->get('/admin/certificates')->assertRedirect(route('login'));
});

test('teachers cannot access the certificate generator', function () {
    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->get('/admin/certificates')
        ->assertForbidden();
});

test('admin can view the certificate generator', function () {
    $this->actingAs(certsAdmin())
        ->get('/admin/certificates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Certificates/Index'));
});

// ──────────────────────────────────────────
// Quarter scoping
// ──────────────────────────────────────────

test('student and teacher lists are scoped to the selected quarter', function () {
    // Mrs A: 2 entries in Q1, 0 in Q2
    makeCertEntry('Mrs A', 2026, 2); // Q1
    makeCertEntry('Mrs A', 2026, 3); // Q1
    // Mr B: 0 in Q1, 1 in Q2
    makeCertEntry('Mr B', 2026, 5);  // Q2

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('teachers', 1)
            ->where('teachers.0.name', 'Mrs A')
            ->where('teachers.0.candidates_count', 2)
            ->has('students', 2)
        );

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=2&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('teachers', 1)
            ->where('teachers.0.name', 'Mr B')
            ->where('teachers.0.candidates_count', 1)
        );
});

test('teachers prop is always serialised as an array, never an object', function () {
    // Regression: `->filter()` preserves keys; without `->values()` the
    // JSON payload becomes an object and the Vue page crashes with
    // `$props.teachers.filter is not a function`.
    makeCertEntry('Mrs A', 2026, 2);
    makeCertEntry('Mr B', 2026, 2);
    makeCertEntry('Mr C', 2026, 2);

    $response = $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026');

    $props = $response->viewData('page')['props'];
    expect($props['teachers'])->toBeArray();
    // A PHP associative array with sparse keys would serialise as an object
    // in Inertia/JSON. Sequential integer keys keep it as an array.
    expect(array_keys($props['teachers']))->toEqual(range(0, count($props['teachers']) - 1));
});

// ──────────────────────────────────────────
// Per-quarter badge tier (NOT all-time)
// ──────────────────────────────────────────

test('badge tier is calculated per quarter, not all-time', function () {
    $name = 'Mrs Power';

    // 40 entries in Q1 — should be Top Award for Q1
    for ($i = 0; $i < 40; $i++) {
        makeCertEntry($name, 2026, 1);
    }
    // 5 entries in Q2 — should NOT earn a Q2 badge, even though she has 45 all-time
    for ($i = 0; $i < 5; $i++) {
        makeCertEntry($name, 2026, 4);
    }

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('teachers.0.name', $name)
            ->where('teachers.0.tier', 'Top Award')
        );

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=2&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('teachers.0.name', $name)
            ->where('teachers.0.tier', null)
        );
});

test('badge tier thresholds match the spec', function () {
    makeCertEntry('T Bronze', 2026, 1); // will stack to 10 below
    for ($i = 0; $i < 9; $i++) makeCertEntry('T Bronze', 2026, 1);

    for ($i = 0; $i < 20; $i++) makeCertEntry('T Silver', 2026, 1);
    for ($i = 0; $i < 30; $i++) makeCertEntry('T Gold', 2026, 1);
    for ($i = 0; $i < 40; $i++) makeCertEntry('T Top', 2026, 1);
    for ($i = 0; $i < 9; $i++)  makeCertEntry('T NoBadge', 2026, 1);

    $page = $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertOk()
        ->viewData('page')['props'];

    $byName = collect($page['teachers'])->keyBy('name');
    expect($byName['T Bronze']['tier'])->toBe('Bronze');
    expect($byName['T Silver']['tier'])->toBe('Silver');
    expect($byName['T Gold']['tier'])->toBe('Gold');
    expect($byName['T Top']['tier'])->toBe('Top Award');
    expect($byName['T NoBadge']['tier'])->toBeNull();
});

// ──────────────────────────────────────────
// Cancelled entries
// ──────────────────────────────────────────

test('cancelled entries are excluded from teacher counts', function () {
    makeCertEntry('Mrs A', 2026, 2, ['notes' => null]);
    makeCertEntry('Mrs A', 2026, 2, ['notes' => null]);
    makeCertEntry('Mrs A', 2026, 2, ['notes' => 'CANCELLED']);

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('teachers.0.candidates_count', 2) // cancelled one skipped
        );
});

test('cancelled entries are excluded from the student list', function () {
    makeCertEntry('Mrs A', 2026, 2, ['notes' => null]);
    makeCertEntry('Mrs A', 2026, 2, ['notes' => 'CANCELLED']);

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page->has('students', 1));
});

// ──────────────────────────────────────────
// Format param validation
// ──────────────────────────────────────────

test('student cert format param only accepts png or pdf', function () {
    $entry = makeCertEntry('Mrs A', 2026, 2);

    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/student', [
            'entry_id' => $entry->id,
            'template' => 'Bravo Certificate',
            'format'   => 'webp',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['format']);
});
