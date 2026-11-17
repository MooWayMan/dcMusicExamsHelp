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
use App\Services\PaymentDocuments;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
