<?php

// tests/Feature/Admin/EnrolmentExportShapesTest.php
//
// Trinity ships TWO enrolment exports and the importer only ever understood
// one of them.
//
//   "Generate Summary of Entries" (digital) carries Submitter Last/First Name
//   and Submitter Email Address.
//
//   The face-to-face export has none of those. The booker sits in Applicant
//   Last/First Name with a plain "Email Address", Subject is EMPTY (the
//   instrument is only inside Examination), and there are extra columns like
//   Line #, Voucher, School and Role.
//
// Requiring the Submitter columns rejected every F2F export outright, which is
// why the July 2026 session went in results-first: 53 entries with no fee and
// six candidates who never sat missing altogether.

use App\Services\TrinityCsvImporter;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function digitalExport(): string
{
    $h = "Examination\tSubject\tCandidate Number\tCandidate Name\tEnrolment Date\tPrice\t"
        ."Submitter Last Name\tSubmitter First Name\tSubmitter Email Address\t"
        ."Applicant Id\tApplicant Last Name\tApplicant First Name";
    $r = "Classical and Jazz Technical Grade 6 (Digital)\tScottish Traditional Fiddle\t1-18210097584\t"
        ."Arran Cameron\t15/07/2026 00:00:00\t£98.00\tHibbs\tJoseph\tjoseph.hibbs@aberdeenshire.gov.uk\t"
        ."1-5774534943\tHibbs\tJoseph";

    return $h."\n".$r."\n";
}

function faceToFaceExport(): string
{
    $h = "Line #\tCandidate Number\tEnrolment Date\tCandidate Name\tApplicant Last Name\t"
        ."Applicant First Name\tEmail Address\tExamination\tExam Type\tSubject\tPrice\tVoucher\t"
        ."Status\tCancelled\tSchool\tApplicant Id\tOrder Number\tSubject Area";
    $r = "8\t1-17563392237\t13/06/2026 11:01:53\tSam Dobie\tRogers\tDaniel\t"
        ."rogers@pulsemusicliverpool.com\tRock and Pop Guitar Grade 4\tPractical\t\t£86.00\t\t"
        ."Processed\tN\tPulse Music and Education\t1-2327046-9\t1-17563364798\tRock and Pop";

    return $h."\n".$r."\n";
}

test('the digital export still parses', function () {
    $rows = (new TrinityCsvImporter())->parseEnrolmentList(digitalExport());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['candidate_name'])->toBe('Arran Cameron')
        ->and($rows[0]['submitter_name'])->toBe('Joseph Hibbs')
        ->and($rows[0]['submitter_email'])->toBe('joseph.hibbs@aberdeenshire.gov.uk')
        ->and($rows[0]['subject'])->toBe('Scottish Traditional Fiddle');
});

test('the face-to-face export is no longer rejected', function () {
    // Previously: "CSV missing required columns: Submitter Last Name,
    // Submitter First Name, Submitter Email Address".
    $rows = (new TrinityCsvImporter())->parseEnrolmentList(faceToFaceExport());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['candidate_name'])->toBe('Sam Dobie')
        ->and($rows[0]['candidate_number'])->toBe('1-17563392237');
});

test('with no Submitter columns the applicant is the booker', function () {
    $rows = (new TrinityCsvImporter())->parseEnrolmentList(faceToFaceExport());

    expect($rows[0]['submitter_name'])->toBe('Daniel Rogers')
        ->and($rows[0]['submitter_email'])->toBe('rogers@pulsemusicliverpool.com');
});

test('the fee is read so entries do not land unpriced', function () {
    // The 53 July entries all carried £0 precisely because this file never
    // imported.
    $rows = (new TrinityCsvImporter())->parseEnrolmentList(faceToFaceExport());

    expect((float) $rows[0]['price'])->toBe(86.0);
});

test('the instrument is recovered from Examination when Subject is empty', function () {
    expect(TrinityCsvImporter::instrumentFromExaminationForTest('Rock and Pop Guitar Grade 4'))->toBe('Guitar')
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Rock and Pop Vocals Grade 1'))->toBe('Vocals')
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Rock and Pop Drums Grade 4'))->toBe('Drums')
        // Trinity writes its Initial grade as "Grade IN", not "Initial".
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Piano Grade IN'))->toBe('Piano')
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Violin Grade IN'))->toBe('Violin')
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Classical Guitar Grade 5'))->toBe('Classical Guitar')
        ->and(TrinityCsvImporter::instrumentFromExaminationForTest('Electronic Keyboard Grade 3'))->toBe('Electronic Keyboard');
});

// ──────────────────────────────────────────
// Sorting dropped files into slots on /admin/imports.
//
// The page puts a file in a slot when its header holds every column
// TrinityCsvImporter::candidateCsvHeaders() lists for that slot (marksheet,
// then summary, then enrolment). It used to match the first few columns of
// the DIGITAL export only, so a face-to-face order export was refused with
// "header doesn't match Enrolment / Summary / Marksheet format" (Daisy
// Miller, 26 Sep 2026) although the import itself reads it.
// ──────────────────────────────────────────

/** The slot the Imports page would give a file with these columns. */
function slotFor(array $columns): ?string
{
    foreach (['marksheet', 'summary', 'enrolment'] as $slot) {
        if (array_diff(TrinityCsvImporter::candidateCsvHeaders()[$slot], $columns) === []) {
            return $slot;
        }
    }

    return null;
}

/** Real header rows, as Trinity exports them (26 Sep 2026). */
function realHeaders(): array
{
    return [
        'face-to-face order' => ['Line #', 'Unique Electronic Reference', 'Candidate Number', 'Enrolment Date', 'Candidate Name',
            'Candidate Birth Date', 'Under 18?', 'Gender', 'Consent Received', 'Applicant Last Name', 'ID Document Type',
            'Applicant First Name', 'Email Address', 'Examination', 'Reason for Change of ID Details', 'ID Document Number',
            'Exam Type', 'Subject', 'Nationality', 'Price', 'Voucher', 'External Funding', 'ID Document Expiry Date', 'Status',
            'Cancelled', 'SEN', 'Exam Code', 'School on Certificate', 'SEN Requirements', 'School', 'Role', 'Comments',
            'Applicant Id', 'Art Form', 'Minimum Age', 'Email Address', 'Age Validated', 'Adjusted Duration', 'First Language',
            'Order Number', 'Product Id', 'Assessment', 'Language of Exam', 'Photo Status', 'Timetable Order',
            'Left Handed Drum Kit', 'Agent', 'Agent Address', 'Subject Area', 'Alternative Assessment'],
        'digital summary of entries' => ['Examination', 'Subject', 'Candidate Number', 'Candidate Name', 'Enrolment Date', 'Price',
            'Submitter Last Name', 'Submitter First Name', 'Submitter Email Address', 'Applicant Id', 'Applicant Last Name',
            'Applicant First Name'],
        'summary' => ['Subject Area', 'Syllabus', 'Examination Date', 'Examination', 'Candidate Number', 'Candidate', 'School',
            'Teacher First Name', 'Teacher Last Name', 'Status', 'Result', 'Digital Certificate ID', 'Digital Certificate URL',
            'Order Number', 'Examiner'],
        'marksheet' => ['Section #', 'Mark', 'Section', 'Max'],
    ];
}

test('every Trinity export lands in the right slot on the Imports page', function () {
    $h = realHeaders();

    expect(slotFor($h['face-to-face order']))->toBe('enrolment')
        ->and(slotFor($h['digital summary of entries']))->toBe('enrolment')
        ->and(slotFor($h['summary']))->toBe('summary')
        ->and(slotFor($h['marksheet']))->toBe('marksheet')
        ->and(slotFor(['Order Number', 'Price']))->toBeNull();
});

test('the Imports page is given the importer\'s own column lists', function () {
    $admin = App\Models\User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/imports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('candidateCsvHeaders', TrinityCsvImporter::candidateCsvHeaders()));
});

test('the Imports page never hard-codes an export header of its own', function () {
    expect(guardOffenders('/Examination,Subject,Candidate Number|Subject Area,Syllabus,Examination Date|Section #,Mark,Section,Max/', []))->toBe([]);
});

// ──────────────────────────────────────────
// The applicant's email on a face-to-face order.
//
// That export has no Submitter columns, so the applicant is the booker and
// their email is the row's own Email Address, which Trinity leaves blank on
// most rows (26 Sep 2026: 2 of 12 candidates on one order had one). The
// email Paul types on the Imports page used to be thrown away in that case,
// so a parent-booked entry saved with no address for results or prizes.
// ──────────────────────────────────────────

function f2fOrderWithOneCandidate(string $email): string
{
    $h = "Line #\tCandidate Number\tEnrolment Date\tCandidate Name\tApplicant Last Name\t"
        ."Applicant First Name\tEmail Address\tExamination\tExam Type\tSubject\tPrice\tRole\tOrder Number\tSubject Area";
    $r = "2\t1796614\t16/04/2026 08:14:38\tJacob Goodwin\tGoodwin\tPhilip\t{$email}\tPiano Grade 5\t"
        ."Practical\t\t£99.00\tApplicant - Parent/Guardian\t1-16044465651\tMusic";

    return $h."\n".$r."\n";
}

function summaryForJacob(): string
{
    $h = "Subject Area\tSyllabus\tExamination Date\tExamination\tCandidate Number\tCandidate\tSchool\t"
        ."Teacher First Name\tTeacher Last Name\tStatus\tResult\tDigital Certificate ID\tOrder Number\tExaminer";
    $r = "Music\tPiano\t09/07/2026\tPiano Grade 5\t1796614\tJacob Goodwin\tLIPA High School\t\t\t"
        ."Certificate Printed\tMerit\t\t1-16044465651\t";

    return $h."\n".$r."\n";
}

/** Import Jacob through the per-candidate triple, as the page does, and return his saved entry. */
function importJacob(string $rowEmail, ?string $typedEmail): App\Models\ExamEntry
{
    App\Models\Order::create([
        'trinity_order_number' => '1-16044465651',
        'order_status' => 'Submitted',
        'subject_area' => 'Music',
        'delivery_method' => 'Face to Face',
        'requested_start_date' => '2026-07-01',
    ]);

    $importer = new TrinityCsvImporter();
    $summary = $importer->parseSummary(summaryForJacob());
    $enrol = $importer->parseEnrolment(f2fOrderWithOneCandidate($rowEmail), $summary['candidate_number']);
    $importer->commitCandidate($enrol, $summary, 80, '12/02/2010', $typedEmail, null);

    return App\Models\ExamEntry::where('candidate_number', '1796614')->sole();
}

test('an email typed on the Imports page is kept when Trinity left the row blank', function () {
    expect(importJacob('', 'philip.goodwin@example.org')->applicant_email)->toBe('philip.goodwin@example.org');
});

test('Trinity\'s own email still wins when the row has one', function () {
    expect(importJacob('goodwin@trinity-row.example', 'typed@example.org')->applicant_email)->toBe('goodwin@trinity-row.example');
});

test('with neither, the entry is saved without an email rather than refused', function () {
    expect(importJacob('', null)->applicant_email)->toBeNull();
});
