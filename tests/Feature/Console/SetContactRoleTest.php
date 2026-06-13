<?php

// tests/Feature/Console/SetContactRoleTest.php
//
// The prize-draw cleanup command: reclassify contacts wrongly tagged
// `teacher` by the old importer (parents/self-applicants) so they drop out
// of the teacher draw.

use App\Models\ExamContact;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('set-role removes the teacher type and adds parent', function () {
    $c = ExamContact::create(['name' => 'Mark Vincent-Smith']);
    $c->addType('teacher');

    $this->artisan('contacts:set-role', ['role' => 'parent', 'names' => ['Mark Vincent-Smith']])
        ->assertExitCode(0);

    $c->refresh();
    expect($c->isTeacher())->toBeFalse()
        ->and($c->isParent())->toBeTrue();
});

test('set-role resolves by id and can set candidate (self-applicants)', function () {
    $c = ExamContact::create(['name' => 'Seth Barraclough']);
    $c->addType('teacher');

    $this->artisan('contacts:set-role', ['role' => 'candidate', 'names' => [(string) $c->id]])
        ->assertExitCode(0);

    $c->refresh();
    expect($c->isTeacher())->toBeFalse()
        ->and($c->isCandidate())->toBeTrue();
});

test('set-role retags several contacts in one run', function () {
    foreach (['Helen Khoo', 'Claire Reed', 'Gillian Leslie'] as $name) {
        ExamContact::create(['name' => $name])->addType('teacher');
    }

    $this->artisan('contacts:set-role', ['role' => 'parent', 'names' => ['Helen Khoo', 'Claire Reed', 'Gillian Leslie']])
        ->assertExitCode(0);

    foreach (['Helen Khoo', 'Claire Reed', 'Gillian Leslie'] as $name) {
        $c = ExamContact::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        expect($c->isTeacher())->toBeFalse()
            ->and($c->isParent())->toBeTrue();
    }
});

test('set-role --dry-run changes nothing', function () {
    $c = ExamContact::create(['name' => 'Helen Khoo']);
    $c->addType('teacher');

    $this->artisan('contacts:set-role', ['role' => 'parent', 'names' => ['Helen Khoo'], '--dry-run' => true])
        ->assertExitCode(0);

    $c->refresh();
    expect($c->isTeacher())->toBeTrue()
        ->and($c->isParent())->toBeFalse();
});

test('set-role leaves a legitimate multi-type teacher+parent intact when told to set teacher', function () {
    // A real teacher who is also a parent — setting role=teacher must not drop teacher.
    $c = ExamContact::create(['name' => 'Alexandra Bibby']);
    $c->addType('teacher');
    $c->addType('parent');

    $this->artisan('contacts:set-role', ['role' => 'teacher', 'names' => ['Alexandra Bibby']])
        ->assertExitCode(0);

    $c->refresh();
    expect($c->isTeacher())->toBeTrue()
        ->and($c->isParent())->toBeTrue();
});

test('set-role rejects an unknown role', function () {
    $this->artisan('contacts:set-role', ['role' => 'wizard', 'names' => ['Whoever']])
        ->assertExitCode(1);
});
