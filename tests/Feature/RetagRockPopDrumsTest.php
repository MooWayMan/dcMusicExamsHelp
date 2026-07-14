<?php

// tests/Feature/RetagRockPopDrumsTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function drumKitInstrument(): Instrument
{
    return Instrument::firstOrCreate(['name' => 'Drum Kit'], ['family' => 'Percussion']);
}

function makeEntry(Instrument $instrument, string $subjectArea, ?ExamContact $teacher = null): ExamEntry
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

test('it moves Rock & Pop drum entries (any spelling) to "Drums" and leaves C&J as "Drum Kit"', function () {
    $drumKit = drumKitInstrument();
    $rpAnd = makeEntry($drumKit, 'Rock and Pop');
    $rpAmp = makeEntry($drumKit, 'Rock & Pop');
    $cjMusic = makeEntry($drumKit, 'Music');
    $cjFull = makeEntry($drumKit, 'Classical & Jazz');

    $this->artisan('exam:retag-rp-drums')->assertSuccessful();

    $drums = Instrument::where('name', 'Drums')->first();
    expect($drums)->not->toBeNull()
        ->and($rpAnd->fresh()->instrument_id)->toBe($drums->id)
        ->and($rpAmp->fresh()->instrument_id)->toBe($drums->id)
        ->and($cjMusic->fresh()->instrument_id)->toBe($drumKit->id)
        ->and($cjFull->fresh()->instrument_id)->toBe($drumKit->id);
});

test('it rebuilds the teacher instrument link off "Drum Kit" onto "Drums"', function () {
    $drumKit = drumKitInstrument();
    $teacher = ExamContact::create(['name' => 'Danny Drums', 'role' => 'teacher']);
    makeEntry($drumKit, 'Rock and Pop', $teacher);

    // Simulate the stale pre-split link (everything drum-related pointed at Drum Kit).
    $teacher->instruments()->syncWithoutDetaching([$drumKit->id]);

    $this->artisan('exam:retag-rp-drums')->assertSuccessful();

    $drums = Instrument::where('name', 'Drums')->firstOrFail();
    $names = $teacher->fresh()->instruments->pluck('name');

    expect($names)->toContain('Drums')
        ->and($names)->not->toContain('Drum Kit');
});

test('dry run changes nothing', function () {
    $drumKit = drumKitInstrument();
    $rp = makeEntry($drumKit, 'Rock and Pop');

    $this->artisan('exam:retag-rp-drums --dry-run')->assertSuccessful();

    expect($rp->fresh()->instrument_id)->toBe($drumKit->id)
        ->and(Instrument::where('name', 'Drums')->exists())->toBeFalse();
});

test('running it twice is idempotent', function () {
    $drumKit = drumKitInstrument();
    $rp = makeEntry($drumKit, 'Rock and Pop');

    $this->artisan('exam:retag-rp-drums')->assertSuccessful();
    $this->artisan('exam:retag-rp-drums')->assertSuccessful();

    $drums = Instrument::where('name', 'Drums')->firstOrFail();
    expect($rp->fresh()->instrument_id)->toBe($drums->id)
        ->and(ExamEntry::where('instrument_id', $drumKit->id)->count())->toBe(0);
});
