<?php

// tests/Feature/Admin/ContactCreditedCountsTest.php
//
// Entry/order counts on the ADMIN LISTS must credit a contact however they are
// linked — as the named teacher, or as the person who submitted the booking.
//
// Found via Cheryl Ritchie (contact 80): /admin/contacts showed her "0 entries,
// 0 orders" while /admin/contacts/80 listed one of each (Harry Ritchie, Rock
// and Pop Grade 5, Distinction 87, order 1-17808029514). She is a Parent who
// was the applicant, so her entry carries submitter_contact_id and no
// teacher_contact_id — and examEntries()/orders() see neither. Same root cause
// as the pre-result blind spot fixed in App\Support\EntryCredit.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;

use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function creditedContact(string $name, string $email, string $type): ExamContact
{
    $contact = ExamContact::create([
        'name' => $name,
        'email' => $email,
        'source' => 'trinity_csv',
    ]);
    $contact->addType($type);

    return $contact;
}

function creditedOrder(?ExamContact $submitter = null): Order
{
    return Order::create([
        'trinity_order_number' => '1-CRED-'.uniqid('', true),
        'delivery_method' => 'Digital',
        'subject_area' => 'Rock and Pop',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => Carbon::create(2026, 7, 4),
        'created_by_contact_id' => $submitter?->id,
    ]);
}

/** A row in the shape the Enrolment List import produces for a parent booking. */
function parentSubmittedEntry(ExamContact $parent, Order $order): ExamEntry
{
    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Harry Ritchie',
        'candidate_number' => '1-6173386853',
        'grade' => '5',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Digital',
        'result' => 'Distinction',
        'score' => 87,
        'exam_date' => Carbon::create(2026, 7, 4),
        // The whole point: no teacher, only a submitter.
        'teacher_contact_id' => null,
        'submitter_contact_id' => $parent->id,
    ]);
}

test('a parent who only submitted is credited on the contacts list', function () {
    $parent = creditedContact('Cheryl Ritchie', 'cheryl_ritchie@live.co.uk', 'parent');
    parentSubmittedEntry($parent, creditedOrder($parent));

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.name', 'Cheryl Ritchie')
            ->where('contacts.data.0.exam_entries_count', 1)
            ->where('contacts.data.0.orders_count', 1));
});

test('a teacher who booked their own candidate is counted once, not twice', function () {
    // teacher AND submitter on the same row — summing two relations would
    // report 2 entries and 2 orders for what is one of each.
    $teacher = creditedContact('Chris Callaway', 'chris@chriscallaway.uk', 'teacher');
    $order = creditedOrder($teacher);
    $teacher->orders()->attach($order->id, ['role_in_order' => 'applicant']);

    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Own Pupil',
        'grade' => '3',
        'subject_area' => 'Piano',
        'delivery_method' => 'Digital',
        'teacher_contact_id' => $teacher->id,
        'submitter_contact_id' => $teacher->id,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.exam_entries_count', 1)
            ->where('contacts.data.0.orders_count', 1));
});

test('a contact linked only as the teacher still counts', function () {
    $teacher = creditedContact('Anthony Bearon', 'anthony@bearon.org.uk', 'teacher');
    $order = creditedOrder();
    $teacher->orders()->attach($order->id, ['role_in_order' => 'teacher']);

    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Taught Pupil',
        'grade' => '2',
        'subject_area' => 'Recorder',
        'delivery_method' => 'Face to Face',
        'teacher_contact_id' => $teacher->id,
        'submitter_contact_id' => null,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.exam_entries_count', 1)
            ->where('contacts.data.0.orders_count', 1));
});

test('a contact with nothing attached still reads zero', function () {
    creditedContact('Benjamin Shore', 'ben@example.com', 'candidate');

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.exam_entries_count', 0)
            ->where('contacts.data.0.orders_count', 0));
});

test('the user detail page lists entries a parent only submitted', function () {
    $parent = creditedContact('Cheryl Ritchie', 'cheryl_ritchie@live.co.uk', 'parent');
    parentSubmittedEntry($parent, creditedOrder($parent));

    $user = User::factory()->create([
        'email' => 'cheryl_ritchie@live.co.uk',
        'role' => 'parent',
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$user->id}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('linkedContact.exam_entries_count', 1)
            ->has('linkedContact.exam_entries', 1)
            ->where('linkedContact.exam_entries.0.candidate_name', 'Harry Ritchie'));
});
