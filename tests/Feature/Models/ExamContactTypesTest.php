<?php

// tests/Feature/Models/ExamContactTypesTest.php

use App\Models\ExamContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('starts with no types', function () {
    $contact = ExamContact::create(['name' => 'Test One', 'email' => 'one@test.com']);

    expect($contact->types)->toBe([]);
    expect($contact->isTeacher())->toBeFalse();
    expect($contact->isParent())->toBeFalse();
    expect($contact->isCandidate())->toBeFalse();
    expect($contact->isSchoolAdmin())->toBeFalse();
    expect($contact->isTrinityAdmin())->toBeFalse();
    expect($contact->isSubscriber())->toBeFalse();
});

it('addType inserts into the pivot', function () {
    $contact = ExamContact::create(['name' => 'Test Two', 'email' => 'two@test.com']);

    $contact->addType('teacher');

    expect($contact->refresh()->types)->toBe(['teacher']);
    expect($contact->isTeacher())->toBeTrue();

    expect(DB::table('contact_types')->where('exam_contact_id', $contact->id)->count())->toBe(1);
});

it('addType is idempotent (unique constraint)', function () {
    $contact = ExamContact::create(['name' => 'Test Three', 'email' => 'three@test.com']);

    $contact->addType('teacher');
    $contact->addType('teacher');
    $contact->addType('teacher');

    expect(DB::table('contact_types')->where('exam_contact_id', $contact->id)->count())->toBe(1);
});

it('supports multi-type membership', function () {
    $contact = ExamContact::create(['name' => 'Alex Bibby', 'email' => 'a@b.com']);

    $contact->addType('teacher');
    $contact->addType('parent');

    $contact->refresh();

    expect($contact->isTeacher())->toBeTrue();
    expect($contact->isParent())->toBeTrue();
    expect($contact->isSubscriber())->toBeFalse();
    expect($contact->types)->toContain('teacher')->toContain('parent');
});

it('removeType deletes the pivot row', function () {
    $contact = ExamContact::create(['name' => 'Test Four', 'email' => 'four@test.com']);

    $contact->addType('teacher');
    $contact->addType('parent');
    $contact->removeType('parent');

    $contact->refresh();
    expect($contact->isTeacher())->toBeTrue();
    expect($contact->isParent())->toBeFalse();
});

it('addType rejects unknown types', function () {
    $contact = ExamContact::create(['name' => 'Test Five', 'email' => 'five@test.com']);

    expect(fn () => $contact->addType('wizard'))
        ->toThrow(InvalidArgumentException::class);
});

it('withType scope filters to contacts that have the given type', function () {
    $teacher = ExamContact::create(['name' => 'A Teacher', 'email' => 't@x.com']);
    $teacher->addType('teacher');

    $parent = ExamContact::create(['name' => 'A Parent', 'email' => 'p@x.com']);
    $parent->addType('parent');

    $both = ExamContact::create(['name' => 'Both', 'email' => 'b@x.com']);
    $both->addType('teacher');
    $both->addType('parent');

    $teachers = ExamContact::withType('teacher')->pluck('name')->all();

    expect($teachers)->toContain('A Teacher');
    expect($teachers)->toContain('Both');
    expect($teachers)->not->toContain('A Parent');
});

it('withType scope accepts an array (any-of match)', function () {
    $teacher = ExamContact::create(['name' => 'T', 'email' => 't@x.com']);
    $teacher->addType('teacher');

    $admin = ExamContact::create(['name' => 'A', 'email' => 'a@x.com']);
    $admin->addType('school_admin');

    $other = ExamContact::create(['name' => 'O', 'email' => 'o@x.com']);
    $other->addType('subscriber');

    $matches = ExamContact::withType(['teacher', 'school_admin'])->pluck('name')->all();

    expect($matches)->toContain('T')->toContain('A');
    expect($matches)->not->toContain('O');
});

it('exposes the allowed types constant', function () {
    expect(ExamContact::TYPES)
        ->toContain('teacher')
        ->toContain('parent')
        ->toContain('candidate')
        ->toContain('school_admin')
        ->toContain('trinity_admin')
        ->toContain('subscriber');
});
