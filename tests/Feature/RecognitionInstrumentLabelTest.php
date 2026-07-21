<?php

// tests/Feature/RecognitionInstrumentLabelTest.php

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Rock & Pop drum kit is stored as the instrument "Drums" (Classical & Jazz
// percussion is "Drum Kit"). The public Recognition page tags genre in
// brackets for guitar/singing, so it renders "Drums" as "Drums (Rock/Pop)"
// for consistency — a display-only transform in ThankYouController. The
// stored instrument name stays "Drums" everywhere else.

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 5, 7, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function rilEntry(string $instrumentName, array $attrs = []): ExamEntry
{
    $date = $attrs['exam_date'] ?? Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-RIL-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);

    $instrument = Instrument::firstOrCreate(['name' => $instrumentName]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Test Candidate',
        'instrument_id' => $instrument->id,
        'grade' => '3',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'exam_date' => $date,
        'result' => 'Pass',
        'score' => 80,
        'show_on_thank_you' => true,
    ], $attrs));
}

test('Recognition page shows R&P Drums as "Drums (Rock/Pop)"', function () {
    rilEntry('Drums', ['candidate_name' => 'Drummer One']);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    $instruments = collect($q1['thankYouEntries'])->pluck('instrument');

    expect($instruments)->toContain('Drums (Rock/Pop)');
    expect($instruments)->not->toContain('Drums');
});

test('non-drum instruments are unchanged on the Recognition page', function () {
    rilEntry('Piano', ['candidate_name' => 'Pianist One', 'subject_area' => 'Piano']);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    $instruments = collect($q1['thankYouEntries'])->pluck('instrument');

    expect($instruments)->toContain('Piano');
});

test('R&P Keyboards shows as "Keyboards (Rock/Pop)"', function () {
    rilEntry('Keyboards', ['candidate_name' => 'Keys One']);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    expect(collect($q1['thankYouEntries'])->pluck('instrument'))->toContain('Keyboards (Rock/Pop)');
});

test('R&P Bass (stored "Bass Guitar") shows as "Bass (Rock/Pop)"', function () {
    rilEntry('Bass Guitar', ['candidate_name' => 'Bass One']);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    expect(collect($q1['thankYouEntries'])->pluck('instrument'))->toContain('Bass (Rock/Pop)');
});

test('R&P Vocals (stored "Singing (Rock/Pop)") shows as "Vocals (Rock/Pop)"', function () {
    rilEntry('Singing (Rock/Pop)', ['candidate_name' => 'Singer One']);

    $payload = $this->get('/recognition')->viewData('page')['props'];
    $q1 = collect($payload['allQuartersData'])->firstWhere('quarter', 1);

    $instruments = collect($q1['thankYouEntries'])->pluck('instrument');
    expect($instruments)->toContain('Vocals (Rock/Pop)')
        ->and($instruments)->not->toContain('Singing (Rock/Pop)');
});
