<?php

// tests/Feature/Dashboard/ExportTest.php
//
// The dated CSV / PDF downloads on the teacher dashboard. These replaced the
// per-teacher Results.csv and Report.pdf that used to be built into the
// Quarter End ZIP — same information, generated on demand for whatever range
// the teacher picks, and streamed rather than written to disk (so nothing
// depends on the container's storage surviving).
//
// The rules that matter:
//   - the range bounds the data, using the order's start date when the entry
//     has no exam date of its own (i.e. it's still awaiting a result);
//   - an export can never contain a candidate the dashboard wouldn't show.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 8, 2, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function exportTeacher(): ExamContact
{
    $contact = ExamContact::create([
        'name' => 'Maria Nielsen',
        'email' => 'mkn21@example.com',
        'source' => 'manual',
    ]);
    $contact->addType('teacher');

    return $contact;
}

function exportEntry(ExamContact $contact, array $attrs = []): ExamEntry
{
    $orderDate = $attrs['order_date'] ?? Carbon::create(2026, 6, 30);
    unset($attrs['order_date']);

    $order = Order::create([
        'trinity_order_number' => '1-EXP-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => $orderDate,
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_number' => '1-'.uniqid('', true),
        'candidate_name' => 'Grace Kennedy',
        'instrument_id' => Instrument::firstOrCreate(['name' => 'Piano'])->id,
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'teacher_contact_id' => $contact->id,
        'exam_date' => Carbon::create(2026, 6, 30),
        'score' => 78,
        'result' => 'Merit',
    ], $attrs));
}

/**
 * The login for a contact. Reused rather than recreated — some tests hit the
 * endpoint twice with different ranges, and users.email is unique.
 */
function actingAsTeacher(ExamContact $contact): User
{
    return User::query()->firstWhere('email', $contact->email)
        ?? User::factory()->create(['email' => $contact->email]);
}

// ── Access ────────────────────────────────────────────────────────────────

test('guests cannot download either export', function () {
    $this->get('/dashboard/export/csv')->assertRedirect(route('login'));
    $this->get('/dashboard/export/pdf')->assertRedirect(route('login'));
});

// ── CSV ───────────────────────────────────────────────────────────────────

test('the CSV contains the teacher own candidates', function () {
    $contact = exportTeacher();
    exportEntry($contact, ['candidate_name' => 'Grace Kennedy', 'score' => 78, 'result' => 'Merit']);

    $response = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv')
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Candidate')
        ->and($csv)->toContain('Grace Kennedy')
        ->and($csv)->toContain('78');
});

test('a candidate still awaiting a result is exported as Awaiting', function () {
    $contact = exportTeacher();
    // The enrolment-list shape: no score, no exam date — only the order's
    // requested start date places it in time.
    exportEntry($contact, [
        'candidate_name' => 'Penelope Jane Mitchell',
        'score' => null,
        'result' => null,
        'exam_date' => null,
        'teacher_contact_id' => null,
        'submitter_contact_id' => $contact->id,
    ]);

    $csv = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv')
        ->streamedContent();

    expect($csv)->toContain('Penelope Jane Mitchell')
        ->and($csv)->toContain('Awaiting');
});

test('the date range bounds the export', function () {
    $contact = exportTeacher();
    exportEntry($contact, [
        'candidate_name' => 'In Range',
        'exam_date' => Carbon::create(2026, 6, 30),
    ]);
    exportEntry($contact, [
        'candidate_name' => 'Out Of Range',
        'exam_date' => Carbon::create(2026, 2, 1),
        'order_date' => Carbon::create(2026, 2, 1),
    ]);

    $csv = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv?from=2026-04-01&to=2026-06-30')
        ->streamedContent();

    expect($csv)->toContain('In Range')
        ->and($csv)->not->toContain('Out Of Range');
});

test('a pending candidate is placed by its order date, not excluded for having none of its own', function () {
    $contact = exportTeacher();
    exportEntry($contact, [
        'candidate_name' => 'Penelope Jane Mitchell',
        'score' => null,
        'result' => null,
        'exam_date' => null,
        'order_date' => Carbon::create(2026, 6, 30),
    ]);

    $inRange = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv?from=2026-04-01&to=2026-06-30')
        ->streamedContent();

    $outOfRange = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv?from=2026-01-01&to=2026-03-31')
        ->streamedContent();

    expect($inRange)->toContain('Penelope Jane Mitchell')
        ->and($outOfRange)->not->toContain('Penelope Jane Mitchell');
});

test('one teacher never gets another teacher candidates', function () {
    $mine = exportTeacher();
    exportEntry($mine, ['candidate_name' => 'Mine Only']);

    $other = ExamContact::create(['name' => 'Someone Else', 'email' => 'other@example.com', 'source' => 'manual']);
    $other->addType('teacher');
    exportEntry($other, ['candidate_name' => 'Theirs Only']);

    $csv = $this->actingAs(actingAsTeacher($other))
        ->get('/dashboard/export/csv')
        ->streamedContent();

    expect($csv)->toContain('Theirs Only')
        ->and($csv)->not->toContain('Mine Only');
});

test('a backwards range is swapped rather than returning nothing', function () {
    $contact = exportTeacher();
    exportEntry($contact, ['candidate_name' => 'Grace Kennedy']);

    $csv = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/csv?from=2026-06-30&to=2026-04-01')
        ->streamedContent();

    expect($csv)->toContain('Grace Kennedy');
});

// ── PDF ───────────────────────────────────────────────────────────────────

test('the PDF downloads and is a PDF', function () {
    $contact = exportTeacher();
    exportEntry($contact);

    // DomPDF's download() returns a normal response with the bytes as content,
    // not a streamed one — unlike the CSV, which is genuinely streamed.
    $response = $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('the PDF renders with no candidates in range', function () {
    $contact = exportTeacher();
    exportEntry($contact, ['exam_date' => Carbon::create(2026, 6, 30)]);

    $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard/export/pdf?from=2026-01-01&to=2026-01-31')
        ->assertOk();
});

// ── Scoping / access ──────────────────────────────────────────────────────

test('the teacher-facing export takes no contact id, so there is nothing to tamper with', function () {
    // The whole GDPR guarantee rests on this: /dashboard/export/* resolves the
    // owner from the authenticated user, never from the URL.
    $mine = exportTeacher();
    exportEntry($mine, ['candidate_name' => 'Mine Only']);

    $other = ExamContact::create(['name' => 'Nosy Teacher', 'email' => 'nosy@example.com', 'source' => 'manual']);
    $other->addType('teacher');

    $csv = $this->actingAs(actingAsTeacher($other))
        ->get("/dashboard/export/csv?contact={$mine->id}&contact_id={$mine->id}")
        ->streamedContent();

    expect($csv)->not->toContain('Mine Only');
});

test('a non-admin cannot use the contact-scoped export', function () {
    $contact = exportTeacher();
    exportEntry($contact, ['candidate_name' => 'Grace Kennedy']);

    $teacher = User::factory()->create(['role' => 'teacher', 'email' => 'plain@example.com']);

    $this->actingAs($teacher)
        ->get("/admin/contacts/{$contact->id}/export/csv")
        ->assertForbidden();
});

test('an admin export for a contact returns that contact candidates, not the admin own', function () {
    // The preview-dashboard bug: its buttons pointed at /dashboard/export/*,
    // which handed the admin their own candidates under the teacher's name.
    $maria = exportTeacher();
    exportEntry($maria, ['candidate_name' => 'Grace Kennedy']);

    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
    $adminContact = ExamContact::create(['name' => 'Paul Sheridan', 'email' => $admin->email, 'source' => 'manual']);
    $adminContact->addType('teacher');
    exportEntry($adminContact, ['candidate_name' => 'Megan Roberts']);

    $csv = $this->actingAs($admin)
        ->get("/admin/contacts/{$maria->id}/export/csv")
        ->streamedContent();

    expect($csv)->toContain('Grace Kennedy')
        ->and($csv)->not->toContain('Megan Roberts');
});

// ── The dashboard itself ──────────────────────────────────────────────────

test('the dashboard filters by the same range and reports it back', function () {
    $contact = exportTeacher();
    exportEntry($contact, ['candidate_name' => 'In Range', 'exam_date' => Carbon::create(2026, 6, 30)]);
    exportEntry($contact, [
        'candidate_name' => 'Out Of Range',
        'exam_date' => Carbon::create(2026, 2, 1),
        'order_date' => Carbon::create(2026, 2, 1),
    ]);

    $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard?from=2026-04-01&to=2026-06-30')
        ->assertInertia(fn ($p) => $p
            ->has('examEntries', 1)
            ->where('examEntries.0.candidate_name', 'In Range')
            ->where('filters.from', '2026-04-01')
            ->where('filters.to', '2026-06-30'));
});

test('with no range the dashboard falls back to the full history', function () {
    $contact = exportTeacher();
    exportEntry($contact);

    $this->actingAs(actingAsTeacher($contact))
        ->get('/dashboard')
        ->assertInertia(fn ($p) => $p
            ->where('filters.from', '2026-01-01')
            ->where('filters.history_start', '2026-01-01'));
});
