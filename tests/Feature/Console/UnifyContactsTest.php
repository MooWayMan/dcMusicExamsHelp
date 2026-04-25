<?php

// tests/Feature/Console/UnifyContactsTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Student;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────

function makeUnifyTestOrder(?string $trinityNumber = null): Order
{
    return Order::create([
        'trinity_order_number' => $trinityNumber ?? 'ORD-'.fake()->unique()->numerify('######'),
        'delivery_method' => 'Digital',
        'subject_area' => 'Music',
        'candidates' => 1,
        'venue' => '',
        'order_status' => 'Processed',
        'requested_start_date' => '2026-03-01',
    ]);
}

// ──────────────────────────────────────────
// Tests
// ──────────────────────────────────────────

it('creates an exam_contact for each canonical entry that does not exist yet', function () {
    expect(ExamContact::count())->toBe(0);

    $this->artisan('contacts:unify')->assertExitCode(0);

    // From the canonical map — Clare Keeling must exist after running
    $clare = ExamContact::whereRaw('LOWER(name) = ?', ['clare keeling'])->first();
    expect($clare)->not->toBeNull();
    expect($clare->email)->toBe('musiclearn11@gmail.com');
});

it('matches by email and updates rather than duplicating', function () {
    $existing = ExamContact::create([
        'name' => 'Clare Keeling',
        'email' => 'musiclearn11@gmail.com',
    ]);

    $this->artisan('contacts:unify')->assertExitCode(0);

    $count = ExamContact::whereRaw('LOWER(name) = ?', ['clare keeling'])->count();
    expect($count)->toBe(1);
    expect($existing->fresh()->id)->toBe($existing->id);
});

it('syncs the contact_types pivot for each canonical contact', function () {
    $this->artisan('contacts:unify')->assertExitCode(0);

    $clare = ExamContact::whereRaw('LOWER(name) = ?', ['clare keeling'])->first();
    expect($clare->isTeacher())->toBeTrue();
    expect($clare->isParent())->toBeFalse();

    // Alexandra is multi-type
    $alex = ExamContact::whereRaw('LOWER(name) = ?', ['alexandra bibby'])->first();
    expect($alex->isTeacher())->toBeTrue();
    expect($alex->isParent())->toBeTrue();

    // Daniel is school_admin, NOT teacher
    $daniel = ExamContact::whereRaw('LOWER(name) = ?', ['daniel rogers'])->first();
    expect($daniel->isSchoolAdmin())->toBeTrue();
    expect($daniel->isTeacher())->toBeFalse();
});

it('is idempotent — running twice produces the same state', function () {
    $this->artisan('contacts:unify')->assertExitCode(0);
    $countAfterFirst = ExamContact::count();
    $typesAfterFirst = DB::table('contact_types')->count();

    $this->artisan('contacts:unify')->assertExitCode(0);

    expect(ExamContact::count())->toBe($countAfterFirst);
    expect(DB::table('contact_types')->count())->toBe($typesAfterFirst);
});

it('dry-run makes no DB changes', function () {
    $beforeContacts = ExamContact::count();
    $beforeTypes = DB::table('contact_types')->count();

    $this->artisan('contacts:unify --dry-run')->assertExitCode(0);

    expect(ExamContact::count())->toBe($beforeContacts);
    expect(DB::table('contact_types')->count())->toBe($beforeTypes);
});

it('copies profile fields from a matching User row', function () {
    User::create([
        'name' => 'Clare Keeling',
        'email' => 'clare-keeling@placeholder.musicexams.help',
        'password' => bcrypt('test'),
        'role' => 'teacher',
        'phone' => '0151 000 0000',
        'met_face_to_face' => true,
        'spoken_on_phone' => true,
    ]);

    $this->artisan('contacts:unify')->assertExitCode(0);

    $clare = ExamContact::whereRaw('LOWER(name) = ?', ['clare keeling'])->first();
    expect($clare->phone)->toBe('0151 000 0000');
    expect($clare->met_face_to_face)->toBeTrue();
    expect($clare->spoken_on_phone)->toBeTrue();
});

it('adds subscriber type to non-test subscribers', function () {
    Subscriber::create([
        'name' => 'Clare Keeling',
        'email' => 'musiclearn11@gmail.com',
        'role' => 'teacher',
        'source' => 'website',
        'subscribed_at' => now(),
    ]);

    $this->artisan('contacts:unify')->assertExitCode(0);

    $clare = ExamContact::whereRaw('LOWER(name) = ?', ['clare keeling'])->first();
    expect($clare->isSubscriber())->toBeTrue();
    expect($clare->isTeacher())->toBeTrue();
});

it('soft-deletes Paul\'s test subscriber entries', function () {
    $test = Subscriber::create([
        'name' => 'Test Bot',
        'email' => 'paul-test@example.com',
        'source' => 'website',
        'subscribed_at' => now(),
    ]);

    $this->artisan('contacts:unify')->assertExitCode(0);

    expect($test->fresh()->unsubscribed_at)->not->toBeNull();
});

it('backfills exam_entries.teacher_contact_id from teacher_name', function () {
    $order = makeUnifyTestOrder();
    $entry = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => 'Sam Williamson',
        'teacher_name' => 'Alexandra Bibby',
        'grade' => 'Grade 2',
        'subject_area' => 'Music',
        'delivery_method' => 'F2F',
    ]);

    expect($entry->teacher_contact_id)->toBeNull();

    $this->artisan('contacts:unify')->assertExitCode(0);

    $alex = ExamContact::whereRaw('LOWER(name) = ?', ['alexandra bibby'])->first();
    expect($entry->fresh()->teacher_contact_id)->toBe($alex->id);
});

it('backfills students.teacher_contact_id via the student\'s exam_entries', function () {
    $order = makeUnifyTestOrder();
    $student = Student::create([
        'first_name' => 'Sam',
        'last_name' => 'Williamson',
    ]);

    ExamEntry::create([
        'order_id' => $order->id,
        'student_id' => $student->id,
        'candidate_name' => 'Sam Williamson',
        'teacher_name' => 'Alexandra Bibby',
        'grade' => 'Grade 2',
        'subject_area' => 'Music',
        'delivery_method' => 'F2F',
    ]);

    expect($student->teacher_contact_id)->toBeNull();

    $this->artisan('contacts:unify')->assertExitCode(0);

    $alex = ExamContact::whereRaw('LOWER(name) = ?', ['alexandra bibby'])->first();
    expect($student->fresh()->teacher_contact_id)->toBe($alex->id);
});
