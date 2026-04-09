<?php

use App\Models\User;
use App\Models\ContactLog;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Contact Logs — Teacher communication tracking
// ──────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->teacher = User::factory()->create(['role' => 'teacher']);
});

test('admin can add a contact log to a teacher', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'contact_type' => 'email',
            'direction' => 'outbound',
            'subject' => 'Welcome to centre 120',
            'summary' => 'Sent welcome email about benefits',
            'contacted_at' => '2026-04-09',
        ])
        ->assertRedirect(route('admin.teachers.show', $this->teacher));

    $this->assertDatabaseHas('contact_logs', [
        'user_id' => $this->teacher->id,
        'contact_type' => 'email',
        'direction' => 'outbound',
    ]);
});

test('contact log requires contact type', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'direction' => 'outbound',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('contact_type');
});

test('contact log requires direction', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'contact_type' => 'phone',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('direction');
});

test('contact log requires contacted_at date', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'contact_type' => 'phone',
            'direction' => 'inbound',
        ])
        ->assertSessionHasErrors('contacted_at');
});

test('contact type must be valid', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'contact_type' => 'telegram',
            'direction' => 'outbound',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('contact_type');
});

test('direction must be inbound or outbound', function () {
    $this->actingAs($this->admin)
        ->post("/admin/teachers/{$this->teacher->id}/contact-logs", [
            'contact_type' => 'email',
            'direction' => 'sideways',
            'contacted_at' => '2026-04-09',
        ])
        ->assertSessionHasErrors('direction');
});

test('admin can delete a contact log', function () {
    $log = ContactLog::create([
        'user_id' => $this->teacher->id,
        'contact_type' => 'phone',
        'direction' => 'inbound',
        'contacted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/teachers/{$this->teacher->id}/contact-logs/{$log->id}")
        ->assertRedirect(route('admin.teachers.show', $this->teacher));

    $this->assertDatabaseMissing('contact_logs', ['id' => $log->id]);
});

test('cannot delete contact log belonging to a different teacher', function () {
    $otherTeacher = User::factory()->create(['role' => 'teacher']);

    $log = ContactLog::create([
        'user_id' => $otherTeacher->id,
        'contact_type' => 'phone',
        'direction' => 'inbound',
        'contacted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete("/admin/teachers/{$this->teacher->id}/contact-logs/{$log->id}")
        ->assertStatus(403);
});
