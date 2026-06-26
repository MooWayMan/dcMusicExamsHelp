<?php

// tests/Feature/Admin/EnrolmentListImportTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\User;
use App\Services\TrinityCsvImporter;
use Illuminate\Http\UploadedFile;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const ENROL_ORDER = '1-17521428644';

function enrolFixture(): UploadedFile
{
    return new UploadedFile(
        base_path('tests/fixtures/enrolment/enrolment-list-sample.tsv'),
        'enrolment-list-sample.tsv',
        'text/tab-separated-values',
        null,
        true, // test mode
    );
}

function enrolContents(): string
{
    return file_get_contents(base_path('tests/fixtures/enrolment/enrolment-list-sample.tsv'));
}

function makeEnrolOrder(array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'trinity_order_number' => ENROL_ORDER,
        'delivery_method' => 'digital',
        'commission_rate' => 20,
        'commission_amount' => null,
        'created_by_contact_id' => null,
    ], $overrides));
}

// ──────────────────────────────────────────
// Parser
// ──────────────────────────────────────────

test('parseEnrolmentList returns every candidate and the submitter', function () {
    $rows = (new TrinityCsvImporter())->parseEnrolmentList(enrolContents());

    expect($rows)->toHaveCount(14);
    expect(collect($rows)->pluck('candidate_name'))->toContain('Alice Tester');
    expect($rows[0]['submitter_name'])->toBe('Alex Teacher');
    expect($rows[0]['submitter_email'])->toBe('teacher@example.com');
});

// ──────────────────────────────────────────
// Preview
// ──────────────────────────────────────────

test('preview matches the order and estimates commission from the fees', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    makeEnrolOrder();

    $res = $this->actingAs($admin)
        ->post('/admin/imports/preview-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => ENROL_ORDER,
        ])
        ->assertOk()
        ->assertJsonPath('order.trinity_order_number', ENROL_ORDER)
        ->assertJsonPath('totals.rows', 14)
        ->assertJsonPath('totals.to_create', 14);

    expect((float) $res->json('totals.total_fees'))->toBe(1009.00);
    expect((float) $res->json('totals.commission_estimate'))->toBe(201.80);
});

test('preview flags an unknown order without throwing', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/admin/imports/preview-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => '1-DOES-NOT-EXIST',
        ])
        ->assertOk()
        ->assertJsonPath('order', null);
});

// ──────────────────────────────────────────
// Commit
// ──────────────────────────────────────────

test('commit creates the candidate entries and sets commission, without tagging a teacher', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeEnrolOrder();

    $this->actingAs($admin)
        ->post('/admin/imports/commit-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => ENROL_ORDER,
        ])
        ->assertRedirect(route('admin.imports.index'))
        ->assertSessionHas('success');

    // 14 entries, all results blank (awaiting the triple).
    $entries = ExamEntry::where('order_id', $order->id)->get();
    expect($entries)->toHaveCount(14);
    expect($entries->whereNull('score'))->toHaveCount(14);
    expect($entries->whereNotNull('teacher_contact_id'))->toHaveCount(0);
    expect($entries->first()->source)->toBe('trinity_enrolment_list');

    // Submitter contact created and linked to the order — but NOT a teacher.
    $submitter = ExamContact::whereRaw('LOWER(email) = ?', ['teacher@example.com'])->first();
    expect($submitter)->not->toBeNull();
    expect($submitter->hasType('teacher'))->toBeFalse();
    expect($order->fresh()->created_by_contact_id)->toBe($submitter->id);

    // Commission set from fees × 20%.
    expect((float) $order->fresh()->commission_amount)->toBe(201.80);
});

test('commit is idempotent — running twice does not duplicate entries', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeEnrolOrder();

    foreach (range(1, 2) as $_) {
        $this->actingAs($admin)->post('/admin/imports/commit-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => ENROL_ORDER,
        ]);
    }

    expect(ExamEntry::where('order_id', $order->id)->count())->toBe(14);
});

test('commit never clobbers a result already imported by the triple', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeEnrolOrder();

    // Simulate the triple having already landed for one candidate.
    $existing = ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-17521423974', // first candidate
        'candidate_name' => 'Alice Tester',
        'score' => 80,
        'result' => 'Merit',
        'source' => 'trinity_csv_import',
    ]);

    $this->actingAs($admin)->post('/admin/imports/commit-enrolment-list', [
        'file' => enrolFixture(),
        'order_number' => ENROL_ORDER,
    ]);

    $existing->refresh();
    expect($existing->score)->toBe(80);
    expect($existing->result)->toBe('Merit');
    expect(ExamEntry::where('order_id', $order->id)->count())->toBe(14);
});

test('commit fails cleanly when the order does not exist', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/admin/imports/commit-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => '1-DOES-NOT-EXIST',
        ])
        ->assertSessionHasErrors('file');

    expect(ExamEntry::count())->toBe(0);
});

test('enrolment import requires an admin', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/admin/imports/preview-enrolment-list', [
            'file' => enrolFixture(),
            'order_number' => ENROL_ORDER,
        ])
        ->assertForbidden();
});
