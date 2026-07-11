<?php

// tests/Feature/Admin/AddressLabelTest.php

use App\Models\User;
use App\Services\AddressLabelParser;
use Illuminate\Http\UploadedFile;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// The real Trinity 8-up label PDFs Paul is handed. These drive the
// integration test through smalot/pdfparser and the controller tests.
function labelPdf(string $name): UploadedFile
{
    return new UploadedFile(
        base_path("tests/fixtures/labels/{$name}"),
        $name,
        'application/pdf',
        null,
        true, // test mode — skip is_uploaded_file()
    );
}

/** Every line across every label, flattened — handy for "no junk" assertions. */
function allLines(array $labels): array
{
    return collect($labels)->flatMap(fn (array $l): array => $l['lines'])->all();
}

// ──────────────────────────────────────────────────────────────────
// Route guarding
// ──────────────────────────────────────────────────────────────────

test('GET /admin/labels returns 200 for an authenticated admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/labels')->assertOk();
});

test('GET /admin/labels is forbidden for a non-admin', function () {
    $user = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($user)->get('/admin/labels')->assertForbidden();
});

// ──────────────────────────────────────────────────────────────────
// Integration — the real PDFs through the real parser
// ──────────────────────────────────────────────────────────────────

test('parseFiles cleans and de-duplicates the four Trinity sample PDFs', function () {
    $dir = base_path('tests/fixtures/labels');
    $labels = (new AddressLabelParser())->parseFiles([
        'cj_LM.pdf' => "{$dir}/cj_LM.pdf",
        'rp_am.pdf' => "{$dir}/rp_am.pdf",
        'rp_pm.pdf' => "{$dir}/rp_pm.pdf",
        'wirral_address.pdf' => "{$dir}/wirral_address.pdf",
    ]);

    // A healthy number of distinct teachers (37 in the reference run — allow a
    // little slack for PDF-extraction granularity).
    expect(count($labels))->toBeGreaterThanOrEqual(34)->toBeLessThanOrEqual(40);

    // Junk is gone.
    expect(allLines($labels))->not->toContain('United Kingdom');

    // Known people survived, with their first line as the name.
    $names = collect($labels)->pluck('name')->all();
    expect($names)->toContain('Christopher Jones')->toContain('David Keeling')->toContain('Fiona Shore');

    // Jennifer KENT is in wirral_address.pdf twice — the exact dupe is merged.
    $kents = collect($labels)->filter(fn (array $l): bool => str_contains(strtolower($l['name']), 'jennifer kent'));
    expect($kents)->toHaveCount(1);

    // The CHRISTOPER JONES typo is kept but flagged as a possible duplicate.
    $flagged = collect($labels)->filter(fn (array $l): bool => $l['flag'] !== '');
    expect($flagged->count())->toBeGreaterThanOrEqual(1);
    expect(collect($labels)->firstWhere('name', 'CHRISTOPER JONES')['flag'])->not->toBe('');
});

// ──────────────────────────────────────────────────────────────────
// Controller — preview + pdf
// ──────────────────────────────────────────────────────────────────

test('preview returns cleaned labels as JSON', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $res = $this->actingAs($admin)->post('/admin/labels/preview', [
        'files' => [labelPdf('cj_LM.pdf'), labelPdf('rp_am.pdf')],
    ]);

    $res->assertOk()
        ->assertJsonStructure(['labels' => [['name', 'lines', 'postcode', 'source', 'flag']], 'count', 'flagged']);

    expect($res->json('count'))->toBeGreaterThan(10);
});

test('preview rejects a non-CSV spreadsheet with a helpful message', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $res = $this->actingAs($admin)->post('/admin/labels/preview', [
        'spreadsheet' => UploadedFile::fake()->create('addresses.xlsx', 5),
    ]);

    $res->assertStatus(422);
    expect($res->json('error'))->toContain('CSV');
});

test('pdf endpoint streams a PDF for the edited labels', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $res = $this->actingAs($admin)->post('/admin/labels/pdf', [
        'labels' => [
            ['Philip Goodwin', '19 Lyttelton Road', 'Liverpool', 'L17 0AS'],
            ['Fiona Shore', 'Near Howe', 'Troutbeck', 'CA11 0SH'],
        ],
    ]);

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($res->getContent(), 0, 4))->toBe('%PDF');
});

test('pdf endpoint rejects an empty label set', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/labels/pdf', ['labels' => []])
        ->assertStatus(422);
});
