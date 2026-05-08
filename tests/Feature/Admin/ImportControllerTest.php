<?php

// tests/Feature/Admin/ImportControllerTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);

    // Seed a minimal instrument list so candidate-triple tests can map.
    foreach (['Trumpet', 'Piano', 'Drum Kit', 'Guitar (Rock/Pop)', 'Singing (Classical)'] as $name) {
        Instrument::firstOrCreate(['name' => $name]);
    }
});

/**
 * Build a CSV file the way Trinity exports it (UTF-16 LE BOM, CRLF) for
 * use with $this->post([..., 'file' => UploadedFile::fake()->createWithContent(...)]).
 */
function importTestCsv(array $lines, string $filename = 'export.CSV'): UploadedFile
{
    $body = implode("\r\n", $lines) . "\r\n";
    $bytes = "\xFF\xFE" . mb_convert_encoding($body, 'UTF-16LE', 'UTF-8');
    $tmp = tempnam(sys_get_temp_dir(), 'tcsv');
    file_put_contents($tmp, $bytes);
    return new UploadedFile($tmp, $filename, 'text/csv', null, true);
}

// ──────────────────────────────────────────────────────────────────
// Auth / route
// ──────────────────────────────────────────────────────────────────

test('non-admin cannot reach /admin/imports', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $this->actingAs($teacher)->get('/admin/imports')->assertStatus(403);
});

test('guests are redirected to login', function () {
    $this->get('/admin/imports')->assertRedirect('/login');
});

test('admin can load /admin/imports', function () {
    $this->actingAs($this->admin)->get('/admin/imports')
        ->assertStatus(200)
        ->assertInertia(fn ($p) => $p->component('admin/Imports/Index'));
});

// ──────────────────────────────────────────────────────────────────
// Section 1 — bulk orders
// ──────────────────────────────────────────────────────────────────

test('Section 1 preview returns expected counts and quarter filter excludes other quarters', function () {
    $file = importTestCsv([
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '15/04/2026 00:00:00,Digital,1-Q2-A,Music,1,,Ready to Deliver',          // Q2
        '20/05/2026 00:00:00,Default,1-Q2-B,Music,11,Wirral School of Music,Processed', // Q2
        '01/02/2026 00:00:00,Digital,1-Q1-X,Music,1,,Delivered',                  // Q1 — filtered out
    ]);

    $response = $this->actingAs($this->admin)->post('/admin/imports/preview-orders', [
        'file' => $file,
        'year' => 2026,
        'quarter' => 2,
    ]);

    $response->assertStatus(200);
    $json = $response->json();

    expect($json['totals']['rows_in_csv'])->toBe(3);
    expect($json['totals']['in_quarter'])->toBe(2);
    expect($json['totals']['filtered_out'])->toBe(1);
    expect($json['totals']['to_create'])->toBe(2);
    expect($json['totals']['to_update'])->toBe(0);
});

test('Section 1 commit creates orders idempotently (re-run is a no-op)', function () {
    $build = fn () => importTestCsv([
        'Requested Start Date,Delivery Method,Order #,Subject Area,Candidates,Venue,Order Status',
        '15/04/2026 00:00:00,Digital,1-IMP-1,Music,1,,Ready to Deliver',
        '20/05/2026 00:00:00,Default,1-IMP-2,Music,11,Wirral School of Music,Processed',
    ]);

    $this->actingAs($this->admin)->post('/admin/imports/commit-orders', [
        'file' => $build(),
        'year' => 2026,
        'quarter' => 2,
    ])->assertRedirect();

    expect(Order::where('trinity_order_number', '1-IMP-1')->exists())->toBeTrue();
    expect(Order::where('trinity_order_number', '1-IMP-2')->exists())->toBeTrue();
    $countAfterFirst = Order::count();

    // Re-run with the same data — should not duplicate.
    $this->actingAs($this->admin)->post('/admin/imports/commit-orders', [
        'file' => $build(),
        'year' => 2026,
        'quarter' => 2,
    ])->assertRedirect();

    expect(Order::count())->toBe($countAfterFirst);
});

// ──────────────────────────────────────────────────────────────────
// Section 2 — per-candidate triple
// ──────────────────────────────────────────────────────────────────

function enrolmentCsv(array $overrides = []): UploadedFile
{
    $defaults = [
        'examination' => 'Classical and Jazz Technical Grade 2 (Digital)',
        'subject' => 'Trumpet',
        'candidate_number' => '1-CAND-1',
        'candidate_name' => 'Megan Roberts',
        'enrolment_date' => '08/04/2026 00:00:00',
        'price' => '£61.00',
        'submitter_last' => 'Sheridan',
        'submitter_first' => 'Paul',
        'submitter_email' => 'madmusic6@hotmail.com',
        'applicant_id' => '1-4781714763',
        'applicant_last' => 'Sheridan',
        'applicant_first' => 'Paul',
    ];
    $r = array_merge($defaults, $overrides);
    return importTestCsv([
        'Examination,Subject,Candidate Number,Candidate Name,Enrolment Date,Price,Submitter Last Name,Submitter First Name,Submitter Email Address,Applicant Id,Applicant Last Name,Applicant First Name',
        "{$r['examination']},{$r['subject']},{$r['candidate_number']},{$r['candidate_name']},{$r['enrolment_date']},{$r['price']},{$r['submitter_last']},{$r['submitter_first']},{$r['submitter_email']},{$r['applicant_id']},{$r['applicant_last']},{$r['applicant_first']}",
        'Centre Commission - Classical and Jazz (Digital),,, ,08/04/2026 14:58:53,(£12.20),,,,,,',
    ]);
}

function summaryCsv(array $overrides = []): UploadedFile
{
    $defaults = [
        'subject_area' => 'Music',
        'syllabus' => 'Classical and Jazz (Digital)',
        'examination_date' => '08/04/2026',
        'examination' => 'Classical and Jazz Technical Grade 2 (Digital)',
        'candidate_number' => '1-CAND-1',
        'candidate' => 'Megan Roberts',
        'school' => '',
        'teacher_first' => '',
        'teacher_last' => '',
        'status' => 'Certificate Printed',
        'result' => 'Merit',
        'digital_certificate_id' => '19603896',
        'order_number' => '1-IMP-ORDER',
        'examiner' => '',
    ];
    $r = array_merge($defaults, $overrides);
    return importTestCsv([
        'Subject Area,Syllabus,Examination Date,Examination,Candidate Number,Candidate,School,Teacher First Name,Teacher Last Name,Status,Result,Digital Certificate ID,Order Number,Examiner',
        "{$r['subject_area']},{$r['syllabus']},{$r['examination_date']},{$r['examination']},{$r['candidate_number']},{$r['candidate']},{$r['school']},{$r['teacher_first']},{$r['teacher_last']},{$r['status']},{$r['result']},{$r['digital_certificate_id']},{$r['order_number']},{$r['examiner']}",
    ]);
}

function marksheetCsv(int ...$marks): UploadedFile
{
    $lines = ['Section #,Mark,Section,Max'];
    foreach ($marks as $i => $m) {
        $lines[] = ($i + 1) . ',' . $m . ',Section ' . ($i + 1) . ',';
    }
    return importTestCsv($lines);
}

test('Section 2 commit creates an exam_entry linked to the existing order with parsed grade and score', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    // Default fixture: Megan Roberts (candidate) entered by Paul Sheridan
    // (applicant + submitter). Submitter == Applicant, so applicant_email
    // is auto-derived from Submitter Email Address — no form input needed.
    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv(),
        'summary' => summaryCsv(),
        'marksheet' => marksheetCsv(18, 17, 18, 11, 7, 7),
    ])->assertRedirect();

    $entry = ExamEntry::where('candidate_number', '1-CAND-1')->first();
    expect($entry)->not->toBeNull();
    expect($entry->score)->toBe(78);
    expect($entry->grade)->toBe('2');
    expect($entry->delivery_method)->toBe('Digital');
    // Megan != Paul → not 'self'. No teacher in summary → not 'teacher'.
    // No matching contact → default 'parent'.
    expect($entry->booking_role)->toBe('parent');
    // Email should auto-fill from submitter when names match the applicant.
    expect($entry->applicant_email)->toBe('madmusic6@hotmail.com');
});

test('booking_role = self when applicant name matches candidate name', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv([
            'candidate_name' => 'Paul Sheridan',
            'applicant_first' => 'Paul',
            'applicant_last' => 'Sheridan',
        ]),
        'summary' => summaryCsv(['candidate' => 'Paul Sheridan']),
        'marksheet' => marksheetCsv(20),
    ])->assertRedirect();

    expect(ExamEntry::where('candidate_number', '1-CAND-1')->value('booking_role'))->toBe('self');
});

test('booking_role = teacher when summary teacher matches applicant name', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv([
            'candidate_name' => 'Some Student',
            'applicant_first' => 'Sarah',
            'applicant_last' => 'Mitchell',
            'submitter_first' => 'Sarah',
            'submitter_last' => 'Mitchell',
            'submitter_email' => 'sarah@example.com',
        ]),
        'summary' => summaryCsv([
            'candidate' => 'Some Student',
            'teacher_first' => 'Sarah',
            'teacher_last' => 'Mitchell',
        ]),
        'marksheet' => marksheetCsv(60),
    ])->assertRedirect();

    expect(ExamEntry::where('candidate_number', '1-CAND-1')->value('booking_role'))->toBe('teacher');
});

test('booking_role = parent by default when names differ and no contact match', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv([
            'candidate_name' => 'Tilly Lamb',
            'applicant_first' => 'Mary',
            'applicant_last' => 'Lamb',
            'submitter_first' => 'Paul',
            'submitter_last' => 'Sheridan',
            'submitter_email' => 'madmusic6@hotmail.com',
        ]),
        'summary' => summaryCsv(['candidate' => 'Tilly Lamb']),
        'marksheet' => marksheetCsv(70),
        // Submitter != Applicant → controller requires applicant_email.
        'applicant_email' => 'mary@example.com',
    ])->assertRedirect();

    expect(ExamEntry::where('candidate_number', '1-CAND-1')->value('booking_role'))->toBe('parent');
});

test('Section 2 errors when the matched order does not exist', function () {
    // No Order created — Section 1 wasn't run.
    $response = $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv(),
        'summary' => summaryCsv(),
        'marksheet' => marksheetCsv(60),
    ]);

    // Inertia/Laravel back()->withErrors → 302 with errors session bag.
    $response->assertSessionHasErrors();
});

test('candidate-number mismatch between Enrolment and Summary returns validation error', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    $response = $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', [
        'enrolment' => enrolmentCsv(['candidate_number' => '1-CAND-A']),
        'summary' => summaryCsv(['candidate_number' => '1-CAND-B']),
        'marksheet' => marksheetCsv(60),
    ]);

    $response->assertSessionHasErrors();
});

test('re-running Section 2 with the same triple does not duplicate the entry', function () {
    Order::create([
        'trinity_order_number' => '1-IMP-ORDER',
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-04-08',
    ]);

    $payload = fn () => [
        'enrolment' => enrolmentCsv(),
        'summary' => summaryCsv(),
        'marksheet' => marksheetCsv(78),
    ];

    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', $payload())->assertRedirect();
    $this->actingAs($this->admin)->post('/admin/imports/commit-candidate', $payload())->assertRedirect();

    expect(ExamEntry::where('candidate_number', '1-CAND-1')->count())->toBe(1);
});
