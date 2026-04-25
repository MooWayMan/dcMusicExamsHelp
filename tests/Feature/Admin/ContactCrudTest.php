<?php

use App\Models\ContactEmail;
use App\Models\ExamContact;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Contact edit/update (role correction for prize-draw eligibility)
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function makeContact(array $overrides = []): ExamContact
{
    return ExamContact::create(array_merge([
        'name' => 'Seth Barraclough',
        'email' => 'seth@example.com',
        'phone' => null,
        'role' => 'parent',
        'source' => 'trinity_csv',
        'notes' => null,
    ], $overrides));
}

test('admin can view the contact edit form', function () {
    $contact = makeContact();

    $this->actingAs($this->admin)
        ->get("/admin/contacts/{$contact->id}/edit")
        ->assertStatus(200);
});

test('admin can change a contact type from parent to teacher', function () {
    $contact = makeContact();
    $contact->addType('parent');

    $response = $this->actingAs($this->admin)
        ->put("/admin/contacts/{$contact->id}", [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => '',
            'types' => ['teacher'],
            'notes' => '',
        ]);

    $response->assertRedirect("/admin/contacts/{$contact->id}");

    expect($contact->fresh()->isTeacher())->toBeTrue();
    expect($contact->fresh()->isParent())->toBeFalse();
});

test('contact update rejects an unknown type value', function () {
    $contact = makeContact();
    $contact->addType('parent');

    $this->actingAs($this->admin)
        ->from("/admin/contacts/{$contact->id}/edit")
        ->put("/admin/contacts/{$contact->id}", [
            'name' => $contact->name,
            'types' => ['wizard'],
        ])
        ->assertSessionHasErrors('types.0');

    expect($contact->fresh()->isParent())->toBeTrue();
});

test('contact update requires a name', function () {
    $contact = makeContact();

    $this->actingAs($this->admin)
        ->from("/admin/contacts/{$contact->id}/edit")
        ->put("/admin/contacts/{$contact->id}", [
            'name' => '',
            'types' => ['teacher'],
        ])
        ->assertSessionHasErrors('name');
});

test('non-admin cannot reach the contact edit form', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $contact = makeContact();

    $this->actingAs($teacher)
        ->get("/admin/contacts/{$contact->id}/edit")
        ->assertStatus(403);
});

test('non-admin cannot update a contact', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $contact = makeContact();
    $contact->addType('parent');

    $this->actingAs($teacher)
        ->put("/admin/contacts/{$contact->id}", [
            'name' => $contact->name,
            'types' => ['teacher'],
        ])
        ->assertStatus(403);

    expect($contact->fresh()->isParent())->toBeTrue();
    expect($contact->fresh()->isTeacher())->toBeFalse();
});

test('guests are redirected to login', function () {
    $contact = makeContact();

    $this->get("/admin/contacts/{$contact->id}/edit")
        ->assertRedirect('/login');
});

// ──────────────────────────────────────────
// Index email rendering — falls back to contact_emails relation
// ──────────────────────────────────────────

test('index lists email from contact_emails relation when direct column is empty', function () {
    // Legacy-import pattern — email only in the related contact_emails table
    $contact = ExamContact::create([
        'name' => 'Roxanne Legacy',
        'email' => null,
        'role' => 'teacher',
        'source' => 'legacy_db',
    ]);

    ContactEmail::create([
        'exam_contact_id' => $contact->id,
        'email' => 'roxanne.legacy@example.com',
        'label' => 'primary',
        'is_primary' => true,
    ]);

    // Narrow the list to a single match so we can assert positional values.
    $response = $this->actingAs($this->admin)
        ->get('/admin/contacts?search=Roxanne+Legacy');

    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/Contacts/Index')
            ->where('contacts.data.0.name', 'Roxanne Legacy')
            ->where('contacts.data.0.email', 'roxanne.legacy@example.com')
    );
});

// ──────────────────────────────────────────
// Primary email sync between exam_contacts.email and contact_emails
// ──────────────────────────────────────────

test('updating a contact syncs the saved email as the primary in contact_emails', function () {
    $contact = makeContact(['email' => 'old@example.com']);
    ContactEmail::create([
        'exam_contact_id' => $contact->id,
        'email' => 'old@example.com',
        'label' => 'legacy',
        'is_primary' => true,
    ]);
    ContactEmail::create([
        'exam_contact_id' => $contact->id,
        'email' => 'stale@example.com',
        'label' => 'legacy_alt',
        'is_primary' => false,
    ]);

    $this->actingAs($this->admin)
        ->put("/admin/contacts/{$contact->id}", [
            'name' => $contact->name,
            'email' => 'new@example.com',
            'phone' => '',
            'role' => 'teacher',
            'notes' => '',
        ])
        ->assertRedirect("/admin/contacts/{$contact->id}");

    // The new email must exist as primary
    $this->assertDatabaseHas('contact_emails', [
        'exam_contact_id' => $contact->id,
        'email' => 'new@example.com',
        'is_primary' => true,
    ]);

    // Older rows must be demoted
    $this->assertDatabaseHas('contact_emails', [
        'exam_contact_id' => $contact->id,
        'email' => 'old@example.com',
        'is_primary' => false,
    ]);
    $this->assertDatabaseHas('contact_emails', [
        'exam_contact_id' => $contact->id,
        'email' => 'stale@example.com',
        'is_primary' => false,
    ]);

    // Show accessor should now match the canonical email
    expect($contact->fresh()->primary_email)->toBe('new@example.com');
});

test('clearing the email demotes all primary flags without inserting a row', function () {
    $contact = makeContact(['email' => 'current@example.com']);
    ContactEmail::create([
        'exam_contact_id' => $contact->id,
        'email' => 'current@example.com',
        'is_primary' => true,
    ]);

    $this->actingAs($this->admin)
        ->put("/admin/contacts/{$contact->id}", [
            'name' => $contact->name,
            'email' => '',
            'phone' => '',
            'role' => 'teacher',
            'notes' => '',
        ]);

    $this->assertDatabaseMissing('contact_emails', [
        'exam_contact_id' => $contact->id,
        'is_primary' => true,
    ]);
});
