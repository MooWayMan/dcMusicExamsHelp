<?php

use App\Models\ContactLog;
use App\Models\ExamContact;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Contact Logs — Contact communication tracking
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->contact = ExamContact::create([
        'name'  => 'Test Teacher',
        'email' => 'test.teacher@example.com',
    ]);
    $this->contact->addType('teacher');
});

test('admin can add a contact log to a contact', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'contact_type' => 'email',
            'direction' => 'outbound',
            'subject' => 'Welcome to centre 120',
            'summary' => 'Sent welcome email about benefits',
            'contacted_at' => '2026-04-09',
        ])
        ->assertRedirect(route('admin.contacts.show', $this->contact));

    $this->assertDatabaseHas('contact_logs', [
        'exam_contact_id' => $this->contact->id,
        'contact_type' => 'email',
        'direction' => 'outbound',
    ]);
});

test('contact log requires contact type', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'direction' => 'outbound',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('contact_type');
});

test('contact log requires direction', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'contact_type' => 'phone',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('direction');
});

test('contact log requires contacted_at date', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'contact_type' => 'phone',
            'direction' => 'inbound',
        ])
        ->assertSessionHasErrors('contacted_at');
});

test('contact type must be valid', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'contact_type' => 'telegram',
            'direction' => 'outbound',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('contact_type');
});

test('direction must be inbound or outbound', function () {
    $this->actingAs($this->admin)
        ->post("/admin/contacts/{$this->contact->id}/contact-logs", [
            'contact_type' => 'email',
            'direction' => 'sideways',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('direction');
});

test('admin can delete a contact log', function () {
    $log = ContactLog::create([
        'exam_contact_id' => $this->contact->id,
        'contact_type' => 'phone',
        'direction' => 'inbound',
        'contacted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/contacts/{$this->contact->id}/contact-logs/{$log->id}")
        ->assertRedirect(route('admin.contacts.show', $this->contact));

    $this->assertDatabaseMissing('contact_logs', ['id' => $log->id]);
});

test('cannot delete contact log belonging to a different contact', function () {
    $otherContact = ExamContact::create([
        'name'  => 'Other Teacher',
        'email' => 'other.teacher@example.com',
    ]);
    $otherContact->addType('teacher');

    $log = ContactLog::create([
        'exam_contact_id' => $otherContact->id,
        'contact_type' => 'phone',
        'direction' => 'inbound',
        'contacted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/contacts/{$this->contact->id}/contact-logs/{$log->id}")
        ->assertStatus(403);
});
