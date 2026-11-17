<?php

// tests/Feature/Admin/PaymentDocumentTest.php
//
// Payment advices and receipts (/admin/payment-documents). What the edit page
// is served must be what is saved; the receipt balance wording follows the
// sign; numbering runs per kind and year; a receipt can fill its lines from a
// Trinity order.

use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\PaymentDocument;
use App\Models\User;
use App\Services\MinimumWage;
use App\Services\PaymentDocuments;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function govUkRatesPage(string $rows): string
{
    return '<html><body><h2>Current rates</h2><table><thead><tr><th></th><th>21 and over</th><th>18 to 20</th>'
        .'<th>Under 18</th><th>Apprentice</th></tr></thead><tbody>'.$rows.'</tbody></table></body></html>';
}

beforeEach(function () {
    $this->govUk = fn () => Http::response(govUkRatesPage(
        '<tr><th>April 2026</th><td>£12.71</td><td>£10.85</td><td>£8</td><td>£8</td></tr>'
        .'<tr><th>April 2027</th><td>£13.21</td><td>£11.27</td><td>£8.31</td><td>£8.31</td></tr>'
    ));

    Http::fake(['www.gov.uk/*' => fn () => ($this->govUk)()]);
});

function paymentDocAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function stewardPayload(array $overrides = []): array
{
    return array_merge([
        'kind' => 'payment',
        'number' => 'PAY-2026-001',
        'issued_on' => '2026-07-11',
        'title' => 'Exam steward',
        'to_name' => 'Laura',
        'to_detail' => 'Learn Music Ltd',
        'order_id' => null,
        'method' => 'Cash',
        'paid_on' => '2026-07-11',
        'amount_received' => null,
        'reference' => 'Steward July 26',
        'notes' => 'Thank you',
        'lines' => [
            ['description' => 'Steward exams LM', 'line_date' => '2026-07-11', 'detail' => '8:30 / 5:30', 'quantity' => '9', 'unit_price' => '12.71'],
        ],
    ], $overrides);
}

test('guests and teachers cannot reach payment documents', function () {
    $this->get('/admin/payment-documents')->assertRedirect('/login');

    $this->actingAs(User::factory()->create(['role' => 'teacher']))
        ->get('/admin/payment-documents')
        ->assertStatus(403);
});

test('a payment advice round-trips every field it is served', function () {
    $this->actingAs(paymentDocAdmin())
        ->post('/admin/payment-documents', stewardPayload())
        ->assertRedirect();

    $doc = PaymentDocument::firstOrFail();

    $this->get("/admin/payment-documents/{$doc->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->component('admin/PaymentDocuments/Edit')
            ->where('document.number', 'PAY-2026-001')
            ->where('document.issued_on', '2026-07-11')
            ->where('document.title', 'Exam steward')
            ->where('document.to_name', 'Laura')
            ->where('document.to_detail', 'Learn Music Ltd')
            ->where('document.method', 'Cash')
            ->where('document.paid_on', '2026-07-11')
            ->where('document.reference', 'Steward July 26')
            ->where('document.notes', 'Thank you')
            ->where('document.lines.0.description', 'Steward exams LM')
            ->where('document.lines.0.line_date', '2026-07-11')
            ->where('document.lines.0.detail', '8:30 / 5:30')
            ->where('document.lines.0.quantity', '9.00')
            ->where('document.lines.0.unit_price', '12.71')
            ->where('document.totals.total', '£114.39')
            ->where('document.totals.balance', null)
        );

    $this->put("/admin/payment-documents/{$doc->id}", stewardPayload([
        'title' => 'Venue rent',
        'lines' => [
            ['description' => 'Rent 9 July', 'line_date' => '2026-07-09', 'detail' => '', 'quantity' => '1', 'unit_price' => '100'],
            ['description' => 'Rent 11 July', 'line_date' => '2026-07-11', 'detail' => '', 'quantity' => '1', 'unit_price' => '100'],
        ],
    ]))->assertRedirect();

    $doc->refresh()->load('lines');
    expect($doc->title)->toBe('Venue rent')
        ->and($doc->lines)->toHaveCount(2)
        ->and($doc->lines[1]->description)->toBe('Rent 11 July')
        ->and(app(PaymentDocuments::class)->totals($doc)['total'])->toBe(20000);
});

test('a payment advice never stores an amount received', function () {
    $this->actingAs(paymentDocAdmin())
        ->post('/admin/payment-documents', stewardPayload(['amount_received' => '500']));

    expect(PaymentDocument::firstOrFail()->amount_received)->toBeNull();
});

test('a receipt balance takes its wording from the sign', function () {
    $service = app(PaymentDocuments::class);
    $lines = [
        ['description' => 'Jacob Hall – Acoustic Guitar Grade 3', 'quantity' => '1', 'unit_price' => '76'],
        ['description' => 'Cyrus Lau – Acoustic Guitar Grade 5', 'quantity' => '1', 'unit_price' => '99'],
        ['description' => 'Imogen Mayes – Classical Guitar Grade 1', 'quantity' => '1', 'unit_price' => '61'],
        ['description' => 'Ruby Taylor – Classical Guitar Grade 5', 'quantity' => '1', 'unit_price' => '57.50'],
    ];

    $overpaid = $service->save(stewardPayload(['kind' => 'receipt', 'number' => 'REC-2026-001', 'amount_received' => '312', 'lines' => $lines]));
    $totals = $service->totals($overpaid);
    expect($totals)->toBe(['total' => 29350, 'received' => 31200, 'balance' => 1850])
        ->and($service->balanceLabel($totals['balance']))->toBe('Balance to be refunded to you');

    $short = $service->save(stewardPayload(['kind' => 'receipt', 'number' => 'REC-2026-002', 'amount_received' => '280', 'lines' => $lines]));
    expect($service->totals($short)['balance'])->toBe(-1350)
        ->and($service->balanceLabel(-1350))->toBe('Still to pay')
        ->and(PaymentDocuments::money(-1350))->toBe('£13.50');

    $exact = $service->save(stewardPayload(['kind' => 'receipt', 'number' => 'REC-2026-003', 'amount_received' => '293.50', 'lines' => $lines]));
    expect($service->balanceLabel($service->totals($exact)['balance']))->toBe('Paid in full');
});

test('numbers run per kind and year and must be unique', function () {
    $service = app(PaymentDocuments::class);
    $date = Carbon::parse('2026-11-17');

    expect($service->nextNumber('payment', $date))->toBe('PAY-2026-001');
    $service->save(stewardPayload());
    expect($service->nextNumber('payment', $date))->toBe('PAY-2026-002')
        ->and($service->nextNumber('receipt', $date))->toBe('REC-2026-001')
        ->and($service->nextNumber('payment', Carbon::parse('2027-01-02')))->toBe('PAY-2027-001');

    $this->actingAs(paymentDocAdmin())
        ->post('/admin/payment-documents', stewardPayload())
        ->assertSessionHasErrors('number');
});

test('a receipt can fill its lines from a Trinity order', function () {
    $order = Order::factory()->create();
    $guitar = Instrument::create(['name' => 'Acoustic Guitar']);
    ExamEntry::create(['order_id' => $order->id, 'candidate_name' => 'Jacob Hall', 'instrument_id' => $guitar->id, 'grade' => 'Grade 3', 'fee' => 76, 'exam_date' => '2026-12-03']);
    ExamEntry::create(['order_id' => $order->id, 'candidate_name' => 'Cyrus Lau', 'instrument_id' => $guitar->id, 'grade' => '5', 'fee' => 99]);

    $this->actingAs(paymentDocAdmin())
        ->getJson("/admin/payment-documents/order-lines/{$order->id}")
        ->assertOk()
        ->assertJsonPath('lines.0.description', 'Jacob Hall – Acoustic Guitar Grade 3')
        ->assertJsonPath('lines.0.unit_price', '76.00')
        ->assertJsonPath('lines.0.line_date', '2026-12-03')
        ->assertJsonPath('lines.1.description', 'Cyrus Lau – Acoustic Guitar Grade 5');
});

test('the PDF opens', function () {
    $doc = app(PaymentDocuments::class)->save(stewardPayload());

    $this->actingAs(paymentDocAdmin())
        ->get("/admin/payment-documents/{$doc->id}/pdf")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('the index lists documents with their totals', function () {
    app(PaymentDocuments::class)->save(stewardPayload());

    $this->actingAs(paymentDocAdmin())
        ->get('/admin/payment-documents')
        ->assertInertia(fn ($page) => $page
            ->component('admin/PaymentDocuments/Index')
            ->where('documents.0.number', 'PAY-2026-001')
            ->where('documents.0.total', '£114.39')
        );
});

test('payment documents are numbered and totalled only by the service', function () {
    expect(guardOffenders('/PaymentDocument(Line)?::(create|query|where)\(|->lines\(\)->create\(/', [
        'app/Services/PaymentDocuments.php',
    ]))->toBe([]);
});

test('a line keeps its start and end times', function () {
    $this->actingAs(paymentDocAdmin())
        ->post('/admin/payment-documents', stewardPayload(['lines' => [
            ['description' => 'Exam steward C&J', 'line_date' => '2026-12-03', 'start_time' => '08:30', 'end_time' => '15:00',
                'detail' => 'Lunch on site', 'quantity' => '6.50', 'unit_price' => '12.71'],
        ]]))
        ->assertRedirect();

    $doc = PaymentDocument::firstOrFail();

    $this->get("/admin/payment-documents/{$doc->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->where('document.lines.0.start_time', '08:30')
            ->where('document.lines.0.end_time', '15:00')
            ->where('document.lines.0.detail', 'Lunch on site')
            ->where('document.lines.0.quantity', '6.50')
            ->where('document.totals.total', '£82.62')
        );

    $this->get("/admin/payment-documents/{$doc->id}/pdf")->assertOk();
});

test('an end time before the start time is refused', function () {
    $this->actingAs(paymentDocAdmin())
        ->post('/admin/payment-documents', stewardPayload(['lines' => [
            ['description' => 'Steward', 'line_date' => '2026-12-03', 'start_time' => '17:00', 'end_time' => '08:30',
                'detail' => '', 'quantity' => '1', 'unit_price' => '12.71'],
        ]]))
        ->assertSessionHasErrors('lines.0.end_time');

    expect(PaymentDocument::count())->toBe(0);
});

test('the edit page is handed the minimum wage read from GOV.UK', function () {
    $this->actingAs(paymentDocAdmin())
        ->get('/admin/payment-documents/create')
        ->assertInertia(fn ($page) => $page
            ->where('minimumWages', [
                ['from' => '2024-04-01', 'rate' => '11.44'],
                ['from' => '2025-04-01', 'rate' => '12.21'],
                ['from' => '2026-04-01', 'rate' => '12.71'],
                ['from' => '2027-04-01', 'rate' => '13.21'],
            ])
        );

    Http::assertSent(fn ($request) => $request->url() === MinimumWage::SOURCE);
});

test('a new rate on GOV.UK is picked up and the history is kept', function () {
    $this->govUk = fn () => Http::response(govUkRatesPage(
        '<tr><th>April 2027</th><td>£13.21</td><td>£11.27</td><td>£8.31</td><td>£8.31</td></tr>'
        .'<tr><th>April 2028</th><td>£13.70</td><td>£12.00</td><td>£8.60</td><td>£8.60</td></tr>'
    ));

    $rates = app(MinimumWage::class)->rates();

    expect(collect($rates)->pluck('rate', 'from')->all())->toMatchArray([
        '2026-04-01' => '12.71',
        '2027-04-01' => '13.21',
        '2028-04-01' => '13.70',
    ]);
});

test('if GOV.UK cannot be read the last rates are still used', function () {
    $this->govUk = fn () => Http::response('Service unavailable', 503);

    expect(app(MinimumWage::class)->refresh())->toBe(0)
        ->and(collect(app(MinimumWage::class)->rates())->pluck('rate', 'from')->get('2026-04-01'))->toBe('12.71');

    $this->govUk = fn () => Http::response('<html><body><p>A page with no table</p></body></html>');

    expect(app(MinimumWage::class)->refresh())->toBe(0)
        ->and(DB::table('minimum_wage_rates')->count())->toBe(4);
});

test('hours worked and the minimum wage on a line are worked out correctly', function () {
    $process = new Process(
        ['node', '--import', './tests/js/register-alias.mjs', '--test', 'tests/js/paymentDocumentLines.test.ts'],
        base_path(),
    );
    $process->setTimeout(120);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});

test('minimum wage figures are never written into the code', function () {
    expect(guardOffenders('/11\\.44|12\\.21|12\\.71|13\\.21/', []))->toBe([])
        ->and(guardOffenders('/national-minimum-wage-rates/', ['app/Services/MinimumWage.php']))->toBe([]);
});

test('hours and the minimum wage for a date are worked out in one place each', function () {
    expect(guardOffenders('/function hoursBetween\\b/', ['resources/js/lib/workTimes.ts']))->toBe([])
        ->and(guardOffenders('/function minimumWageOn\\b/', ['resources/js/lib/minimumWage.ts']))->toBe([]);
});

test('What for offers the defaults, then every type already used on a document or a line, and a new type joins the list once saved', function () {
    $admin = paymentDocAdmin();

    $this->actingAs($admin)
        ->get('/admin/payment-documents/create?kind=payment')
        ->assertInertia(fn ($page) => $page->where('types', ['Exam steward', 'Venue rent']));

    $this->post('/admin/payment-documents', stewardPayload(['title' => 'Piano tuning']))->assertRedirect();
    $this->post('/admin/payment-documents', stewardPayload(['number' => 'PAY-2026-002', 'title' => 'exam steward']))->assertRedirect();

    $this->get('/admin/payment-documents/create?kind=payment')
        ->assertInertia(fn ($page) => $page->where('types', ['Exam steward', 'Venue rent', 'Piano tuning', 'Steward exams LM']));

    $this->get('/admin/payment-documents/create?kind=receipt')
        ->assertInertia(fn ($page) => $page->where('types', ['Exam fees']));
});

test('the What for dropdown is built once and shared by the document and its lines', function () {
    expect(guardOffenders('/__new_type__/', ['resources/js/components/paymentdocuments/WhatForPicker.vue']))->toBe([]);
});
