<?php

// tests/Feature/RecognitionPassFailOrderTest.php
//
// The public Recognition page names Distinction and Merit. Everyone else who
// sat the exam is one alphabetical group: a candidate who did not pass must
// not be told apart from one who did, by label, by sort position, or in the
// payload. Found 11 Sep 2026 via Iris M (Piano Initial, Below Pass), who was
// listed last beneath every pass.

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 5, 7, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function rpfEntry(string $candidateName, ?int $score): ExamEntry
{
    $date = Carbon::create(2026, 2, 15);

    $order = Order::create([
        'trinity_order_number' => '1-RPF-'.uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => $date,
    ]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);

    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => $candidateName,
        'instrument_id' => $piano->id,
        'grade' => 'Initial',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => $date,
        'score' => $score,
        'show_on_thank_you' => true,
    ]);
}

function rpfQuarterOne($test): array
{
    $props = $test->get('/recognition')->viewData('page')['props'];

    return collect($props['allQuartersData'])->firstWhere('quarter', 1);
}

test('a Below Pass sorts alphabetically among the passes, not after them', function () {
    rpfEntry('Zara Young', 70);
    rpfEntry('Iris McBride', 45);
    rpfEntry('Alfie Turner', 65);
    rpfEntry('Wendy Waiting', null);
    rpfEntry('Mia Merit', 80);
    rpfEntry('Dan Distinction', 90);

    $names = collect(rpfQuarterOne($this)['thankYouEntries'])->pluck('name')->all();

    expect($names)->toBe(['Dan D', 'Mia M', 'Alfie T', 'Iris M', 'Zara Y', 'Wendy W']);
});

test('a pass and a fail come back with the same public result and certificate', function () {
    rpfEntry('Alfie Turner', 65);
    rpfEntry('Iris McBride', 45);

    $entries = collect(rpfQuarterOne($this)['thankYouEntries'])->keyBy('name');

    expect($entries['Iris M']['result'])->toBe($entries['Alfie T']['result'])
        ->and($entries['Iris M']['certificate'])->toBe($entries['Alfie T']['certificate']);
});

test('the Recognition payload never carries Pass or Below Pass', function () {
    rpfEntry('Alfie Turner', 65);
    rpfEntry('Iris McBride', 45);
    rpfEntry('Mia Merit', 80);

    $quarter = rpfQuarterOne($this);

    expect(collect($quarter['thankYouEntries'])->pluck('result')->unique()->sort()->values()->all())
        ->toBe(['Merit', 'Sat'])
        ->and(json_encode($quarter))->not->toContain('Below Pass');
});
