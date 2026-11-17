<?php

// app/Http/Controllers/Admin/PaymentDocumentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentDocument;
use App\Services\PaymentDocumentPdf;
use App\Services\PaymentDocuments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * /admin/payment-documents — payment advices (venue rent, stewards) and
 * receipts (exam fees paid to Paul). Rules live in App\Services\PaymentDocuments.
 */
class PaymentDocumentController extends Controller
{
    public function __construct(
        private PaymentDocuments $documents = new PaymentDocuments(),
        private PaymentDocumentPdf $pdf = new PaymentDocumentPdf(),
    ) {}

    public function index(): InertiaResponse
    {
        $rows = PaymentDocument::with('lines')
            ->orderByDesc('issued_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PaymentDocument $doc) => [
                'id' => $doc->id,
                'number' => $doc->number,
                'kind' => PaymentDocuments::KINDS[$doc->kind],
                'issued_on' => $doc->issued_on->format('d M Y'),
                'to_name' => $doc->to_name,
                'title' => $doc->title,
                'total' => PaymentDocuments::money($this->documents->totals($doc)['total']),
            ]);

        return Inertia::render('admin/PaymentDocuments/Index', ['documents' => $rows]);
    }

    public function create(Request $request): InertiaResponse
    {
        $kind = $request->query('kind') === PaymentDocument::KIND_RECEIPT
            ? PaymentDocument::KIND_RECEIPT
            : PaymentDocument::KIND_PAYMENT;

        return Inertia::render('admin/PaymentDocuments/Edit', [
            'document' => null,
            'kind' => $kind,
            'suggestedNumber' => $this->documents->nextNumber($kind, now()),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $document = $this->documents->save($data);

        return redirect()->route('admin.payment-documents.edit', $document)->with('success', 'Saved.');
    }

    public function edit(PaymentDocument $paymentDocument): InertiaResponse
    {
        return Inertia::render('admin/PaymentDocuments/Edit', [
            'document' => $this->documents->payload($paymentDocument),
            'kind' => $paymentDocument->kind,
            'suggestedNumber' => $paymentDocument->number,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, PaymentDocument $paymentDocument): RedirectResponse
    {
        $data = $this->validated($request, $paymentDocument);
        $this->documents->save($data, $paymentDocument);

        return redirect()->route('admin.payment-documents.edit', $paymentDocument)->with('success', 'Saved.');
    }

    public function destroy(PaymentDocument $paymentDocument): RedirectResponse
    {
        $paymentDocument->delete();

        return redirect()->route('admin.payment-documents.index')->with('success', 'Deleted.');
    }

    public function pdf(PaymentDocument $paymentDocument): Response
    {
        return response($this->pdf->render($paymentDocument), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->pdf->filename($paymentDocument).'"',
        ]);
    }

    public function orderLines(Order $order): JsonResponse
    {
        return response()->json(['lines' => $this->documents->linesFromOrder($order)]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'methods' => PaymentDocuments::METHODS,
            'orders' => Order::orderByDesc('requested_start_date')
                ->limit(100)
                ->get(['id', 'trinity_order_number', 'venue', 'requested_start_date', 'candidates'])
                ->map(fn (Order $order) => [
                    'id' => $order->id,
                    'label' => trim($order->trinity_order_number.' · '.($order->venue ?: 'Digital').' · '
                        .optional($order->requested_start_date)->format('d M Y').' · '.$order->candidates.' cand.'),
                ])
                ->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PaymentDocument $document): array
    {
        return $request->validate([
            'kind' => [$document ? 'nullable' : 'required', Rule::in(array_keys(PaymentDocuments::KINDS))],
            'number' => ['required', 'string', 'max:32', Rule::unique('payment_documents', 'number')->ignore($document?->id)],
            'issued_on' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'to_name' => ['required', 'string', 'max:255'],
            'to_detail' => ['nullable', 'string', 'max:1000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'method' => ['nullable', Rule::in(PaymentDocuments::METHODS)],
            'paid_on' => ['nullable', 'date'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.line_date' => ['nullable', 'date'],
            'lines.*.detail' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric'],
        ]);
    }
}
