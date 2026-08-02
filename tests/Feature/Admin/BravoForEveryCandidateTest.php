<?php

// tests/Feature/Admin/BravoForEveryCandidateTest.php
//
// "Every single entry counts. Every student receives at least a Bravo
// Certificate" — TeacherAwards.vue, ForTeachers.vue, ForParents.vue and
// ExamGuideGrades.vue all promise it, and ExamEntry::certificate_name has
// always honoured it (any non-null score returns at least a Bravo).
//
// The admin side did not: QuarterEndController and CertificateController both
// gated on score >= 60, so a Below Pass candidate was listed on the public
// Recognition page as having a Bravo Certificate while never appearing in the
// teacher's cert table, the weekly send, or a generated ZIP. Found 2 Aug 2026
// via Iris McBride (Piano Initial, Fail, 30 Jun 2026).
//
// CANCELLED and NO_SHOW stay excluded — no exam was sat, so there is nothing
// to award. That distinction is what these tests pin down.

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

// Own fixture name — Pest helpers are global across the suite.
function bravoEntry(array $attrs = []): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-BRAVO-'.uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'teacher_name' => 'Paul Sheridan',
        'candidate_name' => 'Anonymous Candidate',
        'instrument_id' => $piano->id,
        'grade' => 'Initial',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => $date,
        'result' => 'Pass',
        'score' => 70,
    ], $attrs));
}

const BRAVO_QE_URL = '/admin/quarter-end?quarter=1&year=2026';

// ── Quarter End ───────────────────────────────────────────────────────────

test('a below-pass candidate appears on Quarter End with a Bravo Certificate', function () {
    bravoEntry(['candidate_name' => 'Iris McBride', 'score' => 52, 'result' => 'Fail']);

    $this->actingAs($this->admin)->get(BRAVO_QE_URL)
        ->assertStatus(200)
        ->assertInertia(fn ($p) => $p
            ->where('teachers.0.teacher_name', 'Paul Sheridan')
            ->where('teachers.0.with_results', 1)
            ->where('teachers.0.pending', 0)
            ->where('teachers.0.below_pass', 1)
            ->where('teachers.0.students.0.name', 'Iris McBride')
            ->where('teachers.0.students.0.result', 'Below Pass')
            ->where('teachers.0.students.0.certificate', 'Bravo Certificate'));
});

test('the certificate breakdown adds up to the cert count', function () {
    bravoEntry(['candidate_name' => 'Dist',  'score' => 92, 'result' => 'Distinction']);
    bravoEntry(['candidate_name' => 'Merit', 'score' => 80, 'result' => 'Merit']);
    bravoEntry(['candidate_name' => 'Pass',  'score' => 65, 'result' => 'Pass']);
    bravoEntry(['candidate_name' => 'Below', 'score' => 41, 'result' => 'Fail']);

    $this->actingAs($this->admin)->get(BRAVO_QE_URL)
        ->assertInertia(fn ($p) => $p
            ->where('teachers.0.with_results', 4)
            ->where('teachers.0.distinctions', 1)
            ->where('teachers.0.merits', 1)
            ->where('teachers.0.passes', 1)
            ->where('teachers.0.below_pass', 1));
});

test('a below-pass entry counts toward the quarter total with results', function () {
    bravoEntry(['candidate_name' => 'Iris McBride', 'score' => 52, 'result' => 'Fail']);

    $this->actingAs($this->admin)->get(BRAVO_QE_URL)
        ->assertInertia(fn ($p) => $p->where('summary.with_results', 1));
});

test('CANCELLED and NO_SHOW still earn no certificate', function () {
    bravoEntry(['candidate_name' => 'Gone Away', 'score' => null, 'result' => null, 'notes' => ExamEntry::NOTE_CANCELLED]);
    bravoEntry(['candidate_name' => 'Never Came', 'score' => null, 'result' => null, 'notes' => ExamEntry::NOTE_NO_SHOW]);

    $this->actingAs($this->admin)->get(BRAVO_QE_URL)
        ->assertInertia(fn ($p) => $p
            ->where('teachers.0.with_results', 0)
            ->where('teachers.0.pending', 0)
            ->where('teachers.0.students', []));
});

// ── Weekly send (certificate generator page) ──────────────────────────────

test('a below-pass candidate is owed a certificate in the weekly send', function () {
    bravoEntry(['candidate_name' => 'Iris McBride', 'score' => 52, 'result' => 'Fail']);

    $this->actingAs($this->admin)->get('/admin/certificates?quarter=1&year=2026')
        ->assertStatus(200)
        ->assertInertia(fn ($p) => $p
            ->where('weeklyGroups.0.teacher_name', 'Paul Sheridan')
            ->where('weeklyGroups.0.unsent_count', 1)
            ->where('weeklyGroups.0.students.0.name', 'Iris McBride')
            ->where('weeklyGroups.0.students.0.result', 'Below Pass')
            ->where('weeklyGroups.0.students.0.certificate', 'Bravo Certificate'));
});

test('a NO_SHOW is not owed a certificate in the weekly send', function () {
    bravoEntry(['candidate_name' => 'Never Came', 'score' => null, 'result' => null, 'notes' => ExamEntry::NOTE_NO_SHOW]);

    $this->actingAs($this->admin)->get('/admin/certificates?quarter=1&year=2026')
        ->assertInertia(fn ($p) => $p->where('weeklyGroups', []));
});

// ── The model contract the admin side now matches ─────────────────────────

test('certificate_name gives a Bravo for any scored result and null for none', function () {
    expect(bravoEntry(['score' => 92])->certificate_name)->toBe('Standing Ovation Certificate')
        ->and(bravoEntry(['score' => 80])->certificate_name)->toBe('Take a Bow Certificate')
        ->and(bravoEntry(['score' => 65])->certificate_name)->toBe('Bravo Certificate')
        ->and(bravoEntry(['score' => 41])->certificate_name)->toBe('Bravo Certificate')
        ->and(bravoEntry(['score' => null])->certificate_name)->toBeNull();
});
