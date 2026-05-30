<?php

// tests/Feature/Services/TrinityCsvImporterTeacherFkTest.php
//
// Regression coverage for the Maria Nielsen / Lily Jago bug surfaced
// 30 May 2026: a teacher submitting on behalf of a student had her
// contact blanket-tagged 'parent', derived booking_role stuck as
// 'parent', and the exam_entries.teacher_contact_id FK never set.
//
// This file drives `TrinityCsvImporter::commitCandidate` end-to-end and
// asserts the FK gets populated for each role shape:
//
//   - Maria-shape:   submitter == applicant != candidate, no Summary
//                    teacher → role='teacher', FK = submitter contact.
//   - Adrian-shape:  existing 'parent'-tagged contact submits for a
//                    different candidate → role='parent', FK = null.
//   - Self-shape:    submitter == candidate → role='self', FK = null.
//   - Trinity-named: Summary CSV has a Teacher Name matching an
//                    existing teacher contact → FK = that teacher.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Services\TrinityCsvImporter;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeOrderForImport(string $orderNumber): Order
{
    return Order::create([
        'trinity_order_number' => $orderNumber,
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 5, 5),
    ]);
}

function enrolPayload(array $overrides = []): array
{
    return array_merge([
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'subject' => 'Singing',
        'candidate_number' => '1-16786741964',
        'candidate_name' => 'Lily Jago',
        'enrolment_date' => Carbon::create(2026, 5, 5),
        'price' => 78.0,
        'submitter_first' => 'Maria',
        'submitter_last' => 'Nielsen',
        'submitter_name' => 'Maria Nielsen',
        'submitter_email' => 'maria.kn.music@gmail.com',
        'applicant_id' => '1-15899768894',
        'applicant_first' => 'Maria',
        'applicant_last' => 'Nielsen',
        'applicant_name' => 'Maria Nielsen',
    ], $overrides);
}

function summaryPayload(string $orderNumber, array $overrides = []): array
{
    return array_merge([
        'subject_area' => 'Music',
        'syllabus' => 'Classical and Jazz (Digital)',
        'examination_date' => Carbon::create(2026, 5, 5),
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'candidate_number' => '1-16786741964',
        'candidate' => 'Lily Jago',
        'school' => null,
        'teacher_first' => '',
        'teacher_last' => '',
        'teacher_name' => '',
        'status' => 'Certificate Printed',
        'result' => 'Distinction',
        'digital_certificate_id' => '20030417',
        'order_number' => $orderNumber,
        'examiner' => null,
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────────
// Maria-shape: submitter == applicant != candidate, no Summary teacher
// ──────────────────────────────────────────────────────────────────

test('Maria Nielsen shape links teacher_contact_id to the submitter contact', function () {
    $order = makeOrderForImport('1-16786761424');

    $importer = new TrinityCsvImporter();
    $importer->commitCandidate(
        enrol: enrolPayload(),
        summary: summaryPayload('1-16786761424'),
        score: 87,
        dob: null,
        applicantEmail: null,
        userId: null,
    );

    $entry = ExamEntry::where('order_id', $order->id)->firstOrFail();
    $maria = ExamContact::where('email', 'maria.kn.music@gmail.com')->firstOrFail();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_contact_id)->toBe($maria->id)
        ->and($entry->teacher_name)->toBe('Maria Nielsen')
        ->and($entry->submitter_contact_id)->toBe($maria->id)
        ->and($maria->isTeacher())->toBeTrue()
        ->and($maria->isParent())->toBeFalse();
});

test('Maria reading via the teacherContact relation gives her contact', function () {
    makeOrderForImport('1-16786761424');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: enrolPayload(),
        summary: summaryPayload('1-16786761424'),
        score: 87,
        dob: null,
        applicantEmail: null,
        userId: null,
    );

    $entry = ExamEntry::with('teacherContact')->latest('id')->firstOrFail();
    expect($entry->teacherContact?->name)->toBe('Maria Nielsen');
});

// ──────────────────────────────────────────────────────────────────
// Adrian-O'Malley shape: an existing 'parent'-tagged contact submits.
// He's the candidate's parent, NOT a teacher. We must NOT link the
// teacher_contact_id to him — leaves it null for human resolution.
// ──────────────────────────────────────────────────────────────────

test('Adrian O\'Malley shape (existing parent contact) leaves teacher_contact_id null', function () {
    makeOrderForImport('1-ADRIAN-ORDER');

    // Pre-existing parent contact — typical of a parent who's already
    // been logged in HubSpot or via earlier admin work.
    $adrian = ExamContact::create([
        'name' => 'Adrian O\'Malley',
        'email' => 'adrian@example.com',
        'source' => 'manual',
    ]);
    $adrian->addType('parent');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: enrolPayload([
            'submitter_first' => 'Adrian',
            'submitter_last' => 'O\'Malley',
            'submitter_name' => 'Adrian O\'Malley',
            'submitter_email' => 'adrian@example.com',
            'applicant_first' => 'Adrian',
            'applicant_last' => 'O\'Malley',
            'applicant_name' => 'Adrian O\'Malley',
            'candidate_name' => 'Jasper O\'Malley',
            'candidate_number' => '1-JASPER',
        ]),
        summary: summaryPayload('1-ADRIAN-ORDER', [
            'candidate_number' => '1-JASPER',
            'candidate' => 'Jasper O\'Malley',
        ]),
        score: 80,
        dob: null,
        applicantEmail: null,
        userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-JASPER')->firstOrFail();

    expect($entry->booking_role)->toBe('parent')
        ->and($entry->teacher_contact_id)->toBeNull()
        ->and($entry->teacher_name)->toBeNull()
        ->and($entry->submitter_contact_id)->toBe($adrian->id);

    $adrian->refresh();
    expect($adrian->isParent())->toBeTrue()
        ->and($adrian->isTeacher())->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// Self-applicant shape: submitter == candidate (adult learner doing
// their own diploma). No teacher FK — the candidate IS the entrant.
// ──────────────────────────────────────────────────────────────────

test('self-applicant shape leaves teacher_contact_id null and tags neither parent nor teacher', function () {
    makeOrderForImport('1-SELF-ORDER');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: enrolPayload([
            'submitter_first' => 'Adam',
            'submitter_last' => 'Lerner',
            'submitter_name' => 'Adam Lerner',
            'submitter_email' => 'adam@example.com',
            'applicant_first' => 'Adam',
            'applicant_last' => 'Lerner',
            'applicant_name' => 'Adam Lerner',
            'candidate_name' => 'Adam Lerner',
            'candidate_number' => '1-ADAM',
        ]),
        summary: summaryPayload('1-SELF-ORDER', [
            'candidate_number' => '1-ADAM',
            'candidate' => 'Adam Lerner',
        ]),
        score: 91,
        dob: null,
        applicantEmail: null,
        userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-ADAM')->firstOrFail();
    $adam = ExamContact::where('email', 'adam@example.com')->firstOrFail();

    expect($entry->booking_role)->toBe('self')
        ->and($entry->teacher_contact_id)->toBeNull()
        ->and($adam->isParent())->toBeFalse()
        ->and($adam->isTeacher())->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// Trinity-named Summary teacher: Summary CSV has Teacher First/Last
// matching an existing teacher contact → FK points to that teacher,
// not the submitter.
// ──────────────────────────────────────────────────────────────────

test('Summary CSV teacher name matches an existing teacher contact and wins over the submitter', function () {
    makeOrderForImport('1-TRINITY-NAMED');

    // Pre-existing teacher contact Trinity has named on the result.
    $trueTeacher = ExamContact::create([
        'name' => 'Rachel Jones',
        'email' => 'rachel@example.com',
        'source' => 'manual',
    ]);
    $trueTeacher->addType('teacher');

    // Submitter is a parent typing the booking, not a teacher.
    (new TrinityCsvImporter())->commitCandidate(
        enrol: enrolPayload([
            'submitter_first' => 'Helen',
            'submitter_last' => 'Booker',
            'submitter_name' => 'Helen Booker',
            'submitter_email' => 'helen@example.com',
            'applicant_first' => 'Helen',
            'applicant_last' => 'Booker',
            'applicant_name' => 'Helen Booker',
            'candidate_name' => 'Theo Curtis',
            'candidate_number' => '1-THEO',
        ]),
        summary: summaryPayload('1-TRINITY-NAMED', [
            'candidate_number' => '1-THEO',
            'candidate' => 'Theo Curtis',
            'teacher_first' => 'Rachel',
            'teacher_last' => 'Jones',
            'teacher_name' => 'Rachel Jones',
        ]),
        score: 78,
        dob: null,
        applicantEmail: null,
        userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-THEO')->firstOrFail();
    expect($entry->teacher_contact_id)->toBe($trueTeacher->id)
        ->and($entry->teacher_name)->toBe('Rachel Jones');
});
