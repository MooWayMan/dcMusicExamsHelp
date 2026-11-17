<?php

// app/Services/PaymentDocuments.php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentDocument;
use App\Support\Grade;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Payment advices and receipts. The only place that writes, numbers or totals
 * a payment document. Money is worked in pence so totals never drift.
 *
 * WHAT IS SERVED IS SAVED: payload() hands the edit page every field, and
 * save() writes every one of those fields back (lines are replaced as a set).
 */
class PaymentDocuments
{
    public const KINDS = [
        PaymentDocument::KIND_PAYMENT => 'Payment advice',
        PaymentDocument::KIND_RECEIPT => 'Receipt',
    ];

    private const PREFIX = [
        PaymentDocument::KIND_PAYMENT => 'PAY',
        PaymentDocument::KIND_RECEIPT => 'REC',
    ];

    public const METHODS = ['Bank transfer', 'Cash', 'Cheque', 'Card', 'Other'];

    /**
     * Create or update a document from validated input.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?PaymentDocument $document = null): PaymentDocument
    {
        return DB::transaction(function () use ($data, $document) {
            $document ??= new PaymentDocument(['kind' => $data['kind']]);

            $isReceipt = $document->kind === PaymentDocument::KIND_RECEIPT;

            $document->fill([
                'number' => $data['number'],
                'issued_on' => $data['issued_on'],
                'title' => $data['title'] ?? null,
                'to_name' => $data['to_name'],
                'to_detail' => $data['to_detail'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'method' => $data['method'] ?? null,
                'paid_on' => $data['paid_on'] ?? null,
                'amount_received' => $isReceipt ? ($data['amount_received'] ?? null) : null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $document->lines()->delete();
            foreach (array_values($data['lines'] ?? []) as $position => $line) {
                $document->lines()->create([
                    'position' => $position,
                    'description' => $line['description'],
                    'line_date' => $line['line_date'] ?? null,
                    'detail' => $line['detail'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            return $document->load('lines');
        });
    }

    /** The next free number for a kind and year, e.g. PAY-2026-003. */
    public function nextNumber(string $kind, CarbonInterface $date): string
    {
        $stem = self::PREFIX[$kind].'-'.$date->format('Y').'-';

        $highest = PaymentDocument::where('number', 'like', $stem.'%')
            ->pluck('number')
            ->map(fn (string $number) => (int) substr($number, strlen($stem)))
            ->max() ?? 0;

        return $stem.str_pad((string) ($highest + 1), 3, '0', STR_PAD_LEFT);
    }

    public function linePence(float|string $quantity, float|string $unitPrice): int
    {
        return (int) round((float) $quantity * (float) $unitPrice * 100);
    }

    /**
     * Totals in pence. For a receipt, balance = received − total: positive
     * means Paul owes the payer a refund, negative means the payer still owes.
     *
     * @return array{total: int, received: int|null, balance: int|null}
     */
    public function totals(PaymentDocument $document): array
    {
        $total = $document->lines->sum(fn ($line) => $this->linePence($line->quantity, $line->unit_price));

        if ($document->kind !== PaymentDocument::KIND_RECEIPT || $document->amount_received === null) {
            return ['total' => $total, 'received' => null, 'balance' => null];
        }

        $received = (int) round((float) $document->amount_received * 100);

        return ['total' => $total, 'received' => $received, 'balance' => $received - $total];
    }

    /**
     * The wording for a receipt balance comes from its sign, never a fixed
     * label over an absolute amount.
     */
    public function balanceLabel(int $balancePence): string
    {
        return match (true) {
            $balancePence > 0 => 'Balance to be refunded to you',
            $balancePence < 0 => 'Still to pay',
            default => 'Paid in full',
        };
    }

    public static function money(int $pence): string
    {
        return '£'.number_format(abs($pence) / 100, 2);
    }

    /**
     * One line per candidate on a Trinity order, at the fee stored on the
     * entry. Used to fill a receipt.
     *
     * @return list<array{description: string, line_date: string|null, detail: string|null, quantity: string, unit_price: string}>
     */
    public function linesFromOrder(Order $order): array
    {
        return $order->examEntries()
            ->with('instrument')
            ->orderBy('id')
            ->get()
            ->map(fn ($entry) => [
                'description' => trim(implode(' – ', array_filter([
                    $entry->candidate_name,
                    trim(($entry->instrument?->name ?? '').' '.Grade::label($entry->grade)),
                ]))),
                'line_date' => $entry->exam_date?->format('Y-m-d'),
                'detail' => null,
                'quantity' => '1.00',
                'unit_price' => number_format((float) ($entry->fee ?? 0), 2, '.', ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Everything the edit page shows, field for field what save() writes.
     *
     * @return array<string, mixed>
     */
    public function payload(PaymentDocument $document): array
    {
        $document->loadMissing('lines', 'order');
        $totals = $this->totals($document);

        return [
            'id' => $document->id,
            'kind' => $document->kind,
            'number' => $document->number,
            'issued_on' => $document->issued_on?->format('Y-m-d'),
            'title' => $document->title,
            'to_name' => $document->to_name,
            'to_detail' => $document->to_detail,
            'order_id' => $document->order_id,
            'method' => $document->method,
            'paid_on' => $document->paid_on?->format('Y-m-d'),
            'amount_received' => $document->amount_received,
            'reference' => $document->reference,
            'notes' => $document->notes,
            'lines' => $document->lines->map(fn ($line) => [
                'description' => $line->description,
                'line_date' => $line->line_date?->format('Y-m-d'),
                'detail' => $line->detail,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ])->values()->all(),
            'totals' => [
                'total' => self::money($totals['total']),
                'received' => $totals['received'] === null ? null : self::money($totals['received']),
                'balance' => $totals['balance'] === null ? null : self::money($totals['balance']),
                'balance_label' => $totals['balance'] === null ? null : $this->balanceLabel($totals['balance']),
            ],
        ];
    }
}
