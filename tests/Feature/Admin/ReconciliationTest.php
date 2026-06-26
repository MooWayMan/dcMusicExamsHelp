<?php

// tests/Feature/Admin/ReconciliationTest.php

use App\Models\ImportRun;
use App\Models\Order;
use App\Models\User;
use App\Services\RemittanceParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Captured Trinity "Remittance Advice" layouts — used for the pure parser
// tests (no PDF library needed). The real PDFs these came from live in
// tests/fixtures/remittances and drive the integration + controller tests.

const REMIT_DIGITAL_TEXT = <<<'TXT'
REMITTANCE ADVICE
Remittance Date 23 June 2026
Page 1/1
Your Account code with us: 71-120
We have emailed this advice to: musicexams@musicexams.help
Date Reference Description MyTrinity Reference Currency Transaction GBP Amount
31 May 2026 CEB012013 DGD 1-17510214884 GBP 9.80 9.80
31 May 2026 CEB012013 DGD 1-17170186444 GBP 9.80 9.80
31 May 2026 CEB012013 DGD 1-16786761424 GBP 15.60 15.60
31 May 2026 CEB012013 DGD 1-17350160874 GBP 13.60 13.60
31 May 2026 CEB012013 DGD 1-17509873774 GBP 9.80 9.80
Total Amount (GBP): 58.60
TXT;

const REMIT_MIXED_TEXT = <<<'TXT'
REMITTANCE ADVICE
Remittance Date 2 April 2026
Page 1/1
Your Account code with us: 71-120
We have emailed this advice to: madmusic6@hotmail.com
Date Reference Description MyTrinity Reference Currency Transaction GBP Amount
28 February 2026 CEB012010 DGD 1-14163844479 GBP 15.60 15.60
28 February 2026 CEB012010 DGD 1-13750176989 GBP 39.60 39.60
28 February 2026 CEB012010 DGD 1-14243820189 GBP 13.60 13.60
28 February 2026 CEB012010 DGD 1-14090535219 GBP 17.60 17.60
28 February 2026 CEB012010 DGD 1-13748006149 GBP 12.20 12.20
28 February 2026 CEB012010 DGD 1-13478401579 GBP 13.60 13.60
6 December 2025 1-6053029943 CET000447 Liverpool GBP 124.25 124.25
Total Amount (GBP): 236.45
TXT;

function digitalPdf(): UploadedFile
{
    return new UploadedFile(
        base_path('tests/fixtures/remittances/remittance-2026-06-23-digital.pdf'),
        'remittance-2026-06-23-digital.pdf',
        'application/pdf',
        null,
        true, // test mode — skip is_uploaded_file()
    );
}

function mixedPdf(): UploadedFile
{
    return new UploadedFile(
        base_path('tests/fixtures/remittances/remittance-2026-04-02-mixed.pdf'),
        'remittance-2026-04-02-mixed.pdf',
        'application/pdf',
        null,
        true,
    );
}

// ──────────────────────────────────────────
// Parser — pure text (no PDF dependency)
// ──────────────────────────────────────────

test('parses a digital-only remittance', function () {
    $parsed = (new RemittanceParser())->parseText(REMIT_DIGITAL_TEXT);

    expect($parsed['remittance_date'])->toBe('2026-06-23');
    expect($parsed['account_code'])->toBe('71-120');
    expect($parsed['total'])->toBe(58.60);
    expect($parsed['rows'])->toHaveCount(5);

    expect($parsed['rows'][0]['order_number'])->toBe('1-17510214884');
    expect($parsed['rows'][0]['gbp_amount'])->toBe(9.80);
    expect($parsed['rows'][0]['transaction_date'])->toBe('2026-05-31');
    expect($parsed['rows'][2]['gbp_amount'])->toBe(15.60);
});

test('parses a mixed digital + face-to-face remittance', function () {
    $parsed = (new RemittanceParser())->parseText(REMIT_MIXED_TEXT);

    expect($parsed['remittance_date'])->toBe('2026-04-02');
    expect($parsed['total'])->toBe(236.45);
    expect($parsed['rows'])->toHaveCount(7);

    // The face-to-face row puts the order number in a different column and
    // carries a venue ("Liverpool") + its own earlier transaction date.
    $f2f = collect($parsed['rows'])->firstWhere('order_number', '1-6053029943');
    expect($f2f)->not->toBeNull();
    expect($f2f['gbp_amount'])->toBe(124.25);
    expect($f2f['transaction_date'])->toBe('2025-12-06');
    expect($f2f['description'])->toContain('Liverpool');
});

test('parsed amounts sum to the stated total', function () {
    $parsed = (new RemittanceParser())->parseText(REMIT_MIXED_TEXT);
    $sum = array_sum(array_column($parsed['rows'], 'gbp_amount'));
    expect(round($sum, 2))->toBe($parsed['total']);
});

test('ignores the account code line and does not treat it as a transaction', function () {
    // "71-120" must not be mistaken for an order number.
    $parsed = (new RemittanceParser())->parseText(REMIT_DIGITAL_TEXT);
    expect(collect($parsed['rows'])->pluck('order_number'))->not->toContain('1-120');
});

// ──────────────────────────────────────────
// Parser — real PDF files (smalot/pdfparser)
// ──────────────────────────────────────────

test('parses the real digital remittance PDF', function () {
    $parsed = (new RemittanceParser())->parseFile(
        base_path('tests/fixtures/remittances/remittance-2026-06-23-digital.pdf')
    );

    expect($parsed['remittance_date'])->toBe('2026-06-23');
    expect($parsed['rows'])->toHaveCount(5);
    expect(collect($parsed['rows'])->pluck('order_number'))->toContain('1-17510214884');

    $row = collect($parsed['rows'])->firstWhere('order_number', '1-17510214884');
    expect($row['gbp_amount'])->toBe(9.80);
});

test('parses the real mixed remittance PDF including the face-to-face row', function () {
    $parsed = (new RemittanceParser())->parseFile(
        base_path('tests/fixtures/remittances/remittance-2026-04-02-mixed.pdf')
    );

    expect($parsed['remittance_date'])->toBe('2026-04-02');
    expect($parsed['rows'])->toHaveCount(7);
    expect(collect($parsed['rows'])->pluck('order_number'))->toContain('1-6053029943');
});

// ──────────────────────────────────────────
// Controller — preview
// ──────────────────────────────────────────

test('preview matches rows against orders with the right statuses', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Order::factory()->create(['trinity_order_number' => '1-14163844479', 'commission_amount' => 15.60]);            // matched
    Order::factory()->create(['trinity_order_number' => '1-13750176989', 'commission_amount' => 10.00]);            // mismatch (paid 39.60)
    Order::factory()->create(['trinity_order_number' => '1-14243820189', 'commission_amount' => 13.60, 'commission_paid_at' => '2026-03-01']); // already paid
    // remaining 4 order numbers absent → not_found

    $this->actingAs($admin)
        ->post('/admin/reconciliation/preview', ['file' => mixedPdf()])
        ->assertOk()
        ->assertJsonPath('remittance_date', '2026-04-02')
        ->assertJsonPath('counts.matched', 1)
        ->assertJsonPath('counts.mismatch', 1)
        ->assertJsonPath('counts.already_paid', 1)
        ->assertJsonPath('counts.not_found', 4)
        ->assertJsonPath('can_commit', true);
});

test('preview rejects a non-remittance PDF gracefully', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // A valid PDF with no Trinity rows: reuse dompdf-free minimal PDF bytes.
    $bytes = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
    $path = sys_get_temp_dir() . '/blank-' . uniqid() . '.pdf';
    file_put_contents($path, $bytes);
    $file = new UploadedFile($path, 'blank.pdf', 'application/pdf', null, true);

    $this->actingAs($admin)
        ->post('/admin/reconciliation/preview', ['file' => $file])
        ->assertStatus(422);
});

// ──────────────────────────────────────────
// Controller — commit
// ──────────────────────────────────────────

test('commit marks matched orders paid on the remittance date', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    $matched = Order::factory()->create(['trinity_order_number' => '1-14163844479', 'commission_amount' => 15.60]);
    $mismatch = Order::factory()->create(['trinity_order_number' => '1-13750176989', 'commission_amount' => 10.00]);
    $already = Order::factory()->create(['trinity_order_number' => '1-14243820189', 'commission_amount' => 13.60, 'commission_paid_at' => '2026-03-01', 'commission_paid_amount' => 13.60]);

    $this->actingAs($admin)
        ->post('/admin/reconciliation/commit', ['file' => mixedPdf()])
        ->assertRedirect(route('admin.reconciliation.index'))
        ->assertSessionHas('success');

    // Matched order paid with the GBP amount, on the remittance date.
    $matched->refresh();
    expect($matched->commission_paid_at->toDateString())->toBe('2026-04-02');
    expect((float) $matched->commission_paid_amount)->toBe(15.60);

    // Mismatch is still payable — gets marked at what Trinity actually paid.
    $mismatch->refresh();
    expect($mismatch->commission_paid_at->toDateString())->toBe('2026-04-02');
    expect((float) $mismatch->commission_paid_amount)->toBe(39.60);

    // Already-paid order is left untouched (its original date stands).
    $already->refresh();
    expect($already->commission_paid_at->toDateString())->toBe('2026-03-01');
});

test('commit logs an ImportRun and stores the PDF', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    Order::factory()->create(['trinity_order_number' => '1-17510214884', 'commission_amount' => 9.80]);

    $this->actingAs($admin)
        ->post('/admin/reconciliation/commit', ['file' => digitalPdf()]);

    $run = ImportRun::where('type', 'remittance')->first();
    expect($run)->not->toBeNull();
    expect($run->summary['marked'])->toBe(1);
    expect($run->summary['not_found'])->toBe(4);
    expect($run->summary['remittance_date'])->toBe('2026-06-23');

    Storage::disk('local')->assertExists($run->summary['stored_path']);
});

test('commit sets commission_amount from the paid amount when the order has none', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    // Imported without a commission figure (the "Expected —" case).
    $blank = Order::factory()->create(['trinity_order_number' => '1-17510214884', 'commission_amount' => null]);
    // Already has an expected commission — must NOT be overwritten.
    $hasExpected = Order::factory()->create(['trinity_order_number' => '1-17170186444', 'commission_amount' => 5.00]);

    $this->actingAs($admin)
        ->post('/admin/reconciliation/commit', ['file' => digitalPdf()]);

    // Both digital rows are £9.80.
    expect((float) $blank->fresh()->commission_amount)->toBe(9.80);
    expect((float) $hasExpected->fresh()->commission_amount)->toBe(5.00);
    expect((float) $hasExpected->fresh()->commission_paid_amount)->toBe(9.80);
});

test('reconciliation pages require an admin', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)->get('/admin/reconciliation')->assertForbidden();
    $this->actingAs($teacher)->post('/admin/reconciliation/preview', ['file' => digitalPdf()])->assertForbidden();
});
