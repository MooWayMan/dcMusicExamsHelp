<?php

// tests/Feature/Admin/ContactFindByEmailTest.php

use App\Models\ContactEmail;
use App\Models\ExamContact;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('findByEmail matches the primary email column', function () {
    $c = ExamContact::create(['name' => 'Daniel Rogers', 'email' => 'rogers@pulsemusicliverpool.com']);

    expect(ExamContact::findByEmail('ROGERS@pulsemusicliverpool.com')?->id)->toBe($c->id);
});

test('findByEmail matches a secondary contact_email (the same person, different address)', function () {
    $c = ExamContact::create(['name' => 'Daniel Rogers', 'email' => 'rogers@pulsemusicliverpool.com']);
    ContactEmail::create([
        'exam_contact_id' => $c->id,
        'email' => 'exams@pulsemusicliverpool.com',
        'label' => 'secondary',
        'is_primary' => false,
    ]);

    // This is the import case: a booking under exams@ resolves to Daniel
    // instead of spawning a duplicate contact.
    expect(ExamContact::findByEmail('exams@pulsemusicliverpool.com')?->id)->toBe($c->id);
});

test('findByEmail returns null for an unknown or empty email', function () {
    ExamContact::create(['name' => 'Someone', 'email' => 'someone@example.test']);

    expect(ExamContact::findByEmail('nobody@example.test'))->toBeNull()
        ->and(ExamContact::findByEmail(''))->toBeNull()
        ->and(ExamContact::findByEmail(null))->toBeNull();
});
