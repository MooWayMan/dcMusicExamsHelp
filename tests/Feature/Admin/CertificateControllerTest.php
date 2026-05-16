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

// ──────────────────────────────────────────
// Weekly Send — payload + mark-sent endpoints
// ──────────────────────────────────────────

test('weeklyGroups payload only includes scored, unsent, non-cancelled entries for the selected quarter', function () {
    // Mrs A — 2 scored unsent entries in Q1 (should show up)
    makeCertEntry('Mrs A', 2026, 2, ['score' => 78]);
    makeCertEntry('Mrs A', 2026, 2, ['score' => 90]);
    // Mrs A — 1 scored entry already marked sent (should NOT show up)
    makeCertEntry('Mrs A', 2026, 2, ['score' => 80, 'certificate_sent_at' => now()]);
    // Mrs A — 1 cancelled entry (should NOT show up)
    makeCertEntry('Mrs A', 2026, 2, ['score' => 80, 'notes' => 'CANCELLED']);
    // Mrs A — 1 pending entry, no score (should NOT show up — no result yet)
    makeCertEntry('Mrs A', 2026, 2, ['score' => null]);
    // Mr B — 1 scored unsent entry in Q2 (different quarter — should NOT show on Q1)
    makeCertEntry('Mr B', 2026, 5, ['score' => 80]);

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('weeklyGroups', 1)
            ->where('weeklyGroups.0.teacher_name', 'Mrs A')
            ->where('weeklyGroups.0.unsent_count', 2)
            ->has('weeklyGroups.0.students', 2)
        );
});

test('weeklyGroups is an empty array when nothing is queued', function () {
    // Only a sent entry exists — nothing should be queued
    makeCertEntry('Mrs A', 2026, 2, ['score' => 80, 'certificate_sent_at' => now()]);

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('weeklyGroups', 0));
});

test('weeklyGroups excludes NO_SHOW entries', function () {
    makeCertEntry('Mrs A', 2026, 2, ['score' => null, 'notes' => 'NO_SHOW']);
    makeCertEntry('Mrs A', 2026, 2, ['score' => 80]); // real unsent — should appear

    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->where('weeklyGroups.0.unsent_count', 1)
        );
});

test('weeklyGroups payload is always a sequential array, never an object', function () {
    // If `->filter()` is called without `->values()` the JSON serialises as
    // an object keyed by integer position and `v-for` in Vue breaks.
    makeCertEntry('Mrs A', 2026, 2, ['score' => 80]);
    makeCertEntry('Mrs B', 2026, 2, ['score' => 80, 'certificate_sent_at' => now()]);
    makeCertEntry('Mrs C', 2026, 2, ['score' => 80]);

    $props = $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->viewData('page')['props'];

    expect($props['weeklyGroups'])->toBeArray();
    expect(array_keys($props['weeklyGroups']))->toEqual(range(0, count($props['weeklyGroups']) - 1));
});

test('mark-sent flips certificate_sent_at to now()', function () {
    $entry = makeCertEntry('Mrs A', 2026, 2, ['score' => 80]);
    expect($entry->certificate_sent_at)->toBeNull();

    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/mark-sent', [
            'entry_ids' => [$entry->id],
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'marked' => 1]);

    expect($entry->fresh()->certificate_sent_at)->not->toBeNull();
});

test('mark-sent hides the entry from weeklyGroups', function () {
    $entry = makeCertEntry('Mrs A', 2026, 2, ['score' => 80]);

    // Pre-mark: weekly should have one teacher
    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page->has('weeklyGroups', 1));

    // Mark sent
    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/mark-sent', ['entry_ids' => [$entry->id]])
        ->assertOk();

    // Post-mark: weekly should be empty
    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page->has('weeklyGroups', 0));
});

test('mark-sent requires at least one entry_id', function () {
    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/mark-sent', ['entry_ids' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entry_ids']);
});

test('mark-sent rejects unknown entry ids', function () {
    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/mark-sent', ['entry_ids' => [999999]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entry_ids.0']);
});

test('unmark-sent clears certificate_sent_at and brings the entry back', function () {
    $entry = makeCertEntry('Mrs A', 2026, 2, ['score' => 80, 'certificate_sent_at' => now()]);

    // Confirm it's hidden first
    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page->has('weeklyGroups', 0));

    // Unmark
    $this->actingAs(certsAdmin())
        ->postJson('/admin/certificates/unmark-sent', ['entry_ids' => [$entry->id]])
        ->assertOk()
        ->assertJson(['success' => true, 'unmarked' => 1]);

    expect($entry->fresh()->certificate_sent_at)->toBeNull();

    // And it should reappear in the weekly list
    $this->actingAs(certsAdmin())
        ->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page->has('weeklyGroups', 1));
});

test('mark-sent endpoint requires admin', function () {
    $entry = makeCertEntry('Mrs A', 2026, 2, ['score' => 80]);

    // Guest
    $this->postJson('/admin/certificates/mark-sent', ['entry_ids' => [$entry->id]])
        ->assertUnauthorized();

    // Teacher role — not admin
    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->postJson('/admin/certificates/mark-sent', ['entry_ids' => [$entry->id]])
        ->assertForbidden();
});
