<?php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Teacher prize draw — parents are excluded
// ──────────────────────────────────────────
// Paul corrected a contact from 'teacher' → 'parent' on /admin/contacts/{id}.
// The teacher draw must honour that role so the parent doesn't win a
// teacher prize. We check both by teacher_contact_id link and by name match
// (older entries may predate the teacher_contact_id backfill).

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function makeEntry(array $attrs): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-TEST-' . uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => Carbon::create(2026, 2, 15),
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Candidate',
        'grade' => 'Grade 1',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => Carbon::create(2026, 2, 15),
    ], $attrs));
}

test('teacher flagged as parent is excluded by teacher_contact_id', function () {
    $parentContact = ExamContact::create([
        'name' => 'Mrs Khoo',
        'role' => 'parent',
    ]);

    // Parent submitted 3 entries — would otherwise qualify (>=2 entries rule)
    makeEntry(['teacher_name' => 'Mrs Khoo', 'teacher_contact_id' => $parentContact->id]);
    makeEntry(['teacher_name' => 'Mrs Khoo', 'teacher_contact_id' => $parentContact->id]);
    makeEntry(['teacher_name' => 'Mrs Khoo', 'teacher_contact_id' => $parentContact->id]);

    // A real teacher in the mix
    $teacherContact = ExamContact::create([
        'name' => 'Ms Keeling',
        'role' => 'teacher',
    ]);
    makeEntry(['teacher_name' => 'Ms Keeling', 'teacher_contact_id' => $teacherContact->id]);
    makeEntry(['teacher_name' => 'Ms Keeling', 'teacher_contact_id' => $teacherContact->id]);

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/draw', [
            'type' => 'teacher',
            'quarter' => 1,
            'year' => 2026,
            'mode' => 'test',
        ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Ms Keeling');
    expect($response->json('total_tickets'))->toBe(2);
});

test('teacher flagged as parent is excluded by name fallback when no teacher_contact_id linked', function () {
    // Legacy entries — teacher_contact_id not linked, match by name
    ExamContact::create(['name' => 'Mrs Khoo', 'role' => 'parent']);

    makeEntry(['teacher_name' => 'Mrs Khoo']);
    makeEntry(['teacher_name' => 'Mrs Khoo']);

    ExamContact::create(['name' => 'Ms Keeling', 'role' => 'teacher']);
    makeEntry(['teacher_name' => 'Ms Keeling']);
    makeEntry(['teacher_name' => 'Ms Keeling']);

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/draw', [
            'type' => 'teacher',
            'quarter' => 1,
            'year' => 2026,
            'mode' => 'test',
        ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Ms Keeling');
});

test('name match for excluded contacts is case-insensitive', function () {
    ExamContact::create(['name' => 'mrs khoo', 'role' => 'parent']);

    makeEntry(['teacher_name' => 'Mrs Khoo']);
    makeEntry(['teacher_name' => 'Mrs Khoo']);

    ExamContact::create(['name' => 'Ms Keeling', 'role' => 'teacher']);
    makeEntry(['teacher_name' => 'Ms Keeling']);
    makeEntry(['teacher_name' => 'Ms Keeling']);

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/draw', [
            'type' => 'teacher',
            'quarter' => 1,
            'year' => 2026,
            'mode' => 'test',
        ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Ms Keeling');
});
