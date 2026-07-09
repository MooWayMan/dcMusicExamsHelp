<?php

// tests/Feature/Admin/ContactActivityTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function activityAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function activityEntry(Order $order, array $attrs = []): ExamEntry
{
    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Jim Hazell',
        'grade' => 'Grade 4',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 6, 15),
    ], $attrs));
}

test('a parent submitter sees the order and entry they submitted', function () {
    $parent = ExamContact::create(['name' => 'Michael Hazell', 'email' => 'm@example.test']);
    $parent->addType('parent');

    $order = Order::factory()->create(['created_by_contact_id' => $parent->id]);
    activityEntry($order, ['submitter_contact_id' => $parent->id, 'teacher_contact_id' => null]);

    $this->actingAs(activityAdmin())
        ->get("/admin/contacts/{$parent->id}")
        ->assertInertia(fn ($page) => $page
            ->where('contact.orders_count', 1)
            ->where('contact.exam_entries_count', 1)
            ->where('contact.exam_entries.0.candidate_name', 'Jim Hazell')
            ->where('contact.exam_entries.0.relationship', 'submitted')
            ->where('contact.orders.0.roles_in_order.0', 'applicant'));
});

test('a taught entry is labelled teacher', function () {
    $teacher = ExamContact::create(['name' => 'Real Teacher']);
    $teacher->addType('teacher');

    $order = Order::factory()->create();
    activityEntry($order, ['teacher_contact_id' => $teacher->id]);

    $this->actingAs(activityAdmin())
        ->get("/admin/contacts/{$teacher->id}")
        ->assertInertia(fn ($page) => $page
            ->where('contact.exam_entries_count', 1)
            ->where('contact.exam_entries.0.relationship', 'teacher'));
});

test('an order counted via both pivot and created_by is not double counted', function () {
    $contact = ExamContact::create(['name' => 'Dual Link']);
    $order = Order::factory()->create(['created_by_contact_id' => $contact->id]);

    DB::table('order_contacts')->insert([
        'order_id' => $order->id, 'exam_contact_id' => $contact->id,
        'role_in_order' => 'teacher', 'is_primary' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->actingAs(activityAdmin())
        ->get("/admin/contacts/{$contact->id}")
        ->assertInertia(fn ($page) => $page->where('contact.orders_count', 1));
});
