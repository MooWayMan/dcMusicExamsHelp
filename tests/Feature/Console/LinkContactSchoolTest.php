<?php

// tests/Feature/Console/LinkContactSchoolTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\School;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function linkEntry(array $attrs): ExamEntry
{
    $order = Order::factory()->create();

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Some Pupil',
        'grade' => 'Grade 1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 5, 1),
    ], $attrs));
}

test('links a contact to a school as school_admin and re-tags their entries', function () {
    $david = ExamContact::create(['name' => 'David Keeling']);
    $school = School::create(['name' => 'Learn Music Ltd']);

    // One entry already FK-linked; one only name-matched (FK null).
    $byFk = linkEntry(['teacher_contact_id' => $david->id, 'booking_role' => 'teacher']);
    $byName = linkEntry(['teacher_name' => 'David Keeling', 'booking_role' => 'teacher']);

    $this->artisan('contacts:link-school', ['contact' => 'David Keeling', 'school' => 'Learn Music Ltd'])
        ->assertSuccessful();

    $david->refresh();
    expect($david->hasType('school_admin'))->toBeTrue()
        ->and($david->schools()->whereKey($school->id)->exists())->toBeTrue()
        ->and($byFk->fresh()->booking_role)->toBe('school_admin')
        ->and($byName->fresh()->booking_role)->toBe('school_admin')
        ->and($byName->fresh()->teacher_contact_id)->toBe($david->id);
});

test('dry run changes nothing', function () {
    $david = ExamContact::create(['name' => 'David Keeling']);
    $school = School::create(['name' => 'Learn Music Ltd']);
    $entry = linkEntry(['teacher_contact_id' => $david->id, 'booking_role' => 'teacher']);

    $this->artisan('contacts:link-school', [
        'contact' => 'David Keeling',
        'school' => 'Learn Music Ltd',
        '--dry-run' => true,
    ])->assertSuccessful();

    expect($david->fresh()->hasType('school_admin'))->toBeFalse()
        ->and($david->schools()->whereKey($school->id)->exists())->toBeFalse()
        ->and($entry->fresh()->booking_role)->toBe('teacher');
});

test('existing types are preserved (teacher + school_admin dual role)', function () {
    $emily = ExamContact::create(['name' => 'Emily Bates']);
    $emily->addType('teacher');
    School::create(['name' => 'Learn Music Ltd']);

    $this->artisan('contacts:link-school', ['contact' => 'Emily Bates', 'school' => 'Learn Music Ltd'])
        ->assertSuccessful();

    expect($emily->fresh()->hasType('teacher'))->toBeTrue()
        ->and($emily->fresh()->hasType('school_admin'))->toBeTrue();
});

test('reports an error when the school is not found', function () {
    ExamContact::create(['name' => 'David Keeling']);

    $this->artisan('contacts:link-school', ['contact' => 'David Keeling', 'school' => 'Nope School'])
        ->assertFailed();
});
