<?php

// tests/Feature/RetagRockPopKeyboardsTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ekInstrument(): Instrument
{
    return Instrument::firstOrCreate(['name' => 'Electronic Keyboard'], ['family' => 'Keyboard']);
}

function makeKeyboardEntry(Instrument $instrument, string $subjectArea, ?ExamContact $teacher = null): ExamEntry
{
    return ExamEntry::create([
        'order_id' => Order::factory()->create()->id,
        'instrument_id' => $instrument->id,
        'subject_area' => $subjectArea,
        'candidate_name' => 'Test Candidate',
        'grade' => 'Grade 1',
        'teacher_contact_id' => $teacher?->id,
        'booking_role' => $teacher ? 'teacher' : null,
    ]);
}

test('it moves Rock & Pop keyboard entries (any spelling) to "Keyboards" and leaves C&J as "Electronic Keyboard"', function () {
    $ek = ekInstrument();
    $rpAnd = makeKeyboardEntry($ek, 'Rock and Pop');
    $rpAmp = makeKeyboardEntry($ek, 'Rock & Pop');
    $cjMusic = makeKeyboardEntry($ek, 'Music');

    $this->artisan('exam:retag-rp-keyboards')->assertSuccessful();

    $keyboards = Instrument::where('name', 'Keyboards')->first();
    expect($keyboards)->not->toBeNull()
        ->and($rpAnd->fresh()->instrument_id)->toBe($keyboards->id)
        ->and($rpAmp->fresh()->instrument_id)->toBe($keyboards->id)
        ->and($cjMusic->fresh()->instrument_id)->toBe($ek->id);
});

test('it rebuilds the teacher instrument link off "Electronic Keyboard" onto "Keyboards"', function () {
    $ek = ekInstrument();
    $teacher = ExamContact::create(['name' => 'Kay Keys', 'role' => 'teacher']);
    makeKeyboardEntry($ek, 'Rock and Pop', $teacher);

    // Simulate the stale pre-split link (R&P keyboard pointed at Electronic Keyboard).
    $teacher->instruments()->syncWithoutDetaching([$ek->id]);

    $this->artisan('exam:retag-rp-keyboards')->assertSuccessful();

    $keyboards = Instrument::where('name', 'Keyboards')->firstOrFail();
    $names = $teacher->fresh()->instruments->pluck('name');

    expect($names)->toContain('Keyboards')
        ->and($names)->not->toContain('Electronic Keyboard');
});

test('dry run changes nothing', function () {
    $ek = ekInstrument();
    $rp = makeKeyboardEntry($ek, 'Rock and Pop');

    $this->artisan('exam:retag-rp-keyboards --dry-run')->assertSuccessful();

    expect($rp->fresh()->instrument_id)->toBe($ek->id)
        ->and(Instrument::where('name', 'Keyboards')->exists())->toBeFalse();
});

test('running it twice is idempotent', function () {
    $ek = ekInstrument();
    $rp = makeKeyboardEntry($ek, 'Rock and Pop');

    $this->artisan('exam:retag-rp-keyboards')->assertSuccessful();
    $this->artisan('exam:retag-rp-keyboards')->assertSuccessful();

    $keyboards = Instrument::where('name', 'Keyboards')->firstOrFail();
    expect($rp->fresh()->instrument_id)->toBe($keyboards->id)
        ->and(ExamEntry::where('instrument_id', $ek->id)->count())->toBe(0);
});
