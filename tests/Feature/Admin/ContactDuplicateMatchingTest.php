<?php

// tests/Feature/Admin/ContactDuplicateMatchingTest.php
//
// The "possible duplicate" flag on /admin/contacts.
//
// The old rule ran PHP's similar_text() over the whole normalised name at 80%.
// That scores a name as a bag of characters, so a shared forename dominates
// when the surnames are short: "claire freeman" vs "claire reed" lands on
// EXACTLY 80.00 (seven characters of "claire ", plus "ree" appearing in both
// "f-ree-man" and "ree-d") and the guard is `< $threshold`, so it flagged two
// unrelated people. "emily bates" vs "emma bates" scored 76 and did not.
//
// Two rules from Paul (3 Aug 2026) shape the replacement:
//   - surnames change on marriage, so a surname match can't be a hard gate
//   - people use several email addresses, so a shared address is strong
//     evidence FOR, and a missing one is no evidence against

use App\Models\ContactEmail;
use App\Models\ContactMergeDismissal;
use App\Models\ExamContact;
use App\Models\User;
use App\Services\ContactMergeService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function dupContact(string $name, ?string $email = null, ?string $phone = null): ExamContact
{
    $contact = ExamContact::create([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'source' => 'trinity_csv',
    ]);
    $contact->addType('teacher');

    return $contact;
}

function flagged(): array
{
    return app(ContactMergeService::class)->duplicateContactIds();
}

test('a shared forename with a different surname is not a duplicate', function () {
    // The exact pair that misfired: 80.00% under the old whole-name rule.
    dupContact('Claire Freeman', 'claire9876@gmail.com');
    dupContact('Claire Reed', 'closborn@hotmail.co.uk');

    expect(flagged())->toBe([]);
});

test('a misspelt surname with the same forename is a duplicate', function () {
    $a = dupContact('Cheryl Ritchie', 'cheryl_ritchie@live.co.uk');
    $b = dupContact('Cheryl Richie', 'cheryl.ritchie@gmail.com');

    $hits = flagged();

    expect($hits)->toHaveKeys([$a->id, $b->id])
        ->and($hits[$a->id]['name'])->toBe('Cheryl Richie');
});

test('an identical name is a duplicate on the name alone', function () {
    $a = dupContact('Amy Morgan', 'amy@one.com');
    $b = dupContact('Amy Morgan', 'amy@two.com');

    expect(flagged())->toHaveKeys([$a->id, $b->id]);
});

test('a changed surname is caught when an email is still shared', function () {
    // Married, took a new surname, kept one address on the old record. This
    // is the case a hard surname gate would lose.
    $a = dupContact('Claire Freeman', 'claire9876@gmail.com');
    $b = dupContact('Claire Reed', 'closborn@hotmail.co.uk');
    ContactEmail::create([
        'exam_contact_id' => $b->id,
        'email' => 'claire9876@gmail.com',
        'label' => 'legacy',
        'is_primary' => false,
    ]);

    expect(flagged())->toHaveKeys([$a->id, $b->id]);
});

test('a changed surname is caught when the phone is shared', function () {
    // Same number written two ways — compared on digits, last 10.
    $a = dupContact('Claire Freeman', 'claire9876@gmail.com', '07584 904 971');
    $b = dupContact('Claire Reed', 'closborn@hotmail.co.uk', '+44 7584 904971');

    expect(flagged())->toHaveKeys([$a->id, $b->id]);
});

test('the same surname with a different forename needs corroborating evidence', function () {
    // Siblings, spouses and parent/child all look like this. On the name
    // alone it is not enough.
    dupContact('John Smith', 'john@example.com');
    dupContact('Joan Smith', 'joan@example.com');

    expect(flagged())->toBe([]);
});

test('a household sharing a phone is not two records for one person', function () {
    // The counterweight to the phone rule above. Same surname, different
    // forename, same landline is a FAMILY — weighting the phone the same way
    // here as for a rename would flag every parent and child in the system.
    dupContact('John Smith', 'john@example.com', '0151 475 0001');
    dupContact('Joan Smith', 'joan@example.com', '0151 475 0001');

    expect(flagged())->toBe([]);
});

test('a shared school alone is never enough', function () {
    // Teachers at the same school share a school. That is not evidence of
    // anything on its own.
    $a = dupContact('Claire Freeman', 'claire9876@gmail.com');
    $b = dupContact('Claire Reed', 'closborn@hotmail.co.uk');
    $school = \App\Models\School::factory()->create();
    $school->contacts()->attach([$a->id, $b->id]);

    expect(flagged())->toBe([]);
});

test('an initial matches the forename it abbreviates', function () {
    $a = dupContact('P Sheridan', 'paul@example.com');
    $b = dupContact('Paul Sheridan', 'paul.sheridan@example.com');

    expect(flagged())->toHaveKeys([$a->id, $b->id]);
});

test('a dismissed pair stays unflagged', function () {
    $a = dupContact('Amy Morgan', 'amy@one.com');
    $b = dupContact('Amy Morgan', 'amy@two.com');

    ContactMergeDismissal::dismiss($a->id, $b->id);

    expect(flagged())->toBe([]);
});

test('the contacts list names who it thinks the duplicate is', function () {
    dupContact('Cheryl Ritchie', 'cheryl_ritchie@live.co.uk');
    dupContact('Cheryl Richie', 'cheryl.ritchie@gmail.com');

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.has_duplicate', true)
            ->where('contacts.data.0.duplicate_of.name', 'Cheryl Ritchie')
            ->where('contacts.data.1.duplicate_of.name', 'Cheryl Richie'));
});

test('a contact with no duplicate carries no counterpart', function () {
    dupContact('Anthony Bearon', 'anthony@bearon.org.uk');

    $this->actingAs($this->admin)
        ->get('/admin/contacts')
        ->assertInertia(fn ($p) => $p
            ->where('contacts.data.0.has_duplicate', false)
            ->where('contacts.data.0.duplicate_of', null));
});
