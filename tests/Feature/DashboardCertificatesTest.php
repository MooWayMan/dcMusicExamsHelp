<?php

// tests/Feature/DashboardCertificatesTest.php
//
// A teacher downloads their own candidates' certificates from the dashboard,
// one at a time or as a ZIP for the dates shown (built 27 Sep 2026). Whose
// candidates these are comes from the signed-in user alone, via
// App\Services\TeacherEntries; anyone else's entry is a 404.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => fakeCertificateTemplates());

function certTeacher(string $name, string $email): array
{
    $contact = ExamContact::create(['name' => $name, 'email' => $email]);
    $contact->addType('teacher');

    return [$contact, User::factory()->create(['email' => $email, 'role' => 'teacher'])];
}

function certEntry(ExamContact $teacher, string $candidate, ?int $score = 80): ExamEntry
{
    $order = Order::factory()->create(['requested_start_date' => Carbon::create(2026, 7, 1)]);

    return ExamEntry::create([
        'order_id' => $order->id,
        'candidate_name' => $candidate,
        'candidate_number' => '1-'.random_int(10000000, 99999999),
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 7, 14),
        'score' => $score,
        'teacher_contact_id' => $teacher->id,
    ]);
}

test('a teacher downloads their own candidate\'s certificate', function () {
    [$contact, $user] = certTeacher('Alexandra King', 'alexi@example.test');
    $entry = certEntry($contact, 'Wilfred Morris', 63);

    $this->actingAs($user)->get("/dashboard/certificates/{$entry->id}")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="Wilfred_Morris_Bravo.pdf"');
});

test('another teacher\'s candidate, or one with no result yet, is a 404', function () {
    [$mine, $user] = certTeacher('Alexandra King', 'alexi@example.test');
    [$theirs] = certTeacher('Daniel Rogers', 'danny@example.test');
    $notMine = certEntry($theirs, 'Aaron Gillespie');
    $noResult = certEntry($mine, 'Pending Pupil', null);

    $this->actingAs($user)->get("/dashboard/certificates/{$notMine->id}")->assertNotFound();
    $this->actingAs($user)->get("/dashboard/certificates/{$noResult->id}")->assertNotFound();
});

test('the ZIP holds only the teacher\'s own certificates with results, in the dates chosen', function () {
    [$contact, $user] = certTeacher('Maria Nielsen', 'maria@example.test');
    [$other] = certTeacher('Daniel Rogers', 'danny@example.test');
    certEntry($contact, 'Eos Daniels', 89);
    certEntry($contact, 'Emily Taylor', 77);
    certEntry($contact, 'Still Waiting', null);
    certEntry($other, 'Not Hers', 80);

    $response = $this->actingAs($user)->get('/dashboard/certificates?from=2026-07-01&to=2026-09-30')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/zip');

    $path = tempnam(sys_get_temp_dir(), 'z');
    file_put_contents($path, $response->getContent());
    $zip = new ZipArchive;
    $zip->open($path);
    $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();
    $zip->close();
    unlink($path);

    expect($names)->toBe(['Emily_Taylor_Take_a_Bow.pdf', 'Eos_Daniels_Standing_Ovation.pdf']);
});

test('a range with no results says so instead of downloading an empty file', function () {
    [$contact, $user] = certTeacher('Maria Nielsen', 'maria@example.test');
    certEntry($contact, 'Still Waiting', null);

    $this->actingAs($user)->from('/dashboard')->get('/dashboard/certificates')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('error');
});

test('an admin previewing a teacher downloads that teacher\'s certificates; a teacher cannot use those routes', function () {
    [$contact, $user] = certTeacher('Maria Nielsen', 'maria@example.test');
    $entry = certEntry($contact, 'Eos Daniels', 89);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get("/admin/contacts/{$contact->id}/certificates/{$entry->id}")->assertOk();
    $this->actingAs($admin)->get("/admin/contacts/{$contact->id}/certificates?from=2026-07-01&to=2026-09-30")->assertOk();
    $this->actingAs($user)->get("/admin/contacts/{$contact->id}/certificates/{$entry->id}")->assertForbidden();
});
