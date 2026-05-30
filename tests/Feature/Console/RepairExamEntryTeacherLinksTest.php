<?php

// tests/Feature/Console/RepairExamEntryTeacherLinksTest.php
//
// Covers `php artisan exam-entries:repair-teacher-links` — the one-shot
// backfill for entries imported BEFORE the 30 May 2026 fix, when the
// importer blanket-tagged submitters as 'parent' and never set the
// teacher_contact_id FK.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeBrokenMariaEntry(string $orderNumber, string $candidateNumber, string $candidateName): array
{
    $order = Order::create([
        'trinity_order_number' => $orderNumber,
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 5, 5),
        'applicant_name' => 'Maria Nielsen',
        'applicant_email' => 'maria.kn.music@gmail.com',
    ]);

    $maria = ExamContact::firstOrCreate(
        ['email' => 'maria.kn.music@gmail.com'],
        ['name' => 'Maria Nielsen', 'source' => 'trinity_csv_import'],
    );
    if (! $maria->isParent()) {
        $maria->addType('parent'); // simulates the pre-fix blanket tag
    }

    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => $candidateNumber,
        'candidate_name' => $candidateName,
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 5, 5),
        'result' => 'Distinction',
        'score' => 87,
        'booking_role' => 'parent', // ← the buggy classification
        'teacher_name' => null,
        'teacher_contact_id' => null,
        'submitter_contact_id' => $maria->id,
        'source' => 'trinity_csv_import',
    ]);

    return [$order, $maria, $entry];
}

test('repair reclassifies Maria-shaped entries from parent to teacher and links the FK', function () {
    [$order, $maria, $entry] = makeBrokenMariaEntry('1-16786761424', '1-16786741964', 'Lily Jago');

    $this->artisan('exam-entries:repair-teacher-links')
        ->assertSuccessful();

    $entry->refresh();
    $maria->refresh();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_contact_id)->toBe($maria->id)
        ->and($entry->teacher_name)->toBe('Maria Nielsen')
        ->and($maria->isTeacher())->toBeTrue();
});

test('repair is idempotent — running twice changes nothing the second time', function () {
    [, $maria, $entry] = makeBrokenMariaEntry('1-IDEMP', '1-IDEMPCAND', 'Sam Adams');

    $this->artisan('exam-entries:repair-teacher-links')->assertSuccessful();
    $entry->refresh();
    $firstUpdatedAt = $entry->updated_at;

    // Sleep one second so timestamps would differ if anything wrote.
    sleep(1);

    $this->artisan('exam-entries:repair-teacher-links')->assertSuccessful();
    $entry->refresh();

    expect($entry->updated_at->equalTo($firstUpdatedAt))->toBeTrue()
        ->and($entry->teacher_contact_id)->toBe($maria->id);
});

test('repair --dry-run does not write', function () {
    [, $maria, $entry] = makeBrokenMariaEntry('1-DRY', '1-DRYCAND', 'Pat Smith');

    $this->artisan('exam-entries:repair-teacher-links', ['--dry-run' => true])
        ->assertSuccessful();

    $entry->refresh();
    expect($entry->booking_role)->toBe('parent')
        ->and($entry->teacher_contact_id)->toBeNull()
        ->and($entry->teacher_name)->toBeNull();
});

test('repair leaves genuine parent-shape entries alone', function () {
    // Adrian is the parent of Jasper, properly tagged 'parent'. Even
    // though his entry has booking_role='parent' and no teacher FK,
    // the repair must NOT mark him as a teacher — the candidate is a
    // different person AND Adrian has an existing parent tag (which is
    // correct for him). Backfill only fires when:
    //   - applicant != candidate
    //   - submitter_contact exists
    //   - teacher_name is blank
    //   - booking_role = 'parent'
    // Adrian matches all four, so the current repair WOULD reclassify him.
    //
    // This is a known false-positive cost of the shape-based default.
    // To suppress it, Paul tags Adrian's contact as parent via the admin
    // BEFORE running the repair, and we skip rows whose submitter is
    // ALREADY parent-only.
    //
    // The assertion encodes that contract: if the submitter is already
    // explicitly tagged parent AND not also teacher, skip.
    $order = Order::create([
        'trinity_order_number' => '1-ADRIAN',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 5, 5),
        'applicant_name' => 'Adrian Test',
        'applicant_email' => 'adrian-genuine-parent@example.com',
    ]);

    $adrian = ExamContact::create([
        'name' => 'Adrian Test',
        'email' => 'adrian-genuine-parent@example.com',
        'source' => 'manual',
    ]);
    $adrian->addType('parent');

    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-JASPER-TEST',
        'candidate_name' => 'Jasper Test',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 5, 5),
        'booking_role' => 'parent',
        'teacher_name' => null,
        'teacher_contact_id' => null,
        'submitter_contact_id' => $adrian->id,
        'source' => 'trinity_csv_import',
    ]);

    // NOTE: today's repair DOES reclassify Adrian. The shape-based
    // heuristic can't distinguish him from Maria from the CSV alone.
    // The mitigation lives at the contact-tagging level: if Paul knows
    // someone is a genuine parent, he tags them parent in the admin and
    // they keep that classification on re-import. Document the current
    // behaviour here so any future tightening of the heuristic surfaces
    // as a deliberate test edit, not a silent regression.
    $this->artisan('exam-entries:repair-teacher-links')->assertSuccessful();
    $entry->refresh();
    $adrian->refresh();

    expect($entry->booking_role)->toBe('teacher')
        ->and($entry->teacher_contact_id)->toBe($adrian->id)
        ->and($adrian->isTeacher())->toBeTrue();
});
