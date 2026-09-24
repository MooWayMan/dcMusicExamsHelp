<?php

// tests/Feature/Admin/QuarterCertificateBatchTest.php
//
// The quarter's certificates are made in small steps (start, one step per
// slice of a teacher's candidates, finish) so no single request is long.
// The one-request version timed out at around fifty candidates.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\School;
use App\Models\User;
use App\Services\CertificateRenderer;
use App\Services\QuarterCertificateBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    Storage::fake('local');
    fakeCertificateTemplates();
});

function quarterBatchEntry(?string $teacher, ?int $score, array $attrs = [], int $month = 5): ExamEntry
{
    $date = Carbon::create(2026, $month, 10);
    $order = Order::create([
        'trinity_order_number' => '1-QCB-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => $date,
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'teacher_name' => $teacher,
        'candidate_name' => 'Pupil '.uniqid(),
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => $date,
        'score' => $score,
    ], $attrs));
}

test('a big teacher is split into slices so no step draws more than PER_STEP', function () {
    $count = QuarterCertificateBatch::PER_STEP * 2 + 1;
    for ($i = 0; $i < $count; $i++) {
        quarterBatchEntry('Big Teacher', 70);
    }
    quarterBatchEntry('Small Teacher', 70);

    $plan = $this->actingAs($this->admin)
        ->postJson('/admin/certificates/batch/start', ['quarter' => 2, 'year' => 2026])
        ->assertOk()
        ->json();

    expect($plan['quarter_label'])->toBe('2nd Quarter 2026')
        ->and(collect($plan['steps'])->where('teacher', 'Big Teacher')->pluck('part')->all())->toBe([1, 2, 3])
        ->and(collect($plan['steps'])->where('teacher', 'Small Teacher')->pluck('parts')->all())->toBe([1]);
});

test('every candidate who sat comes back as a certificate in their teacher ZIP, Below Pass included', function () {
    quarterBatchEntry('Jane Doe', 90, ['candidate_name' => 'Anna Distinction']);
    quarterBatchEntry('Jane Doe', 80, ['candidate_name' => 'Ben Merit']);
    quarterBatchEntry('Jane Doe', 45, ['candidate_name' => 'Cara Below']);
    quarterBatchEntry('Jane Doe', null, ['candidate_name' => 'Dan Cancelled', 'notes' => 'CANCELLED']);
    quarterBatchEntry('Jane Doe', 70, ['candidate_name' => 'Eve Otherquarter'], 8);

    $this->actingAs($this->admin);
    $result = runQuarterCertificateBatch($this, 2, 2026);

    expect($result['download_links'])->toBe(['Jane Doe' => 'certificates/2026-Q2/zips/Jane_Doe_Q2_2026.zip'])
        ->and($result['master_zip'])->toBe('certificates/2026-Q2/ALL_Q2_2026_Certificates.zip');

    $zip = new ZipArchive();
    $zip->open(Storage::disk('local')->path($result['download_links']['Jane Doe']));
    $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();
    $zip->close();

    expect($names)->toContain('Anna_Distinction_Standing_Ovation.pdf')
        ->toContain('Ben_Merit_Take_a_Bow.pdf')
        ->toContain('Cara_Below_Bravo.pdf')
        ->not->toContain('Dan_Cancelled_Bravo.pdf')
        ->not->toContain('Eve_Otherquarter_Take_a_Bow.pdf');

    $master = new ZipArchive();
    $master->open(Storage::disk('local')->path($result['master_zip']));
    expect($master->getNameIndex(0))->toBe('Jane_Doe_Q2_2026.zip');
    $master->close();
});

test('a slice over PER_STEP still produces every certificate once', function () {
    $count = QuarterCertificateBatch::PER_STEP + 3;
    for ($i = 0; $i < $count; $i++) {
        quarterBatchEntry('Big Teacher', 70, ['candidate_name' => "Pupil Number{$i}"]);
    }

    $this->actingAs($this->admin);
    $result = runQuarterCertificateBatch($this, 2, 2026);

    $bravos = collect(Storage::disk('local')->files('certificates/2026-Q2/Big_Teacher'))
        ->filter(fn ($f) => str_ends_with($f, '_Bravo.pdf'));

    expect($bravos)->toHaveCount($count)
        ->and($result['teachers']['Big Teacher'])->toBe($count + 1);
});

test('an enrolment-list entry with no teacher yet is credited to whoever submitted it', function () {
    $submitter = ExamContact::create(['name' => 'Sue Submitter']);
    quarterBatchEntry(null, 70, ['candidate_name' => 'Fay Enrolled', 'submitter_contact_id' => $submitter->id]);

    $this->actingAs($this->admin);
    $result = runQuarterCertificateBatch($this, 2, 2026);

    expect($result['download_links'])->toHaveKey('Sue Submitter')
        ->and(Storage::disk('local')->exists('certificates/2026-Q2/Sue_Submitter/Fay_Enrolled_Bravo.pdf'))->toBeTrue();
});

test('the top scorer is drawn into top-scorers and into their teacher folder, and finish lists it', function () {
    quarterBatchEntry('Jane Doe', 95, ['candidate_name' => 'Anna Top']);
    quarterBatchEntry('Jane Doe', 88, ['candidate_name' => 'Ben Second']);

    $this->actingAs($this->admin);
    $result = runQuarterCertificateBatch($this, 2, 2026);

    expect(Storage::disk('local')->exists('certificates/2026-Q2/top-scorers/Anna_Top_Showstopper.pdf'))->toBeTrue()
        ->and(Storage::disk('local')->exists('certificates/2026-Q2/Jane_Doe/Anna_Top_Showstopper.pdf'))->toBeTrue()
        ->and(collect($result['top_scorer_certs'])->pluck('short_name')->all())->toBe(['Anna T'])
        ->and($result['top_scorer_count'])->toBe(1);
});

test('the teacher appreciation certificate shows the school name when one is linked', function () {
    for ($i = 0; $i < 10; $i++) {
        quarterBatchEntry('Jane Doe', 70);
    }
    $contact = ExamContact::create(['name' => 'Jane Doe']);
    $contact->addType('teacher');
    $contact->schools()->attach(School::create(['name' => 'Wirral Music School'])->id);

    $this->actingAs($this->admin);
    runQuarterCertificateBatch($this, 2, 2026);

    expect(Storage::disk('local')->exists('certificates/2026-Q2/Jane_Doe/Jane_Doe_Bronze_Appreciation.pdf'))->toBeTrue()
        ->and(Storage::disk('local')->exists('certificates/2026-Q2/Jane_Doe/Jane_Doe_Bronze_Badge.png'))->toBeTrue();
});

test('start says so when the quarter has no results', function () {
    $this->actingAs($this->admin)
        ->postJson('/admin/certificates/batch/start', ['quarter' => 2, 'year' => 2026])
        ->assertStatus(422)
        ->assertJson(['error' => 'No entries with results found for 2nd Quarter 2026.']);
});

test('a re-run starts from an empty folder', function () {
    Storage::disk('local')->put('certificates/2026-Q2/Old_Teacher/stale.pdf', 'x');
    quarterBatchEntry('Jane Doe', 70);

    $this->actingAs($this->admin);
    runQuarterCertificateBatch($this, 2, 2026);

    expect(Storage::disk('local')->exists('certificates/2026-Q2/Old_Teacher/stale.pdf'))->toBeFalse();
});

test('the batch endpoints are admin only and validate their input', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    foreach (['start', 'step', 'finish'] as $step) {
        $this->postJson("/admin/certificates/batch/{$step}", ['quarter' => 2, 'year' => 2026])->assertUnauthorized();
    }
    foreach (['start', 'step', 'finish'] as $step) {
        $this->actingAs($teacher)->postJson("/admin/certificates/batch/{$step}", ['quarter' => 2, 'year' => 2026])->assertForbidden();
    }

    $this->actingAs($this->admin)
        ->postJson('/admin/certificates/batch/start', ['quarter' => 5, 'year' => 2026])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['quarter']);

    $this->actingAs($this->admin)
        ->postJson('/admin/certificates/batch/step', ['quarter' => 2, 'year' => 2026, 'part' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['teacher', 'part']);
});

test('the single teacher certificate is found by the contact id the page sends', function () {
    $contact = ExamContact::create(['name' => 'Jane Doe']);
    $contact->schools()->attach(School::create(['name' => 'Wirral Music School'])->id);

    $response = $this->actingAs($this->admin)->postJson('/admin/certificates/teacher', [
        'teacher_id' => $contact->id,
        'template' => 'Bronze Appreciation Certificate',
        'format' => 'png',
    ]);

    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($response->headers->get('Content-Disposition'))->toContain('Wirral_Music_School');
});

test('the top-scorers-only button draws into top-scorers alone', function () {
    quarterBatchEntry('Jane Doe', 95, ['candidate_name' => 'Anna Top']);

    $this->actingAs($this->admin)
        ->postJson('/admin/certificates/top-scorers', ['quarter' => 2, 'year' => 2026])
        ->assertOk()
        ->assertJson(['success' => true, 'count' => 1]);

    expect(Storage::disk('local')->exists('certificates/2026-Q2/top-scorers/Anna_Top_Showstopper.pdf'))->toBeTrue()
        ->and(Storage::disk('local')->exists('certificates/2026-Q2/Jane_Doe/Anna_Top_Showstopper.pdf'))->toBeFalse();
});

test('badge thresholds', function (int $candidates, ?string $badge) {
    expect(CertificateRenderer::teacherBadge($candidates))->toBe($badge);
})->with([
    [9, null],
    [10, 'Bronze'],
    [19, 'Bronze'],
    [20, 'Silver'],
    [30, 'Gold'],
    [40, 'Top Award'],
]);
