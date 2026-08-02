<?php

// tests/Feature/Dashboard/PendingEntriesTest.php
//
// A teacher's not-yet-resulted candidates must show on their dashboard.
//
// The Section 1b enrolment-list import creates the entry the moment the order
// is imported, with `teacher_contact_id` and `applicant_email` both null —
// Trinity hasn't named a teacher yet, so the only link to a person is
// `submitter_contact_id`. The dashboard query matched on the first two only,
// so every awaiting candidate was invisible to the teacher who booked them.
// (Maria Nielsen, Q2 2026: 3 candidates shown, Grace Kennedy missing.)
//
// The page has always had a "Pending" filter — it just could never match.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function pendingOrder(): Order
{
    return Order::create([
        'trinity_order_number' => '1-PEND-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Processed',
        'requested_start_date' => Carbon::create(2026, 6, 30),
    ]);
}

/** Exactly what the enrolment-list import writes for a pre-result candidate. */
function enrolmentListEntry(ExamContact $submitter, string $name): ExamEntry
{
    return ExamEntry::create([
        'order_id' => pendingOrder()->id,
        'candidate_number' => '1-'.uniqid('', true),
        'candidate_name' => $name,
        'grade' => '4',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'score' => null,
        'result' => null,
        'exam_date' => null,
        'teacher_name' => null,
        'teacher_contact_id' => null,
        'booking_role' => null,
        'applicant_email' => null,
        'submitter_contact_id' => $submitter->id,
        'source' => 'trinity_enrolment_list',
    ]);
}

function maria(): ExamContact
{
    $maria = ExamContact::create([
        'name' => 'Maria Nielsen',
        'email' => 'mkn21@example.com',
        'source' => 'manual',
    ]);
    $maria->addType('teacher');

    return $maria;
}

test('a teacher sees a candidate whose result has not come back yet', function () {
    $contact = maria();
    enrolmentListEntry($contact, 'Grace Kennedy');

    $user = User::factory()->create(['email' => $contact->email]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('hasLinkedContact', true)
            ->where('examEntries.0.candidate_name', 'Grace Kennedy')
            ->where('examEntries.0.result', null)
            ->where('examEntries.0.score', null));
});

test('pending and resulted candidates appear together', function () {
    $contact = maria();
    enrolmentListEntry($contact, 'Grace Kennedy');

    ExamEntry::create([
        'order_id' => pendingOrder()->id,
        'candidate_number' => '1-EMILY',
        'candidate_name' => 'Emily Hamilton-Cook',
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'score' => 77,
        'result' => 'Merit',
        'exam_date' => Carbon::create(2026, 6, 30),
        'teacher_contact_id' => $contact->id,
        'teacher_name' => 'Maria Nielsen',
    ]);

    $user = User::factory()->create(['email' => $contact->email]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($p) => $p->has('examEntries', 2));
});

test('another teacher does not see a candidate they did not submit', function () {
    enrolmentListEntry(maria(), 'Grace Kennedy');

    $other = ExamContact::create(['name' => 'Someone Else', 'email' => 'other@example.com', 'source' => 'manual']);
    $other->addType('teacher');
    $user = User::factory()->create(['email' => $other->email]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($p) => $p->has('examEntries', 0));
});

test('the admin preview shows the same pending candidate as the real dashboard', function () {
    // The preview exists so Paul can check a teacher's page before they've
    // registered — it has to resolve entries identically or it misleads him.
    $contact = maria();
    enrolmentListEntry($contact, 'Grace Kennedy');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get("/admin/contacts/{$contact->id}/preview-dashboard")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('examEntries.0.candidate_name', 'Grace Kennedy')
            ->where('examEntries.0.result', null));
});
