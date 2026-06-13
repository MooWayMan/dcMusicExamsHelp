<?php

// tests/Feature/Services/TrinityCsvImporterRoleOverrideTest.php
//
// Phase 1 of the recognition-attribution model (13 Jun 2026):
//   - the human confirms the booking role at import; commitCandidate honours
//     it instead of guessing (kills the parent-as-teacher pollution);
//   - a Student is created + linked inline so candidates are never orphaned
//     (the Isaac Ellison "cert but no student" gap);
//   - previewCandidate returns an evidence-based role suggestion.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\School;
use App\Services\TrinityCsvImporter;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Self-contained fixtures (named to avoid clashing with the teacher-FK test).
function roleOrder(string $orderNumber): Order
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

function roleEnrol(array $overrides = []): array
{
    return array_merge([
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'subject' => 'Singing',
        'candidate_number' => '1-CAND-X',
        'candidate_name' => 'Kid Smith',
        'enrolment_date' => Carbon::create(2026, 5, 5),
        'price' => 78.0,
        'submitter_first' => 'Mum',
        'submitter_last' => 'Smith',
        'submitter_name' => 'Mum Smith',
        'submitter_email' => 'mum@example.com',
        'applicant_id' => '1-APPL-X',
        'applicant_first' => 'Mum',
        'applicant_last' => 'Smith',
        'applicant_name' => 'Mum Smith',
    ], $overrides);
}

function roleSummary(string $orderNumber, array $overrides = []): array
{
    return array_merge([
        'subject_area' => 'Music',
        'syllabus' => 'Classical and Jazz (Digital)',
        'examination_date' => Carbon::create(2026, 5, 5),
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'candidate_number' => '1-CAND-X',
        'candidate' => 'Kid Smith',
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

test('explicit parent role overrides the teacher shape-guess and is not tagged teacher', function () {
    roleOrder('1-ROLE-PARENT');

    // Submitter == applicant != candidate → the old shape default would have
    // guessed 'teacher'. The human confirmed 'parent'.
    (new TrinityCsvImporter())->commitCandidate(
        enrol: roleEnrol([
            'submitter_first' => 'Mark', 'submitter_last' => 'Vincent-Smith',
            'submitter_name' => 'Mark Vincent-Smith',
            'submitter_email' => 'markandjulievs@aol.com',
            'applicant_first' => 'Mark', 'applicant_last' => 'Vincent-Smith',
            'applicant_name' => 'Mark Vincent-Smith',
            'candidate_name' => 'Jacob Vincent-Smith',
            'candidate_number' => '1-JACOB',
        ]),
        summary: roleSummary('1-ROLE-PARENT', [
            'candidate_number' => '1-JACOB', 'candidate' => 'Jacob Vincent-Smith',
        ]),
        score: 60, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: ['role' => 'parent'],
    );

    $entry = ExamEntry::where('candidate_number', '1-JACOB')->firstOrFail();
    expect($entry->booking_role)->toBe('parent')
        ->and($entry->teacher_contact_id)->toBeNull();

    $mark = ExamContact::where('email', 'markandjulievs@aol.com')->firstOrFail();
    expect($mark->isTeacher())->toBeFalse()
        ->and($mark->isParent())->toBeTrue();
});

test('explicit teacher role creates a new teacher contact, tags it, and credits the student', function () {
    roleOrder('1-ROLE-TEACHER');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: roleEnrol([
            'candidate_name' => 'Kid Smith', 'candidate_number' => '1-KID',
        ]),
        summary: roleSummary('1-ROLE-TEACHER', [
            'candidate_number' => '1-KID', 'candidate' => 'Kid Smith',
        ]),
        score: 90, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: [
            'role' => 'teacher',
            'teacher_name' => 'Ivy Teacher',
            'teacher_email' => 'ivy@music.example',
        ],
    );

    $entry = ExamEntry::where('candidate_number', '1-KID')->firstOrFail();
    $ivy = ExamContact::where('email', 'ivy@music.example')->firstOrFail();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_contact_id)->toBe($ivy->id)
        ->and($entry->teacher_name)->toBe('Ivy Teacher')
        ->and($ivy->isTeacher())->toBeTrue();

    $student = \App\Models\Student::find($entry->student_id);
    expect($student)->not->toBeNull();
    expect($student->teacher_contact_id)->toBe($ivy->id);
});

test('explicit school_admin role tags school_admin and reuses an existing contact', function () {
    roleOrder('1-ROLE-SCHOOL');

    $daniel = ExamContact::create(['name' => 'Daniel Rogers', 'email' => 'daniel@pulse.example', 'source' => 'manual']);
    $daniel->addType('school_admin');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: roleEnrol([
            'submitter_first' => 'Daniel', 'submitter_last' => 'Rogers',
            'submitter_name' => 'Daniel Rogers', 'submitter_email' => 'daniel@pulse.example',
            'applicant_first' => 'Daniel', 'applicant_last' => 'Rogers',
            'applicant_name' => 'Daniel Rogers',
            'candidate_name' => 'Oscar Cain', 'candidate_number' => '1-OSCAR',
        ]),
        summary: roleSummary('1-ROLE-SCHOOL', [
            'candidate_number' => '1-OSCAR', 'candidate' => 'Oscar Cain',
        ]),
        score: 75, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: [
            'role' => 'school_admin',
            'teacher_contact_id' => $daniel->id,
            'school_name' => 'Pulse Music School',
        ],
    );

    $entry = ExamEntry::where('candidate_number', '1-OSCAR')->firstOrFail();
    expect($entry->booking_role)->toBe('school_admin')
        ->and($entry->teacher_contact_id)->toBe($daniel->id)
        ->and($entry->school_name)->toBe('Pulse Music School');

    expect(ExamContact::where('name', 'Daniel Rogers')->count())->toBe(1);
    $daniel->refresh();
    expect($daniel->isSchoolAdmin())->toBeTrue();

    // The admin contact is linked to the school it rolls up to.
    $school = School::where('name', 'Pulse Music School')->firstOrFail();
    expect($daniel->schools()->where('schools.id', $school->id)->exists())->toBeTrue();
});

test('school_admin role reuses an existing school by id without duplicating it', function () {
    roleOrder('1-ROLE-SCHOOL-ID');

    $learnMusic = School::create(['name' => 'Learn Music Ltd']);
    $clare = ExamContact::create(['name' => 'Clare Keeling', 'email' => 'lessons@learnmusic.co.uk', 'source' => 'manual']);
    $clare->addType('school_admin');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: roleEnrol([
            'submitter_first' => 'Emily', 'submitter_last' => 'Bates',
            'submitter_name' => 'Emily Bates', 'submitter_email' => 'musiclearn11@gmail.com',
            'applicant_first' => 'Clare', 'applicant_last' => 'Keeling',
            'applicant_name' => 'Clare Keeling',
            'candidate_name' => 'Joshua Ing Hern Ting', 'candidate_number' => '1-JOSHUA',
        ]),
        summary: roleSummary('1-ROLE-SCHOOL-ID', [
            'candidate_number' => '1-JOSHUA', 'candidate' => 'Joshua Ing Hern Ting',
        ]),
        score: 76, dob: null, applicantEmail: 'lessons@learnmusic.co.uk', userId: null, filename: null,
        roleOverride: [
            'role' => 'school_admin',
            'teacher_contact_id' => $clare->id,
            'school_id' => $learnMusic->id,
            'school_name' => 'Learn Music Ltd',
        ],
    );

    $entry = ExamEntry::where('candidate_number', '1-JOSHUA')->firstOrFail();
    expect($entry->school_name)->toBe('Learn Music Ltd');

    // Reused, not duplicated.
    expect(School::where('name', 'Learn Music Ltd')->count())->toBe(1);
    expect($clare->fresh()->schools()->where('schools.id', $learnMusic->id)->exists())->toBeTrue();
});

test('commit without a role override still links a Student (legacy path, the Isaac Ellison gap)', function () {
    roleOrder('1-LEGACY');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: roleEnrol(['candidate_name' => 'Lily Jago', 'candidate_number' => '1-LILY']),
        summary: roleSummary('1-LEGACY', ['candidate_number' => '1-LILY', 'candidate' => 'Lily Jago']),
        score: 80, dob: null, applicantEmail: null, userId: null,
    );

    $entry = ExamEntry::where('candidate_number', '1-LILY')->firstOrFail();
    expect($entry->student_id)->not->toBeNull();
    expect(\App\Models\Student::find($entry->student_id)->full_name)->toBe('Lily Jago');
});

test('preview suggests teacher when the applicant matches a registered teacher', function () {
    roleOrder('1-SUGGEST');

    $clare = ExamContact::create(['name' => 'Clare Keeling', 'email' => 'lessons@learnmusic.co.uk', 'source' => 'manual']);
    $clare->addType('teacher');

    // Applicant (Clare) differs from submitter (Emily) → applicant email supplied.
    $preview = (new TrinityCsvImporter())->previewCandidate(
        roleEnrol([
            'submitter_first' => 'Emily', 'submitter_last' => 'Bates',
            'submitter_name' => 'Emily Bates', 'submitter_email' => 'musiclearn11@gmail.com',
            'applicant_first' => 'Clare', 'applicant_last' => 'Keeling',
            'applicant_name' => 'Clare Keeling',
            'candidate_name' => 'Ariya Evans', 'candidate_number' => '1-ARIYA',
        ]),
        roleSummary('1-SUGGEST', ['candidate_number' => '1-ARIYA', 'candidate' => 'Ariya Evans']),
        70, null, 'lessons@learnmusic.co.uk',
    );

    expect($preview['roleSuggestion']['role'])->toBe('teacher')
        ->and($preview['roleSuggestion']['matched_contact']['id'])->toBe($clare->id)
        ->and($preview['roleSuggestion']['matched_contact']['matched_by'])->toBe('email');
});

test('preview suggests parent when the applicant is a known parent', function () {
    roleOrder('1-SUGGEST-P');

    $parent = ExamContact::create(['name' => 'Helen Khoo', 'email' => 'helen@example.com', 'source' => 'manual']);
    $parent->addType('parent');

    $preview = (new TrinityCsvImporter())->previewCandidate(
        roleEnrol([
            'submitter_first' => 'Helen', 'submitter_last' => 'Khoo',
            'submitter_name' => 'Helen Khoo', 'submitter_email' => 'helen@example.com',
            'applicant_first' => 'Helen', 'applicant_last' => 'Khoo',
            'applicant_name' => 'Helen Khoo',
            'candidate_name' => 'Alice Khoo', 'candidate_number' => '1-ALICE',
        ]),
        roleSummary('1-SUGGEST-P', ['candidate_number' => '1-ALICE', 'candidate' => 'Alice Khoo']),
        81, null, null,
    );

    expect($preview['roleSuggestion']['role'])->toBe('parent');
});
