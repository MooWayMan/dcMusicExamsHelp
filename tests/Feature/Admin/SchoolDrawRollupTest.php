<?php

// tests/Feature/Admin/SchoolDrawRollupTest.php
//
// Phase 2 of the recognition-attribution model (13 Jun 2026): a school_admin
// entry credits the SCHOOL it's linked to (Learn Music Ltd), while an
// individual teacher's entries stay personal. The same dual-role contact
// (Emily Bates) splits per entry: her Learn Music entries roll up, her
// private-student entries stay hers. Driven entirely by exam_entries
// .booking_role + the contact_school link — no schema change.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function rollupEntry(array $attrs): ExamEntry
{
    $order = Order::create([
        'trinity_order_number' => '1-SCH-' . uniqid('', true),
        'delivery_method' => 'F2F',
        'subject_area' => 'Music',
        'candidates' => 1,
        'order_status' => 'Delivered',
        'requested_start_date' => Carbon::create(2026, 2, 15),
    ]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Candidate ' . uniqid('', true),
        'grade' => 'Grade 1',
        'subject_area' => 'Piano',
        'delivery_method' => 'F2F',
        'exam_date' => Carbon::create(2026, 2, 15),
    ], $attrs));
}

/** @return array{0: School, 1: ExamContact} */
function learnMusicSchoolAdmin(string $adminName = 'Clare Keeling'): array
{
    $school = School::create(['name' => 'Learn Music Ltd']);
    $admin = ExamContact::create(['name' => $adminName, 'email' => 'lessons@learnmusic.co.uk']);
    $admin->addType('school_admin');
    $admin->schools()->attach($school->id);

    return [$school, $admin];
}

test('school_admin entries roll up to the school in the teacher draw', function () {
    [, $clare] = learnMusicSchoolAdmin();

    foreach (range(1, 3) as $i) {
        rollupEntry([
            'teacher_name' => 'Clare Keeling',
            'teacher_contact_id' => $clare->id,
            'booking_role' => 'school_admin',
        ]);
    }

    $response = $this->actingAs($this->admin)->postJson('/admin/quarter-end/draw', [
        'type' => 'teacher', 'quarter' => 1, 'year' => 2026, 'mode' => 'test',
    ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Learn Music Ltd')
        ->and($response->json('total_tickets'))->toBe(3);
});

test('a school is eligible even with a single entry (registered-like)', function () {
    [, $clare] = learnMusicSchoolAdmin();

    rollupEntry([
        'teacher_name' => 'Clare Keeling',
        'teacher_contact_id' => $clare->id,
        'booking_role' => 'school_admin',
    ]);

    $response = $this->actingAs($this->admin)->postJson('/admin/quarter-end/draw', [
        'type' => 'teacher', 'quarter' => 1, 'year' => 2026, 'mode' => 'test',
    ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Learn Music Ltd')
        ->and($response->json('total_tickets'))->toBe(1);
});

test('individual teacher entries stay personal, not rolled to a school', function () {
    $teacher = ExamContact::create(['name' => 'Helen Help']);
    $teacher->addType('teacher');

    rollupEntry(['teacher_name' => 'Helen Help', 'teacher_contact_id' => $teacher->id, 'booking_role' => 'teacher']);
    rollupEntry(['teacher_name' => 'Helen Help', 'teacher_contact_id' => $teacher->id, 'booking_role' => 'teacher']);

    $response = $this->actingAs($this->admin)->postJson('/admin/quarter-end/draw', [
        'type' => 'teacher', 'quarter' => 1, 'year' => 2026, 'mode' => 'test',
    ]);

    $response->assertStatus(200);
    expect($response->json('winner.name'))->toBe('Helen Help');
});

test('a dual-role contact splits: school_admin entries credit the school, teacher entries stay personal', function () {
    $school = School::create(['name' => 'Learn Music Ltd']);
    $emily = ExamContact::create(['name' => 'Emily Bates', 'email' => 'emily@learnmusic.co.uk']);
    $emily->addType('school_admin');
    $emily->addType('teacher');
    $emily->schools()->attach($school->id);

    rollupEntry(['teacher_name' => 'Emily Bates', 'teacher_contact_id' => $emily->id, 'booking_role' => 'school_admin']);
    rollupEntry(['teacher_name' => 'Emily Bates', 'teacher_contact_id' => $emily->id, 'booking_role' => 'school_admin']);
    rollupEntry(['teacher_name' => 'Emily Bates', 'teacher_contact_id' => $emily->id, 'booking_role' => 'teacher']);
    rollupEntry(['teacher_name' => 'Emily Bates', 'teacher_contact_id' => $emily->id, 'booking_role' => 'teacher']);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->component('admin/QuarterEnd/Index')
            ->where('prizeDraw.eligible_teachers', function ($list) {
                $names = collect($list)->pluck('name');

                return $names->contains('Learn Music Ltd') && $names->contains('Emily Bates');
            })
        );
});

test('quarter end volume badge rolls up to the school', function () {
    [, $clare] = learnMusicSchoolAdmin();

    // 10 entries → Bronze. Credited to the school, not Clare.
    foreach (range(1, 10) as $i) {
        rollupEntry([
            'teacher_name' => 'Clare Keeling',
            'teacher_contact_id' => $clare->id,
            'booking_role' => 'school_admin',
        ]);
    }

    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=1&year=2026')
        ->assertInertia(fn ($page) => $page
            ->component('admin/QuarterEnd/Index')
            ->where('teachers', function ($teachers) {
                $school = collect($teachers)->firstWhere('teacher_name', 'Learn Music Ltd');

                return $school !== null
                    && $school['is_school'] === true
                    && $school['badge_tier'] === 'Bronze'
                    && $school['total_entries'] === 10;
            })
        );
});
