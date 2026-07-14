<?php
// tests/Feature/Console/LinkF2FTeachersTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f2fWriteLinksCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'links').'.csv';
    $fh = fopen($path, 'w');
    fputcsv($fh, ['candidate_number', 'teacher_name', 'teacher_email', 'school_name']);
    foreach ($rows as $r) {
        fputcsv($fh, $r);
    }
    fclose($fh);

    return $path;
}

function f2fLinkTestEntry(string $candidateNumber): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-16044465651',
        'delivery_method' => 'Default',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Learn Music Ltd',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-07-09',
    ]);

    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => $candidateNumber,
        'candidate_name' => 'Test Candidate',
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'exam_date' => '2026-07-09',
        'result' => 'Pass',
        'score' => 65,
        'teacher_name' => null,
        'teacher_contact_id' => null,
        'source' => 'results_scan',
    ]);
}

it('creates a teacher contact and links the entry', function () {
    $entry = f2fLinkTestEntry('1796684');
    $csv = f2fWriteLinksCsv([
        ['1796684', 'Helen Hodgkiss', 'gold.musictuition@gmail.com', 'Prelude School of Music'],
    ]);

    $this->artisan('f2f:link-teachers', ['path' => $csv])->assertExitCode(0);

    $contact = ExamContact::findByEmail('gold.musictuition@gmail.com');
    expect($contact)->not->toBeNull();
    expect($contact->name)->toBe('Helen Hodgkiss');

    $this->assertDatabaseHas('contact_types', [
        'exam_contact_id' => $contact->id,
        'type' => 'teacher',
    ]);

    $entry->refresh();
    expect($entry->teacher_contact_id)->toBe($contact->id);
    expect($entry->teacher_name)->toBe('Helen Hodgkiss');
    expect($entry->school_name)->toBe('Prelude School of Music');
});

it('reuses an existing contact by email instead of duplicating', function () {
    $existing = ExamContact::create([
        'name' => 'Daniel Rogers',
        'email' => 'exams@pulsemusicliverpool.com',
    ]);
    $entry = f2fLinkTestEntry('1-17563392249');

    $csv = f2fWriteLinksCsv([
        ['1-17563392249', 'Daniel Rogers', 'exams@pulsemusicliverpool.com', 'Pulse Music and Education'],
    ]);

    $this->artisan('f2f:link-teachers', ['path' => $csv])->assertExitCode(0);

    expect(ExamContact::where('email', 'exams@pulsemusicliverpool.com')->count())->toBe(1);
    expect($entry->refresh()->teacher_contact_id)->toBe($existing->id);
});

it('writes nothing on a dry run', function () {
    $entry = f2fLinkTestEntry('1796684');
    $csv = f2fWriteLinksCsv([
        ['1796684', 'Helen Hodgkiss', 'gold.musictuition@gmail.com', 'Prelude School of Music'],
    ]);

    $this->artisan('f2f:link-teachers', ['path' => $csv, '--dry-run' => true])->assertExitCode(0);

    expect(ExamContact::count())->toBe(0);
    expect($entry->refresh()->teacher_contact_id)->toBeNull();
});

it('reports a candidate with no matching entry and still exits cleanly', function () {
    $csv = f2fWriteLinksCsv([
        ['9999999', 'Nobody Here', 'nobody@example.com', ''],
    ]);

    $this->artisan('f2f:link-teachers', ['path' => $csv])
        ->expectsOutputToContain('No exam entry found')
        ->assertExitCode(0);
});

it('fails cleanly when the CSV path is missing', function () {
    $this->artisan('f2f:link-teachers', ['path' => '/no/such/file.csv'])
        ->assertExitCode(1);
});
