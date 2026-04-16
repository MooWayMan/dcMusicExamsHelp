<?php

// tests/Feature/Console/MigrateToExamContactsTest.php

use App\Models\ContactEmail;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// Helper to set up a teacher with emails and a linked exam entry
// ──────────────────────────────────────────

function createTeacherWithEntry(string $name, string $email, string $type = 'teacher'): array
{
    $teacher = Teacher::create([
        'name' => $name,
        'type' => $type,
    ]);

    TeacherEmail::create([
        'teacher_id' => $teacher->id,
        'email' => $email,
        'label' => 'primary',
        'is_primary' => true,
    ]);

    $order = Order::create([
        'trinity_order_number' => 'ORD-' . fake()->unique()->numerify('###'),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => '',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-03-01',
    ]);

    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Test Student',
        'teacher_name' => $name,
        'teacher_id' => $teacher->id,
        'grade' => 'Grade 3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
    ]);

    return compact('teacher', 'order', 'entry');
}

// ──────────────────────────────────────────
// Tests
// ──────────────────────────────────────────

it('creates ExamContacts from Teacher records', function () {
    $data = createTeacherWithEntry('Daniel Rogers', 'dan@example.com');

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $contact = ExamContact::where('name', 'Daniel Rogers')->first();

    expect($contact)->not->toBeNull();
    expect($contact->role)->toBe('teacher');
    expect($contact->source)->toBe('migrated_from_teachers');
    expect($contact->email)->toBe('dan@example.com');
});

it('copies TeacherEmail records to contact_emails', function () {
    // Use a name NOT in the known contacts list to isolate the test
    $teacher = Teacher::create(['name' => 'Test Musician', 'type' => 'teacher']);

    TeacherEmail::create([
        'teacher_id' => $teacher->id,
        'email' => 'test@personal.com',
        'label' => 'personal',
        'is_primary' => true,
    ]);

    TeacherEmail::create([
        'teacher_id' => $teacher->id,
        'email' => 'test@school.com',
        'label' => 'school',
        'is_primary' => false,
    ]);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $contact = ExamContact::where('name', 'Test Musician')->first();
    $emails = ContactEmail::where('exam_contact_id', $contact->id)->get();

    expect($emails)->toHaveCount(2);
    expect($emails->firstWhere('is_primary', true)->email)->toBe('test@personal.com');
});

it('links exam entries to ExamContact via teacher_contact_id', function () {
    $data = createTeacherWithEntry('Clare Keeling', 'clare@example.com');

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $entry = $data['entry']->fresh();
    $contact = ExamContact::where('name', 'Clare Keeling')->first();

    expect($entry->teacher_contact_id)->toBe($contact->id);
    expect($entry->teacher_credit_status)->toBe('confirmed');
});

it('links students to ExamContact via most common teacher in their entries', function () {
    $data = createTeacherWithEntry('Daniel Rogers', 'dan@example.com');

    $student = Student::create([
        'first_name' => 'Test',
        'last_name' => 'Student',
    ]);

    $data['entry']->update(['student_id' => $student->id]);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $student->refresh();
    $contact = ExamContact::where('name', 'Daniel Rogers')->first();

    expect($student->teacher_contact_id)->toBe($contact->id);
    expect($student->teacher_credit_status)->toBe('confirmed');
});

it('does not duplicate ExamContacts on re-run', function () {
    createTeacherWithEntry('Jennifer Hynes', 'jen@example.com');

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    expect(ExamContact::where('name', 'Jennifer Hynes')->count())->toBe(1);
});

it('does not duplicate contact_emails on re-run', function () {
    // Use a name NOT in the known contacts list to isolate the test
    $teacher = Teacher::create(['name' => 'Test Drummer', 'type' => 'teacher']);

    TeacherEmail::create([
        'teacher_id' => $teacher->id,
        'email' => 'drummer@example.com',
        'label' => 'personal',
        'is_primary' => true,
    ]);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $contact = ExamContact::where('name', 'Test Drummer')->first();

    expect(ContactEmail::where('exam_contact_id', $contact->id)->count())->toBe(1);
});

it('maps parent type correctly', function () {
    Teacher::create(['name' => 'Helen Khoo', 'type' => 'parent']);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $contact = ExamContact::where('name', 'Helen Khoo')->first();

    expect($contact->role)->toBe('parent');
});

it('creates order_contacts for teacher roles', function () {
    $data = createTeacherWithEntry('Roxanne Twomey', 'rox@example.com');

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $contact = ExamContact::where('name', 'Roxanne Twomey')->first();

    $this->assertDatabaseHas('order_contacts', [
        'order_id' => $data['order']->id,
        'exam_contact_id' => $contact->id,
        'role_in_order' => 'teacher',
    ]);
});

it('links by teacher_name when teacher_id is null', function () {
    // Create a teacher and ExamContact but entry only has teacher_name, no teacher_id
    Teacher::create(['name' => 'Jenny Capstick', 'type' => 'teacher']);

    $order = Order::create([
        'trinity_order_number' => 'ORD-999',
        'delivery_method' => 'Default',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => 'Learn Music Ltd',
        'order_status' => 'Delivered',
        'requested_start_date' => '2026-03-20',
    ]);

    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Some Student',
        'teacher_name' => 'Jenny Capstick',
        'teacher_id' => null,
        'grade' => 'Grade 1',
        'subject_area' => 'Music',
        'delivery_method' => 'Default',
    ]);

    $this->artisan('contacts:migrate-from-teachers')
        ->assertExitCode(0);

    $entry->refresh();
    $contact = ExamContact::where('name', 'Jenny Capstick')->first();

    expect($entry->teacher_contact_id)->toBe($contact->id);
});

it('dry run does not create any records', function () {
    createTeacherWithEntry('Test Teacher', 'test@example.com');

    $this->artisan('contacts:migrate-from-teachers', ['--dry-run' => true])
        ->assertExitCode(0);

    expect(ExamContact::count())->toBe(0);
    expect(ContactEmail::count())->toBe(0);
});
