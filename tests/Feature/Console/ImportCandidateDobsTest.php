<?php

use App\Models\ExamEntry;
use App\Models\Order;
use Illuminate\Support\Facades\File;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// students:import-dobs <csv> — populates exam_entries.date_of_birth from a
// CSV with candidate_number + date_of_birth columns. Match by candidate_number.
// ──────────────────────────────────────────

// Names prefixed with `dobImport_` to avoid clashing with helpers of the same
// shorthand in TeacherPrizeDrawExclusionTest and MarkOrderPaidTest — Pest
// declares these in the global function table so duplicates are a fatal.

function dobImport_makeOrder(): Order
{
    return Order::create([
        'trinity_order_number' => 'ORD-'.fake()->unique()->numerify('#######'),
        'order_status' => 'Submitted',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'requested_start_date' => '2026-03-01',
    ]);
}

function dobImport_makeEntry(string $candidateNumber, string $name = 'Test Candidate'): ExamEntry
{
    return ExamEntry::create([
        'order_id' => dobImport_makeOrder()->id,
        'candidate_number' => $candidateNumber,
        'candidate_name' => $name,
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
    ]);
}

function dobImport_writeCsv(string $contents): string
{
    $path = storage_path('app/test-dobs-'.uniqid().'.csv');
    File::put($path, $contents);

    return $path;
}

test('imports a single DOB matched by candidate_number', function () {
    $entry = dobImport_makeEntry('1-12345', 'Freddie Smith');

    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-12345,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth->format('Y-m-d'))->toBe('2014-05-14');
});

test('accepts ISO YYYY-MM-DD as well as DD/MM/YYYY', function () {
    $entry = dobImport_makeEntry('1-99999');
    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-99999,2014-05-14\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth->format('Y-m-d'))->toBe('2014-05-14');
});

test('updates every exam_entries row sharing the same candidate_number', function () {
    $a = dobImport_makeEntry('1-12345', 'Freddie Smith — entry 1');
    $b = dobImport_makeEntry('1-12345', 'Freddie Smith — entry 2');

    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-12345,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($a->fresh()->date_of_birth?->format('Y-m-d'))->toBe('2014-05-14');
    expect($b->fresh()->date_of_birth?->format('Y-m-d'))->toBe('2014-05-14');
});

test('blank DOB rows are silently skipped', function () {
    $entry = dobImport_makeEntry('1-55555');

    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-55555,\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth)->toBeNull();
});

test('a row with no matching candidate_number is reported but does not fail', function () {
    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-NOSUCH,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);
});

test('an invalid date is reported but does not fail the run', function () {
    $entry = dobImport_makeEntry('1-77777');

    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-77777,not-a-date\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth)->toBeNull();
});

test('--dry-run does not persist changes', function () {
    $entry = dobImport_makeEntry('1-DRY01');

    $csv = dobImport_writeCsv("candidate_number,date_of_birth\n1-DRY01,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv, '--dry-run' => true])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth)->toBeNull();
});

test('tolerates a TablePlus-style filename pseudo-header on line 1', function () {
    $entry = dobImport_makeEntry('1-TPLUS1');

    // TablePlus exports CSV with the source filename on the first line,
    // then the actual column headers on line 2.
    $csv = dobImport_writeCsv("prod-2026-04-25-pre-unified-refactor.sql\ncandidate_number,date_of_birth\n1-TPLUS1,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth?->format('Y-m-d'))->toBe('2014-05-14');
});

test('header columns are normalised case-insensitively', function () {
    $entry = dobImport_makeEntry('1-CASE1');

    $csv = dobImport_writeCsv("Candidate Number,Date of Birth\n1-CASE1,14/05/2014\n");

    $this->artisan('students:import-dobs', ['path' => $csv])->assertExitCode(0);

    expect($entry->fresh()->date_of_birth?->format('Y-m-d'))->toBe('2014-05-14');
});

test('a missing CSV path returns failure', function () {
    $this->artisan('students:import-dobs', ['path' => '/no/such/file.csv'])->assertExitCode(1);
});
