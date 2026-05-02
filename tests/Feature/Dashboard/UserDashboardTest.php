<?php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Task;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Non-admin user dashboard — flat read-only view of the user's exam_entries,
// with a linkage form for users whose email doesn't match anything.
// ──────────────────────────────────────────

function makeOrderForDashboardTest(): Order
{
    return Order::create([
        'trinity_order_number' => 'ORD-'.fake()->unique()->numerify('#######'),
        'order_status' => 'Submitted',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'requested_start_date' => '2026-03-01',
    ]);
}

test('a teacher with matching exam_entries (via teacher_contact) sees their candidates', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'tina@example.com',
    ]);

    $contact = ExamContact::create([
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
        'source' => 'trinity_csv',
    ]);
    $contact->addType('teacher');

    $order = makeOrderForDashboardTest();

    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-12345',
        'candidate_name' => 'Freddie Smith',
        'date_of_birth' => '2014-05-14',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'result' => 'Merit',
        'score' => 78,
        'teacher_contact_id' => $contact->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('hasLinkedContact', true)
                ->has('examEntries', 1)
                ->where('examEntries.0.candidate_name', 'Freddie Smith')
                ->where('examEntries.0.date_of_birth', '14 May 2014')
                ->where('examEntries.0.result', 'Merit')
        );
});

test('a teacher with matching exam_entries via applicant_email sees their candidates', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'apply@example.com',
    ]);

    $order = makeOrderForDashboardTest();

    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-99999',
        'candidate_name' => 'Iris Solo',
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-02-15',
        'result' => 'Pass',
        'applicant_email' => 'apply@example.com',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(
            fn ($page) => $page
                ->where('hasLinkedContact', true)
                ->has('examEntries', 1)
                ->where('examEntries.0.candidate_name', 'Iris Solo')
        );
});

test('a teacher with no matches sees the linkage form (hasLinkedContact false, no entries)', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'lonely@example.com',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('hasLinkedContact', false)
                ->has('examEntries', 0)
        );
});

test('submitting the linkage form creates an admin task', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'name' => 'Lonely Larry',
        'email' => 'larry@example.com',
    ]);

    $this->actingAs($user)
        ->post('/dashboard/link-request', [
            'alternative_email' => 'larry-on-trinity@example.com',
            'note' => 'I run a music school in Liverpool',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHas('success');

    $task = Task::latest('id')->first();
    expect($task)->not->toBeNull();
    expect($task->title)->toContain('larry@example.com');
    expect($task->title)->toContain('larry-on-trinity@example.com');
    expect($task->detail)->toContain('Lonely Larry');
    expect($task->detail)->toContain('I run a music school in Liverpool');
    expect($task->category)->toBe('admin');
    expect($task->status)->toBe('pending');
});

test('linkage form requires a valid email', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)
        ->post('/dashboard/link-request', [
            'alternative_email' => 'not-an-email',
            'note' => '',
        ])
        ->assertSessionHasErrors('alternative_email');

    expect(Task::count())->toBe(0);
});

test('admin sees the admin quick-links view, not the user candidate table', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page->component('Dashboard')
        );
});

test('guests are redirected to login from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

// ──────────────────────────────────────────
// Report-a-correction endpoint
// ──────────────────────────────────────────

test('a teacher can report a correction on their own entry (matched by applicant_email)', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
    ]);

    $order = makeOrderForDashboardTest();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-CORR1',
        'candidate_name' => 'Fred Smith',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'applicant_email' => 'tina@example.com',
    ]);

    $this->actingAs($user)
        ->post("/dashboard/entries/{$entry->id}/correction-request", [
            'note' => 'Spelling should be Freddie not Fred. DOB should be 14/05/2014.',
        ])
        ->assertRedirect('/dashboard')
        ->assertSessionHas('success');

    $task = Task::latest('id')->first();
    expect($task)->not->toBeNull();
    expect($task->title)->toContain('Fred Smith');
    expect($task->title)->toContain((string) $entry->id);
    expect($task->detail)->toContain('Tina Teacher');
    expect($task->detail)->toContain('Freddie not Fred');
    expect($task->category)->toBe('admin');
});

test('a teacher cannot report a correction on someone else\'s entry', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'tina@example.com',
    ]);

    $order = makeOrderForDashboardTest();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-NOTYOURS',
        'candidate_name' => 'Not Your Student',
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'applicant_email' => 'someone-else@example.com',
    ]);

    $this->actingAs($user)
        ->post("/dashboard/entries/{$entry->id}/correction-request", [
            'note' => 'Trying to mess with someone else\'s record',
        ])
        ->assertStatus(403);

    expect(Task::count())->toBe(0);
});

test('an admin can report a correction on any entry', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $order = makeOrderForDashboardTest();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-ADMIN1',
        'candidate_name' => 'Other Person',
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'applicant_email' => 'someone-else@example.com',
    ]);

    $this->actingAs($admin)
        ->post("/dashboard/entries/{$entry->id}/correction-request", [
            'note' => 'Admin override fix',
        ])
        ->assertRedirect('/dashboard');

    expect(Task::count())->toBe(1);
});

test('correction note is required and rejects very short input', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'tina@example.com',
    ]);

    $order = makeOrderForDashboardTest();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-VAL1',
        'candidate_name' => 'Val Idate',
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'applicant_email' => 'tina@example.com',
    ]);

    // Empty note
    $this->actingAs($user)
        ->post("/dashboard/entries/{$entry->id}/correction-request", ['note' => ''])
        ->assertSessionHasErrors('note');

    // Too short
    $this->actingAs($user)
        ->post("/dashboard/entries/{$entry->id}/correction-request", ['note' => 'oops'])
        ->assertSessionHasErrors('note');

    expect(Task::count())->toBe(0);
});

test('a teacher can report a correction via teacher_contact_id link', function () {
    $user = User::factory()->create([
        'role' => 'teacher',
        'email' => 'linked@example.com',
    ]);

    $contact = ExamContact::create([
        'name' => 'Linked Teacher',
        'email' => 'linked@example.com',
        'source' => 'trinity_csv',
    ]);
    $contact->addType('teacher');

    $order = makeOrderForDashboardTest();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-LINKED1',
        'candidate_name' => 'Linked Candidate',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
        'teacher_contact_id' => $contact->id,
    ]);

    $this->actingAs($user)
        ->post("/dashboard/entries/{$entry->id}/correction-request", [
            'note' => 'Spelling correction needed',
        ])
        ->assertRedirect('/dashboard');

    expect(Task::count())->toBe(1);
});
