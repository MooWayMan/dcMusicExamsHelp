<?php

// tests/Feature/SchemaCleanupTest.php
//
// Locks in the post-refactor schema state — the legacy columns and
// relations are gone for good and the replacement paths render fine.
//
// Sister of the unified-contacts shipping work: orders.user_id is fully
// replaced by created_by_contact_id (→ exam_contacts), and
// students.instrument_id is replaced by per-exam exam_entries.instrument_id.

use App\Models\ExamContact;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Schema is gone
// ──────────────────────────────────────────

test('orders table no longer has user_id column', function () {
    expect(Schema::hasColumn('orders', 'user_id'))->toBeFalse();
});

test('students table no longer has instrument_id column', function () {
    expect(Schema::hasColumn('students', 'instrument_id'))->toBeFalse();
});

// ──────────────────────────────────────────
// Models no longer expose legacy relations
// ──────────────────────────────────────────

test('Order model no longer defines a teacher() relation', function () {
    expect(method_exists(Order::class, 'teacher'))->toBeFalse();
});

test('Order model no longer lists user_id as fillable', function () {
    $order = new Order;
    expect($order->getFillable())->not->toContain('user_id');
});

test('Student model no longer defines an instrument() relation', function () {
    expect(method_exists(Student::class, 'instrument'))->toBeFalse();
});

test('Student model no longer lists instrument_id as fillable', function () {
    $student = new Student;
    expect($student->getFillable())->not->toContain('instrument_id');
});

// ──────────────────────────────────────────
// Replacement paths still work end-to-end
// ──────────────────────────────────────────

test('orders index uses createdByContact for teacher_name (no User fallback needed)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $contact = ExamContact::create([
        'name' => 'Megan Price',
        'email' => 'meganclr96@gmail.com',
    ]);
    $contact->addType('teacher');

    Order::factory()->create([
        'created_by_contact_id' => $contact->id,
        'applicant_name' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Index')
            ->where('orders.data.0.teacher_name', 'Megan Price')
        );
});

test('students index exposes instruments as a collection (chips), not a single value', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $piano = Instrument::firstOrCreate(['name' => 'Piano'], ['family' => 'Keyboard']);
    $drums = Instrument::firstOrCreate(['name' => 'Drum Kit'], ['family' => 'Percussion']);

    $student = Student::create([
        'first_name' => 'Test',
        'last_name'  => 'Multi',
    ]);
    $order = Order::factory()->create();
    $student->examEntries()->create([
        'order_id' => $order->id,
        'candidate_name' => 'Test Multi',
        'instrument_id' => $piano->id,
        'delivery_method' => 'Digital',
        'source' => 'manual',
    ]);
    $student->examEntries()->create([
        'order_id' => $order->id,
        'candidate_name' => 'Test Multi',
        'instrument_id' => $drums->id,
        'delivery_method' => 'Digital',
        'source' => 'manual',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Index')
            ->has('students.data.0.instruments', 2)
        );
});

test('students index summary total reflects the active family filter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $piano = Instrument::firstOrCreate(['name' => 'Piano'], ['family' => 'Keyboard']);
    $drums = Instrument::firstOrCreate(['name' => 'Drum Kit'], ['family' => 'Percussion']);

    $order = Order::factory()->create();

    foreach (['Pianist One', 'Pianist Two', 'Pianist Three'] as $name) {
        $s = Student::create(['first_name' => $name, 'last_name' => 'X']);
        $s->examEntries()->create([
            'order_id' => $order->id,
            'candidate_name' => $name,
            'instrument_id' => $piano->id,
            'delivery_method' => 'Digital',
            'source' => 'manual',
        ]);
    }

    $drummer = Student::create(['first_name' => 'Just', 'last_name' => 'Drums']);
    $drummer->examEntries()->create([
        'order_id' => $order->id,
        'candidate_name' => 'Just Drums',
        'instrument_id' => $drums->id,
        'delivery_method' => 'Digital',
        'source' => 'manual',
    ]);

    // Unfiltered: 4 students.
    $this->actingAs($admin)
        ->get(route('admin.students.index'))
        ->assertInertia(fn ($page) => $page->where('summary.total', 4));

    // Filtered to Keyboard: 3 students.
    $this->actingAs($admin)
        ->get(route('admin.students.index', ['family' => 'Keyboard']))
        ->assertInertia(fn ($page) => $page->where('summary.total', 3));
});
