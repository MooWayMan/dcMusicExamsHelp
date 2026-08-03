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
