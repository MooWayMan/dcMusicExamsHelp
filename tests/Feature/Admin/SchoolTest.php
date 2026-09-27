<?php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\School;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function schoolAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function schoolTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access schools index', function () {
    $this->get(route('admin.schools.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access schools index', function () {
    $this->actingAs(schoolTeacher())
        ->get(route('admin.schools.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view schools index', function () {
    $admin = schoolAdmin();
    School::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.schools.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Schools/Index')
            ->has('schools.data', 3)
        );
});

test('schools index can be searched by name', function () {
    $admin = schoolAdmin();
    School::factory()->create(['name' => 'Maple Academy']);
    School::factory()->create(['name' => 'Oak School']);

    $this->actingAs($admin)
        ->get(route('admin.schools.index', ['search' => 'Maple']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('schools.data', 1)
            ->where('schools.data.0.name', 'Maple Academy')
        );
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create(['name' => 'Test School']);

    $this->actingAs($admin)
        ->get(route('admin.schools.show', $school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Schools/Show')
            ->where('school.name', 'Test School')
        );
});

// ──────────────────────────────────────────
// Create & Store
// ──────────────────────────────────────────

test('admin can view the create school form', function () {
    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Schools/Create'));
});

test('admin can create a new school', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('admin.schools.store'), [
            'name' => 'New School',
            'address' => '123 Test Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('schools', [
        'name' => 'New School',
        'city' => 'London',
    ]);
});

// ──────────────────────────────────────────
// Edit & Update
// ──────────────────────────────────────────

test('admin can view the edit school form', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.schools.edit', $school))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Schools/Edit'));
});

test('admin can update a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create(['name' => 'Old School']);

    $this->actingAs($admin)
        ->put(route('admin.schools.update', $school), [
            'name' => 'Renamed School',
        ])
        ->assertRedirect();

    expect($school->fresh()->name)->toBe('Renamed School');
});

// ──────────────────────────────────────────
// Delete
// ──────────────────────────────────────────

test('admin can delete a school', function () {
    $admin = schoolAdmin();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.schools.destroy', $school))
        ->assertRedirect();

    expect($school->fresh()->trashed())->toBeTrue();
});

// ──────────────────────────────────────────
// Who works here, and what the school teaches (App\Services\SchoolLinks)
// ──────────────────────────────────────────

function schoolStaff(string $name, string $type = 'teacher'): ExamContact
{
    $contact = ExamContact::create(['name' => $name]);
    $contact->addType($type);

    return $contact;
}

test('saving a school records who works there, former teachers included, and the school page shows them', function () {
    $school = School::factory()->create(['name' => 'Learn Music Ltd']);
    $clare = schoolStaff('Clare Keeling', 'school_admin');
    $emily = schoolStaff('Emily Bates');
    $piano = Instrument::create(['name' => 'Piano', 'family' => 'Keyboard']);
    $trumpet = Instrument::create(['name' => 'Trumpet', 'family' => 'Brass']);

    $this->actingAs(schoolAdmin())
        ->put(route('admin.schools.update', $school), [
            'name' => 'Learn Music Ltd',
            'teachers' => [['id' => $emily->id, 'former' => true], ['id' => $clare->id, 'former' => false]],
            'instrument_ids' => [$piano->id, $trumpet->id],
        ])
        ->assertRedirect();

    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.show', $school))
        ->assertInertia(fn ($page) => $page
            ->where('school.teachers', [
                ['id' => $clare->id, 'name' => 'Clare Keeling', 'former' => false],
                ['id' => $emily->id, 'name' => 'Emily Bates', 'former' => true],
            ])
            ->where('school.instruments', fn ($list) => collect($list)->pluck('name')->sort()->values()->all() === ['Piano', 'Trumpet']));

    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.edit', $school))
        ->assertInertia(fn ($page) => $page
            ->where('teachers.1.former', true)
            ->where('instrumentIds', fn ($ids) => collect($ids)->sort()->values()->all() === [$piano->id, $trumpet->id])
            ->where('teacherOptions', fn ($o) => collect($o)->pluck('name')->contains('Emily Bates')));
});

test('a teacher removed on Edit is unlinked, and an instrument unticked is removed', function () {
    $school = School::factory()->create();
    $a = schoolStaff('Jennifer Hynes');
    $school->contacts()->attach($a->id);
    $piano = Instrument::create(['name' => 'Piano', 'family' => 'Keyboard']);
    $school->instruments()->attach($piano->id);

    $this->actingAs(schoolAdmin())->put(route('admin.schools.update', $school), [
        'name' => $school->name, 'teachers' => [], 'instrument_ids' => [],
    ]);

    expect($school->contacts()->count())->toBe(0)
        ->and($school->instruments()->count())->toBe(0);
});

test('a save without the teacher and instrument lists leaves them as they were', function () {
    $school = School::factory()->create();
    $school->contacts()->attach(schoolStaff('Clare Keeling')->id);
    $school->instruments()->attach(Instrument::create(['name' => 'Drums', 'family' => 'Percussion'])->id);

    $this->actingAs(schoolAdmin())->put(route('admin.schools.update', $school), ['name' => 'Renamed']);

    expect($school->contacts()->count())->toBe(1)
        ->and($school->instruments()->count())->toBe(1);
});

test('a teacher whose pupils only sat exams at the school (the venue) is not listed as working there', function () {
    // 27 Sep 2026: Trinity's venue was stored as the school, so Jennifer
    // Hynes showed as a Learn Music teacher because her pupils sat exams there.
    $school = School::factory()->create(['name' => 'Learn Music Ltd']);
    $visitor = schoolStaff('Jennifer Hynes');
    $order = Order::create([
        'trinity_order_number' => 'ORD-5550001',
        'order_status' => 'Submitted',
        'subject_area' => 'Music',
        'delivery_method' => 'Face to Face',
        'requested_start_date' => '2026-03-01',
    ]);
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-55500',
        'candidate_name' => 'Some Pupil',
        'grade' => '2',
        'subject_area' => 'Music',
        'delivery_method' => 'Face to Face',
        'exam_date' => '2026-03-10',
        'teacher_contact_id' => $visitor->id,
        'teacher_name' => 'Jennifer Hynes',
        'school_name' => 'Learn Music Ltd',
    ]);

    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.show', $school))
        ->assertInertia(fn ($page) => $page->where('school.teachers', []));

    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.index'))
        ->assertInertia(fn ($page) => $page->where('schools.data.0.teachers_count', 0));
});

test('the schools list counts current teachers only', function () {
    $school = School::factory()->create();
    $school->contacts()->attach(schoolStaff('Clare Keeling')->id);
    $school->contacts()->attach(schoolStaff('David Keeling')->id, ['former' => true]);

    $this->actingAs(schoolAdmin())
        ->get(route('admin.schools.index'))
        ->assertInertia(fn ($page) => $page->where('schools.data.0.teachers_count', 1));
});
