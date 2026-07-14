<?php
// tests/Feature/Admin/PreviewDashboardTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lets an admin preview the teacher dashboard as a contact', function () {
    $teacher = ExamContact::create(['name' => 'Daniel Rogers', 'email' => 'exams@pulse.test']);
    $teacher->addType('teacher');

    $order = Order::factory()->create();
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Nina Dlugosz',
        'candidate_number' => '1-17563381207',
        'grade' => 'Grade 1',
        'subject_area' => 'Rock and Pop',
        'delivery_method' => 'Default',
        'exam_date' => Carbon::create(2026, 7, 11),
        'result' => 'Merit',
        'score' => 75,
        'teacher_contact_id' => $teacher->id,
        'report' => ['subject' => 'Rock & Pop Vocals', 'total' => 75, 'sections' => []],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get("/admin/contacts/{$teacher->id}/preview-dashboard")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('preview.contact_name', 'Daniel Rogers')
            ->where('examEntries.0.candidate_name', 'Nina Dlugosz')
            ->where('examEntries.0.report.total', 75));
});

it('also matches entries via the contact email (applicant_email path)', function () {
    $teacher = ExamContact::create(['name' => 'Helen Hodgkiss', 'email' => 'gold@pulse.test']);
    $teacher->addType('teacher');

    $order = Order::factory()->create();
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Emilia Sindila',
        'grade' => 'Grade 5',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
        'exam_date' => Carbon::create(2026, 7, 9),
        'result' => 'Merit',
        'score' => 76,
        'teacher_contact_id' => null,
        'applicant_email' => 'gold@pulse.test',
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get("/admin/contacts/{$teacher->id}/preview-dashboard")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('examEntries.0.candidate_name', 'Emilia Sindila'));
});

it('blocks a non-admin from the preview route', function () {
    $teacher = ExamContact::create(['name' => 'Daniel Rogers', 'email' => 'exams@pulse.test']);
    $nonAdmin = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($nonAdmin)
        ->get("/admin/contacts/{$teacher->id}/preview-dashboard")
        ->assertForbidden();
});
