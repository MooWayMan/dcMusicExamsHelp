<?php

// tests/Feature/TeacherRewardsTest.php
//
// The dashboard's Rewards card (built 27 Sep 2026): a teacher's badge with
// its certificate, and the prizes won by them or their own pupils. Only
// their own pupils, named first name + initial unless opted in, and the
// badge is the certificate batch's rule so the two cannot disagree.

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\PrizeDraw;
use App\Models\TopScorerPublication;
use App\Models\User;
use App\Services\TeacherRewards;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => fakeCertificateTemplates());

function rewardsTeacher(string $name, string $email): array
{
    $contact = ExamContact::create(['name' => $name, 'email' => $email]);
    $contact->addType('teacher');

    return [$contact, User::factory()->create(['email' => $email, 'role' => 'teacher'])];
}

function rewardsEntry(ExamContact $teacher, string $candidate, array $extra = []): ExamEntry
{
    $order = Order::factory()->create(['requested_start_date' => Carbon::create(2026, 5, 1)]);

    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => $candidate,
        'candidate_number' => '1-'.random_int(10000000, 99999999),
        'grade' => '3',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 5, 14),
        'score' => 80,
        'teacher_name' => $teacher->name,
        'teacher_contact_id' => $teacher->id,
    ], $extra));
}

function rewardsDraw(string $type, string $winner, int $by): void
{
    PrizeDraw::create([
        'type' => $type, 'quarter' => 2, 'year' => 2026,
        'winner_name' => $winner, 'total_tickets' => 20, 'drawn_by' => $by,
    ]);
}

function rewardsScenario(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    [$maria, $user] = rewardsTeacher('Maria Nielsen', 'maria@example.test');
    [$danny] = rewardsTeacher('Daniel Rogers', 'danny@example.test');

    rewardsEntry($maria, 'Eos Daniels');
    rewardsEntry($maria, 'Emily Taylor', ['show_full_name' => true, 'score' => 95]);
    foreach (range(1, 8) as $i) {
        rewardsEntry($maria, "Pupil Number{$i}");
    }
    rewardsEntry($danny, 'Aaron Gillespie', ['score' => 96, 'grade' => '7']);

    rewardsDraw('student', 'Eos Daniels', $admin->id);
    rewardsDraw('teacher', 'Maria Nielsen', $admin->id);
    TopScorerPublication::create([
        'quarter' => 2, 'year' => 2026,
        'winners' => [
            'initial_5' => ['distinction' => [['name' => 'Emily T', 'full_name' => 'Emily Taylor']], 'merit' => []],
            '6_8' => ['distinction' => [['name' => 'Aaron G', 'full_name' => 'Aaron Gillespie']], 'merit' => []],
        ],
        'finalised_with_pending' => false, 'pending_count' => 0,
        'published_by' => $admin->id, 'published_at' => now(),
    ]);

    return [$maria, $user, $admin];
}

test('a teacher sees their badge and the prizes for them and their own pupils only', function () {
    [$maria] = rewardsScenario();

    $rows = app(TeacherRewards::class)->forContact($maria);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['label'])->toBe('2nd Quarter 2026')
        ->and($rows[0]['badge'])->toBe('Bronze')
        ->and($rows[0]['certificate'])->toBe('Bronze Appreciation Certificate')
        ->and(collect($rows[0]['prizes'])->pluck('who')->sort()->values()->all())
        ->toBe(['Emily Taylor', 'Eos D', 'You']);
});

test('the prize status is in the teacher\'s words, not the admin list\'s', function () {
    [$maria] = rewardsScenario();

    $statuses = collect(app(TeacherRewards::class)->forContact($maria)[0]['prizes'])->pluck('status')->unique()->all();

    expect($statuses)->toBe([TeacherRewards::STATUS['email_not_sent']]);
});

test('below 10 entries there is no badge, and a quarter with nothing is left out', function () {
    [$danny] = rewardsTeacher('Daniel Rogers', 'danny@example.test');
    rewardsEntry($danny, 'Aaron Gillespie');

    expect(app(TeacherRewards::class)->forContact($danny))->toBe([]);
});

test('the dashboard sends the rewards, and the teacher downloads their certificate', function () {
    [, $user] = rewardsScenario();

    $this->actingAs($user)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->has('rewards', 1)->where('rewards.0.badge', 'Bronze'));

    $this->actingAs($user)->get('/dashboard/rewards/2026/2/certificate')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="Maria_Nielsen_Q2_2026_Bronze_Appreciation_Certificate.pdf"');
});

test('a quarter with no badge, or a user with no contact, is a 404', function () {
    [, $user] = rewardsScenario();
    $stranger = User::factory()->create(['email' => 'nobody@example.test', 'role' => 'teacher']);

    $this->actingAs($user)->get('/dashboard/rewards/2026/1/certificate')->assertNotFound();
    $this->actingAs($stranger)->get('/dashboard/rewards/2026/2/certificate')->assertNotFound();
});

test('the admin preview downloads the previewed teacher\'s certificate', function () {
    [$maria, , $admin] = rewardsScenario();

    $this->actingAs($admin)->get("/admin/contacts/{$maria->id}/rewards/2026/2/certificate")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('the teacher badge rule is asked in one place', function () {
    expect(guardOffenders('/CertificateRenderer::teacherTier\(/', ['app/Services/QuarterCertificateBatch.php']))->toBe([]);
});
