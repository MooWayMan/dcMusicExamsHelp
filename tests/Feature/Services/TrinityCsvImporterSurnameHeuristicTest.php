<?php

// tests/Feature/Services/TrinityCsvImporterSurnameHeuristicTest.php
//
// The shared-surname rule in TrinityCsvImporter::deriveBookingRole (2 Aug 2026).
//
// A parent entering their own child and a teacher entering a student produce
// an IDENTICAL Trinity CSV shape: submitter == applicant != candidate. The old
// rule-4 shape default read every one of them as 'teacher', which stamped
// booking_role='teacher' on the entry — and because QuarterEndController reads
// the per-entry role BEFORE contact-type inference, a correctly typed Parent
// contact still lost their "Parent booking" tag and sat in the prize draw.
// (Found via Mark Vincent-Smith → Jacob Vincent-Smith, Q2 2026.)
//
// The family name is the one signal that separates the two shapes.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Services\TrinityCsvImporter;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Self-contained fixtures — Pest helper functions are global, so these names
// must not collide with the other TrinityCsvImporter test files.
function surnameOrder(string $orderNumber): Order
{
    return Order::create([
        'trinity_order_number' => $orderNumber,
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 6, 30),
    ]);
}

function surnameEnrol(array $overrides = []): array
{
    return array_merge([
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'subject' => 'Singing',
        'candidate_number' => '1-CAND-S',
        'candidate_name' => 'Jacob Vincent-Smith',
        'enrolment_date' => Carbon::create(2026, 6, 30),
        'price' => 78.0,
        'submitter_first' => 'Mark',
        'submitter_last' => 'Vincent-Smith',
        'submitter_name' => 'Mark Vincent-Smith',
        'submitter_email' => 'markandjulievs@aol.com',
        'applicant_id' => '1-APPL-S',
        'applicant_first' => 'Mark',
        'applicant_last' => 'Vincent-Smith',
        'applicant_name' => 'Mark Vincent-Smith',
    ], $overrides);
}

function surnameSummary(string $orderNumber, array $overrides = []): array
{
    return array_merge([
        'subject_area' => 'Music',
        'syllabus' => 'Classical and Jazz (Digital)',
        'examination_date' => Carbon::create(2026, 6, 30),
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'candidate_number' => '1-CAND-S',
        'candidate' => 'Jacob Vincent-Smith',
        'school' => null,
        'teacher_first' => '',
        'teacher_last' => '',
        'teacher_name' => '',
        'status' => 'Certificate Printed',
        'result' => 'Pass',
        'digital_certificate_id' => '14517696',
        'order_number' => $orderNumber,
        'examiner' => null,
    ], $overrides);
}

test('a parent entering their own child is derived as parent, not teacher', function () {
    surnameOrder('1-SURNAME-PARENT');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: surnameEnrol(['candidate_number' => '1-JACOB-S']),
        summary: surnameSummary('1-SURNAME-PARENT', ['candidate_number' => '1-JACOB-S']),
        score: 72, dob: null, applicantEmail: null, userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-JACOB-S')->firstOrFail();

    expect($entry->booking_role)->toBe('parent')
        ->and($entry->teacher_contact_id)->toBeNull()
        ->and($entry->teacher_name)->toBeNull();

    $mark = ExamContact::where('email', 'markandjulievs@aol.com')->firstOrFail();
    expect($mark->isParent())->toBeTrue()
        ->and($mark->isTeacher())->toBeFalse();
});

test('a teacher entering a student with a different surname is still derived as teacher', function () {
    // The Maria Nielsen shape the rule-4 default was written for — it must
    // survive the new rule sitting in front of it.
    surnameOrder('1-SURNAME-TEACHER');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: surnameEnrol([
            'submitter_first' => 'Maria', 'submitter_last' => 'Nielsen',
            'submitter_name' => 'Maria Nielsen', 'submitter_email' => 'mkn21@me.com',
            'applicant_first' => 'Maria', 'applicant_last' => 'Nielsen',
            'applicant_name' => 'Maria Nielsen',
            'candidate_name' => 'Grace Kennedy', 'candidate_number' => '1-GRACE-S',
        ]),
        summary: surnameSummary('1-SURNAME-TEACHER', [
            'candidate' => 'Grace Kennedy', 'candidate_number' => '1-GRACE-S',
        ]),
        score: 80, dob: null, applicantEmail: null, userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-GRACE-S')->firstOrFail();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_name)->toBe('Maria Nielsen');
});

test('a registered teacher who shares a surname with the candidate stays a teacher', function () {
    // Rule 3 (contact lookup) runs BEFORE the surname rule, so a known
    // teacher is never demoted by a coincidental family name.
    surnameOrder('1-SURNAME-KNOWN');

    $anna = ExamContact::create([
        'name' => 'Anna Ford',
        'email' => 'anna@studio.example',
        'source' => 'manual',
    ]);
    $anna->addType('teacher');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: surnameEnrol([
            'submitter_first' => 'Anna', 'submitter_last' => 'Ford',
            'submitter_name' => 'Anna Ford', 'submitter_email' => 'anna@studio.example',
            'applicant_first' => 'Anna', 'applicant_last' => 'Ford',
            'applicant_name' => 'Anna Ford',
            'candidate_name' => 'Ben Ford', 'candidate_number' => '1-BEN-S',
        ]),
        summary: surnameSummary('1-SURNAME-KNOWN', [
            'candidate' => 'Ben Ford', 'candidate_number' => '1-BEN-S',
        ]),
        score: 85, dob: null, applicantEmail: null, userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-BEN-S')->firstOrFail();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_contact_id)->toBe($anna->id);
});

test('a one-word candidate name never matches a surname', function () {
    // surnameOf() returns '' when there is no separable last name, so a
    // mononym can't collide with an applicant's surname.
    surnameOrder('1-SURNAME-MONONYM');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: surnameEnrol([
            'submitter_first' => 'Sarah', 'submitter_last' => 'Mitchell',
            'submitter_name' => 'Sarah Mitchell', 'submitter_email' => 'sarah@example.com',
            'applicant_first' => 'Sarah', 'applicant_last' => 'Mitchell',
            'applicant_name' => 'Sarah Mitchell',
            'candidate_name' => 'Madonna', 'candidate_number' => '1-MADONNA-S',
        ]),
        summary: surnameSummary('1-SURNAME-MONONYM', [
            'candidate' => 'Madonna', 'candidate_number' => '1-MADONNA-S',
        ]),
        score: 90, dob: null, applicantEmail: null, userId: null,
    );

    expect(ExamEntry::where('candidate_number', '1-MADONNA-S')->firstOrFail()->booking_role)
        ->toBe('teacher');
});

test('an explicit human role at import still wins over the surname rule', function () {
    // A teacher who genuinely shares a student's surname — Paul overrides on
    // the import page and the confirmed role is what gets stored.
    surnameOrder('1-SURNAME-OVERRIDE');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: surnameEnrol(['candidate_number' => '1-OVERRIDE-S']),
        summary: surnameSummary('1-SURNAME-OVERRIDE', ['candidate_number' => '1-OVERRIDE-S']),
        score: 70, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: [
            'role' => 'teacher',
            'teacher_name' => 'Mark Vincent-Smith',
        ],
    );

    expect(ExamEntry::where('candidate_number', '1-OVERRIDE-S')->firstOrFail()->booking_role)
        ->toBe('teacher');
});

test('the preview pre-selects parent and says which surname matched', function () {
    surnameOrder('1-SURNAME-PREVIEW');

    $preview = (new TrinityCsvImporter())->previewCandidate(
        surnameEnrol(),
        surnameSummary('1-SURNAME-PREVIEW'),
        72,
        null,
        null,
    );

    expect($preview['derivedRole'])->toBe('parent')
        ->and($preview['roleSuggestion']['role'])->toBe('parent')
        ->and($preview['roleSuggestion']['reason'])->toContain('Vincent-Smith')
        ->and($preview['warnings'])->not->toContain('Order 1-SURNAME-PREVIEW not found — run Section 1 first.');
});
