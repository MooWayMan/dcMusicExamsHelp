<?php

// tests/Feature/Services/PersistedInstrumentsTest.php
//
// Instruments persist on the teacher / school-admin contact and the school
// (contact_instrument / school_instrument), so the instrument profile
// survives deletion of the exam entries it came from. Plus the backfill for
// pre-existing entries.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Services\TrinityCsvImporter;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function instrOrder(string $n): Order
{
    return Order::create([
        'trinity_order_number' => $n,
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 5, 5),
    ]);
}

function instrEnrol(array $o = []): array
{
    return array_merge([
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'subject' => 'Piano',
        'candidate_number' => '1-INSTR',
        'candidate_name' => 'Joshua Ing Hern Ting',
        'enrolment_date' => Carbon::create(2026, 5, 5),
        'price' => 78.0,
        'submitter_first' => 'Clare', 'submitter_last' => 'Keeling', 'submitter_name' => 'Clare Keeling',
        'submitter_email' => 'lessons@learnmusic.co.uk',
        'applicant_id' => '1-A', 'applicant_first' => 'Clare', 'applicant_last' => 'Keeling', 'applicant_name' => 'Clare Keeling',
    ], $o);
}

function instrSummary(string $order, array $o = []): array
{
    return array_merge([
        'subject_area' => 'Music',
        'syllabus' => 'Classical and Jazz (Digital)',
        'examination_date' => Carbon::create(2026, 5, 5),
        'examination' => 'Classical and Jazz Technical Grade 4 (Digital)',
        'candidate_number' => '1-INSTR',
        'candidate' => 'Joshua Ing Hern Ting',
        'school' => null,
        'teacher_first' => '', 'teacher_last' => '', 'teacher_name' => '',
        'status' => 'Certificate Printed',
        'result' => 'Merit',
        'digital_certificate_id' => '1',
        'order_number' => $order,
        'examiner' => null,
    ], $o);
}

test('import persists the instrument on the school-admin contact AND the school, surviving entry deletion', function () {
    $piano = Instrument::firstOrCreate(['name' => 'Piano'], ['family' => 'Keyboard']);
    instrOrder('1-INSTR-ORDER');

    $school = School::create(['name' => 'Learn Music Ltd']);
    $clare = ExamContact::create(['name' => 'Clare Keeling', 'email' => 'lessons@learnmusic.co.uk']);
    $clare->addType('school_admin');
    $clare->schools()->attach($school->id);

    (new TrinityCsvImporter())->commitCandidate(
        enrol: instrEnrol(),
        summary: instrSummary('1-INSTR-ORDER'),
        score: 80, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: [
            'role' => 'school_admin',
            'teacher_contact_id' => $clare->id,
            'school_id' => $school->id,
            'school_name' => 'Learn Music Ltd',
        ],
    );

    expect($clare->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
    expect($school->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();

    // Delete the entry — the persisted links remain (that's the whole point).
    ExamEntry::where('candidate_number', '1-INSTR')->delete();

    expect($clare->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
    expect($school->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
});

test('import persists the instrument on an individual teacher contact (no school)', function () {
    $piano = Instrument::firstOrCreate(['name' => 'Piano'], ['family' => 'Keyboard']);
    instrOrder('1-INSTR-T');

    $teacher = ExamContact::create(['name' => 'Helen Help', 'email' => 'helen@example.com']);
    $teacher->addType('teacher');

    (new TrinityCsvImporter())->commitCandidate(
        enrol: instrEnrol([
            'submitter_name' => 'Helen Help', 'submitter_first' => 'Helen', 'submitter_last' => 'Help',
            'submitter_email' => 'helen@example.com',
            'applicant_name' => 'Helen Help', 'applicant_first' => 'Helen', 'applicant_last' => 'Help',
            'candidate_name' => 'Some Kid', 'candidate_number' => '1-KID2',
        ]),
        summary: instrSummary('1-INSTR-T', ['candidate_number' => '1-KID2', 'candidate' => 'Some Kid']),
        score: 80, dob: null, applicantEmail: null, userId: null, filename: null,
        roleOverride: ['role' => 'teacher', 'teacher_contact_id' => $teacher->id],
    );

    expect($teacher->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
});

test('instruments:backfill populates contact and school instruments from existing entries', function () {
    $piano = Instrument::firstOrCreate(['name' => 'Piano'], ['family' => 'Keyboard']);
    $order = instrOrder('1-BF');

    $school = School::create(['name' => 'Learn Music Ltd']);
    $clare = ExamContact::create(['name' => 'Clare Keeling']);
    $clare->addType('school_admin');
    $clare->schools()->attach($school->id);

    // Pre-feature entry — no persisted links yet.
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Old Kid',
        'candidate_number' => '1-OLD',
        'instrument_id' => $piano->id,
        'teacher_contact_id' => $clare->id,
        'booking_role' => 'school_admin',
        'grade' => 'Grade 1',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 5, 5),
    ]);

    expect($clare->fresh()->instruments()->count())->toBe(0);

    $this->artisan('instruments:backfill')->assertExitCode(0);

    expect($clare->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
    expect($school->fresh()->instruments()->where('instruments.id', $piano->id)->exists())->toBeTrue();
});
