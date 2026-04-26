<?php

// tests/Feature/Console/LinkStudentTeachersFromEntriesTest.php
//
// Locks down the students.teacher_contact_id backfill command. The exam_entries
// FK was backfilled 24 Apr 2026; this command propagates the same FK down to
// the students table so the contact-show "Students (N)" count stops undercounting.

use App\Models\ExamContact;
use App\Models\Order;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────

function makeBackfillTeacher(string $name = 'Megan Price'): ExamContact
{
    $contact = ExamContact::create(['name' => $name]);
    $contact->addType('teacher');

    return $contact;
}

function makeBackfillStudent(string $first, string $last, ?int $teacherContactId = null): Student
{
    return Student::create([
        'first_name' => $first,
        'last_name' => $last,
        'teacher_contact_id' => $teacherContactId,
    ]);
}

function attachEntry(Student $student, Order $order, ?int $teacherContactId): void
{
    $student->examEntries()->create([
        'order_id' => $order->id,
        'candidate_name' => $student->full_name,
        'delivery_method' => 'Digital',
        'source' => 'manual',
        'teacher_contact_id' => $teacherContactId,
    ]);
}

// ──────────────────────────────────────────
// Behaviour
// ──────────────────────────────────────────

it('propagates teacher_contact_id from exam_entries to students with NULL FK', function () {
    $teacher = makeBackfillTeacher('Megan Price');
    $student = makeBackfillStudent('Elise', 'Scott');
    $order = Order::factory()->create();

    attachEntry($student, $order, $teacher->id);

    $this->artisan('students:link-teachers-from-exam-entries')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBe($teacher->id);
});

it('does not overwrite an already-set teacher_contact_id', function () {
    $existing = makeBackfillTeacher('Existing Teacher');
    $other = makeBackfillTeacher('Other Teacher');
    $student = makeBackfillStudent('Already', 'Linked', $existing->id);
    $order = Order::factory()->create();

    attachEntry($student, $order, $other->id);

    $this->artisan('students:link-teachers-from-exam-entries')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBe($existing->id);
});

it('skips students whose entries all have NULL teacher_contact_id', function () {
    $student = makeBackfillStudent('No', 'Teacher');
    $order = Order::factory()->create();

    attachEntry($student, $order, null);

    $this->artisan('students:link-teachers-from-exam-entries')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBeNull();
});

it('flags ambiguous students with two distinct teacher_contact_ids and leaves them NULL', function () {
    $teacherA = makeBackfillTeacher('Teacher A');
    $teacherB = makeBackfillTeacher('Teacher B');
    $student = makeBackfillStudent('Two', 'Teachers');
    $order = Order::factory()->create();

    attachEntry($student, $order, $teacherA->id);
    attachEntry($student, $order, $teacherB->id);

    $this->artisan('students:link-teachers-from-exam-entries')
        ->expectsOutputToContain('Ambiguous')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBeNull();
});

it('dry run does not persist any changes', function () {
    $teacher = makeBackfillTeacher('Megan Price');
    $student = makeBackfillStudent('Elise', 'Scott');
    $order = Order::factory()->create();

    attachEntry($student, $order, $teacher->id);

    $this->artisan('students:link-teachers-from-exam-entries', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run complete')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBeNull();
});

it('handles students with multiple entries all under the same teacher (idempotent dedup)', function () {
    $teacher = makeBackfillTeacher('Megan Price');
    $student = makeBackfillStudent('Multi', 'Entry');
    $order = Order::factory()->create();

    attachEntry($student, $order, $teacher->id);
    attachEntry($student, $order, $teacher->id);
    attachEntry($student, $order, $teacher->id);

    $this->artisan('students:link-teachers-from-exam-entries')
        ->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBe($teacher->id);
});

it('is idempotent — second run is a no-op', function () {
    $teacher = makeBackfillTeacher('Megan Price');
    $student = makeBackfillStudent('Elise', 'Scott');
    $order = Order::factory()->create();

    attachEntry($student, $order, $teacher->id);

    $this->artisan('students:link-teachers-from-exam-entries')->assertExitCode(0);
    $afterFirstRun = $student->fresh()->updated_at;

    $this->artisan('students:link-teachers-from-exam-entries')->assertExitCode(0);

    expect($student->fresh()->teacher_contact_id)->toBe($teacher->id);
    expect($student->fresh()->updated_at->eq($afterFirstRun))->toBeTrue();
});
