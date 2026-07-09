<?php

// tests/Feature/Admin/ContactMergeTest.php

use App\Models\ContactMergeDismissal;
use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\Student;
use App\Models\User;
use App\Services\ContactMergeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function mergeAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function contact(string $name, ?string $email = null): ExamContact
{
    return ExamContact::create(['name' => $name, 'email' => $email]);
}

function entryFor(Order $order, array $attrs = []): ExamEntry
{
    return ExamEntry::create(array_merge([
        'order_id' => $order->id,
        'candidate_name' => 'Test Candidate',
        'grade' => 'Grade 1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => Carbon::create(2026, 4, 15),
    ], $attrs));
}

// ──────────────────────────────────────────
// Detection
// ──────────────────────────────────────────

test('fuzzy detection flags a same-name contact', function () {
    $a = contact('Maria Nielsen', 'maria.kn.music@gmail.com');
    contact('Maria Nielsen', 'mkn21@me.com');
    contact('David Keeling', 'david@example.test');

    $dupes = app(ContactMergeService::class)->possibleDuplicatesFor($a);

    expect($dupes)->toHaveCount(1)
        ->and($dupes->first()['contact']->name)->toBe('Maria Nielsen')
        ->and($dupes->first()['score'])->toBe(100);
});

test('a dismissed pair is never flagged again', function () {
    $a = contact('Maria Nielsen', 'maria.kn.music@gmail.com');
    $b = contact('Maria Nielsen', 'mkn21@me.com');

    ContactMergeDismissal::dismiss($a->id, $b->id);

    expect(app(ContactMergeService::class)->possibleDuplicatesFor($a))->toHaveCount(0);
});

test('dismissal is order-independent', function () {
    $a = contact('Sam Jones');
    $b = contact('Sam Jones');

    ContactMergeDismissal::dismiss($b->id, $a->id);

    expect(ContactMergeDismissal::isDismissed($a->id, $b->id))->toBeTrue();
});

// ──────────────────────────────────────────
// Merge engine
// ──────────────────────────────────────────

test('merge repoints every reference and retires the loser', function () {
    $keep = contact('Maria Nielsen', 'maria.kn.music@gmail.com');
    $keep->addType('teacher');
    $drop = contact('Maria Nielsen', 'mkn21@me.com');
    $drop->addType('parent');

    $order = Order::factory()->create(['created_by_contact_id' => $drop->id]);
    DB::table('order_contacts')->insert([
        'order_id' => $order->id, 'exam_contact_id' => $drop->id,
        'role_in_order' => 'applicant', 'is_primary' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $taught = entryFor($order, ['teacher_contact_id' => $drop->id]);
    $submitted = entryFor($order, ['submitter_contact_id' => $drop->id]);
    $student = Student::factory()->create(['teacher_contact_id' => $drop->id]);

    app(ContactMergeService::class)->merge($keep, $drop);

    expect($taught->fresh()->teacher_contact_id)->toBe($keep->id)
        ->and($submitted->fresh()->submitter_contact_id)->toBe($keep->id)
        ->and($student->fresh()->teacher_contact_id)->toBe($keep->id)
        ->and($order->fresh()->created_by_contact_id)->toBe($keep->id);

    // Pivot repointed.
    expect(DB::table('order_contacts')->where('exam_contact_id', $keep->id)->count())->toBe(1)
        ->and(DB::table('order_contacts')->where('exam_contact_id', $drop->id)->count())->toBe(0);

    // Types unioned.
    $keepTypes = DB::table('contact_types')->where('exam_contact_id', $keep->id)->pluck('type')->all();
    expect($keepTypes)->toContain('teacher')->toContain('parent');

    // Drop's email folded in as a secondary; gmail stays primary.
    $keepEmails = DB::table('contact_emails')->where('exam_contact_id', $keep->id)->pluck('email')->all();
    expect($keepEmails)->toContain('mkn21@me.com');

    // Loser soft-deleted.
    expect(ExamContact::find($drop->id))->toBeNull()
        ->and(ExamContact::withTrashed()->find($drop->id))->not->toBeNull();
});

test('merge dedupes a pivot row both contacts already share', function () {
    $keep = contact('A Teacher');
    $drop = contact('A Teacher');
    $order = Order::factory()->create();

    foreach ([$keep, $drop] as $c) {
        DB::table('order_contacts')->insert([
            'order_id' => $order->id, 'exam_contact_id' => $c->id,
            'role_in_order' => 'teacher', 'is_primary' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    app(ContactMergeService::class)->merge($keep, $drop);

    expect(DB::table('order_contacts')
        ->where('order_id', $order->id)
        ->where('role_in_order', 'teacher')
        ->count())->toBe(1);
});

test('merging a contact into itself is rejected', function () {
    $c = contact('Solo');

    expect(fn () => app(ContactMergeService::class)->merge($c, $c))
        ->toThrow(InvalidArgumentException::class);
});

// ──────────────────────────────────────────
// Endpoints
// ──────────────────────────────────────────

test('admin can merge one contact into another via the endpoint', function () {
    $keep = contact('Emily Bates', 'emily@example.test');
    $drop = contact('Emily French', 'emily.f@example.test');

    $this->actingAs(mergeAdmin())
        ->post("/admin/contacts/{$keep->id}/merge", ['drop_id' => $drop->id])
        ->assertRedirect("/admin/contacts/{$keep->id}");

    expect(ExamContact::find($drop->id))->toBeNull();
});

test('merge endpoint rejects merging a contact into itself', function () {
    $c = contact('Solo');

    $this->actingAs(mergeAdmin())
        ->post("/admin/contacts/{$c->id}/merge", ['drop_id' => $c->id])
        ->assertSessionHasErrors('drop_id');
});

test('dismiss endpoint records the pair and stops it being flagged', function () {
    $keep = contact('Maria Nielsen');
    $other = contact('Maria Nielsen');

    $this->actingAs(mergeAdmin())
        ->post("/admin/contacts/{$keep->id}/dismiss-duplicate", ['other_id' => $other->id])
        ->assertRedirect("/admin/contacts/{$keep->id}");

    expect(ContactMergeDismissal::isDismissed($keep->id, $other->id))->toBeTrue();
});

test('show payload includes possible duplicates', function () {
    $keep = contact('Maria Nielsen', 'a@example.test');
    contact('Maria Nielsen', 'b@example.test');

    $this->actingAs(mergeAdmin())
        ->get("/admin/contacts/{$keep->id}")
        ->assertInertia(fn ($page) => $page
            ->component('admin/Contacts/Show')
            ->has('possibleDuplicates', 1));
});

test('non-admin cannot merge contacts', function () {
    $keep = contact('X');
    $drop = contact('Y');

    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->post("/admin/contacts/{$keep->id}/merge", ['drop_id' => $drop->id])
        ->assertForbidden();

    expect(ExamContact::find($drop->id))->not->toBeNull();
});
