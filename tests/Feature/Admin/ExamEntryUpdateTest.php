<?php

// tests/Feature/Admin/ExamEntryUpdateTest.php
//
// The inline edit-row modal on /admin/exam-entries: correcting a single
// imported entry (wrong candidate name, parent-in-teacher field, or a
// result/score Trinity reported wrong) without opening TablePlus. Every
// editable field is covered, plus validation, the audit log, and access.

use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function makeEditableEntry(array $attrs = []): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-TEST-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => now()->subMonth(),
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Mei Khoo',
        'grade' => 'Grade 1',
        'subject_area' => 'Piano',
        'delivery_method' => 'Digital',
        'teacher_name' => 'A Parent',
        'show_full_name' => false,
    ], $attrs));
}

test('an admin can correct the candidate name', function () {
    $entry = makeEditableEntry();

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", [
            'candidate_name' => 'Alice Jun Mei Khoo',
            'teacher_name' => $entry->teacher_name,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($entry->fresh()->candidate_name)->toBe('Alice Jun Mei Khoo');
});

test('an admin can fix a parent-in-the-teacher-field attribution', function () {
    $entry = makeEditableEntry(['teacher_name' => 'Mrs Khoo (mum)']);

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", [
            'candidate_name' => $entry->candidate_name,
            'teacher_name' => 'Daniel Carty',
        ])
        ->assertSessionHas('success');

    expect($entry->fresh()->teacher_name)->toBe('Daniel Carty');
});

test('an admin can correct a wrong result and score', function () {
    $entry = makeEditableEntry(['result' => 'Pass', 'score' => 62]);

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", [
            'result' => 'Distinction',
            'score' => 91,
        ])
        ->assertSessionHas('success');

    $fresh = $entry->fresh();
    expect($fresh->result)->toBe('Distinction')
        ->and($fresh->score)->toBe(91);
});

test('an admin can edit notes and the show-full-name consent flag', function () {
    $entry = makeEditableEntry();

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", [
            'notes' => 'NO_SHOW',
            'show_full_name' => true,
        ])
        ->assertSessionHas('success');

    $fresh = $entry->fresh();
    expect($fresh->notes)->toBe('NO_SHOW')
        ->and($fresh->show_full_name)->toBeTrue();
});

test('blank text fields are stored as null, not empty strings', function () {
    $entry = makeEditableEntry(['result' => 'Pass']);

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", ['result' => '   ']);

    expect($entry->fresh()->result)->toBeNull();
});

test('score must be an integer between 0 and 100', function () {
    $entry = makeEditableEntry(['score' => 70]);

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", ['score' => 250])
        ->assertSessionHasErrors('score');

    // Unchanged after the invalid attempt.
    expect($entry->fresh()->score)->toBe(70);
});

test('the correction is written to the audit log with the admin and the diff', function () {
    $entry = makeEditableEntry(['candidate_name' => 'Mei Khoo']);
    Log::spy();

    $this->actingAs($this->admin)
        ->put("/admin/exam-entries/{$entry->id}", [
            'candidate_name' => 'Alice Jun Mei Khoo',
        ]);

    Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($entry) {
        return $message === 'admin.exam_entry.updated'
            && $context['exam_entry_id'] === $entry->id
            && $context['admin_id'] === $this->admin->id
            && isset($context['changes']['candidate_name']);
    })->once();
});

test('guests cannot update an exam entry', function () {
    $entry = makeEditableEntry();

    $this->put("/admin/exam-entries/{$entry->id}", ['candidate_name' => 'Hacker'])
        ->assertRedirect('/login');

    expect($entry->fresh()->candidate_name)->toBe('Mei Khoo');
});
