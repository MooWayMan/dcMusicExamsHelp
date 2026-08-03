<?php

// tests/Feature/Admin/ReEntryPermitUploadTest.php
//
// /admin/re-entry-permits — drop the permit PDFs Trinity issues when a booked
// candidate doesn't sit.
//
// The permits are matched on CANDIDATE NUMBER, which is the whole reason the
// enrolment list has to be imported first: Sam Dobie's permit carries
// 1-17563392237, and if no entry holds that number there is nothing to mark.

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->admin = User::factory()->create(['role' => 'admin']);
});

/**
 * A spec-compliant one-page PDF carrying permit text.
 *
 * It needs a real xref table and startxref offset — smalot/pdfparser walks
 * those, and a PDF without them parses to an empty string, which the
 * controller correctly reports as "not a permit".
 */
function permitPdf(string $name, string $candidateNumber, string $code, string $exam = 'Rock and Pop Guitar Grade 4'): UploadedFile
{
    $lines = [
        'Re-entry Permit',
        'Date of issue: 14/07/2026',
        "Candidate Name: {$name}",
        "Candidate Id: {$candidateNumber}",
        'Subject: Rock and Pop',
        "Exam: {$exam}",
        'Valid Until: 14/07/2027',
        'Status: Valid',
        "Code: {$code}",
        'Credit Discount: 100%',
    ];

    $stream = '';
    $y = 760;
    foreach ($lines as $line) {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
        $stream .= "BT /F1 11 Tf 60 {$y} Td ({$escaped}) Tj ET\n";
        $y -= 20;
    }

    $objects = [
        1 => "<</Type/Catalog/Pages 2 0 R>>",
        2 => "<</Type/Pages/Kids[3 0 R]/Count 1>>",
        3 => "<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>",
        4 => "<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>",
        5 => "<</Length ".strlen($stream).">>stream\n{$stream}endstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<</Size ".(count($objects) + 1)."/Root 1 0 R>>\nstartxref\n{$xrefOffset}\n%%EOF";

    $path = tempnam(sys_get_temp_dir(), 'permit').'.pdf';
    file_put_contents($path, $pdf);

    return new UploadedFile($path, str_replace(' ', '_', $name).'_Voucher.pdf', 'application/pdf', null, true);
}

function withdrawnEntry(string $candidateNumber, string $name): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-PERM-'.uniqid('', true),
        'delivery_method' => 'Default',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => Carbon::create(2026, 7, 11),
    ]);

    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => $candidateNumber,
        'candidate_name' => $name,
        'grade' => '4',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Default',
        'score' => null,
        'result' => null,
    ]);
}

test('the page loads', function () {
    $this->actingAs($this->admin)
        ->get('/admin/re-entry-permits')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('admin/ReEntryPermits/Index'));
});

test('a permit matched to an entry previews as ready', function () {
    withdrawnEntry('1-17563392237', 'Sam Dobie');

    $this->actingAs($this->admin)
        ->post('/admin/re-entry-permits/preview', [
            'files' => [permitPdf('Sam Dobie', '1-17563392237', '1-18154879067')],
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.status', 'ready')
        ->assertJsonPath('rows.0.candidate_name', 'Sam Dobie')
        ->assertJsonPath('rows.0.code', '1-18154879067');
});

test('committing marks the entry and stores the code', function () {
    $entry = withdrawnEntry('1-17563392237', 'Sam Dobie');

    $this->actingAs($this->admin)
        ->post('/admin/re-entry-permits/commit', [
            'files' => [permitPdf('Sam Dobie', '1-17563392237', '1-18154879067')],
        ])
        ->assertRedirect('/admin/re-entry-permits');

    $entry->refresh();

    expect($entry->notes)->toBe(ExamEntry::NOTE_RE_ENTRY)
        ->and($entry->re_entry_code)->toBe('1-18154879067')
        // And therefore out of the student draw, certificates and Recognition.
        ->and(ExamEntry::whereResultPossible()->count())->toBe(0);
});

test('a permit with no matching entry says so instead of failing silently', function () {
    // Exactly today's situation: the enrolment list hasn't been imported, so
    // Sam Dobie has no row at all.
    $this->actingAs($this->admin)
        ->post('/admin/re-entry-permits/preview', [
            'files' => [permitPdf('Sam Dobie', '1-17563392237', '1-18154879067')],
        ])
        ->assertOk()
        ->assertJsonPath('rows.0.status', 'not_found');
});

test('several permits are handled in one drop', function () {
    withdrawnEntry('1-17563392237', 'Sam Dobie');
    withdrawnEntry('1-17572882641', 'Keane Branch-Curtis');

    $this->actingAs($this->admin)
        ->post('/admin/re-entry-permits/commit', [
            'files' => [
                permitPdf('Sam Dobie', '1-17563392237', '1-18154879067'),
                permitPdf('Keane Branch-Curtis', '1-17572882641', '1-18155075732', 'Rock and Pop Guitar Grade 1'),
            ],
        ])
        ->assertRedirect('/admin/re-entry-permits');

    expect(ExamEntry::where('notes', ExamEntry::NOTE_RE_ENTRY)->count())->toBe(2);
});

test('a non-admin cannot reach it', function () {
    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->get('/admin/re-entry-permits')
        ->assertForbidden();
});
