<?php

// app/Services/PaymentDocumentPdf.php

namespace App\Services;

use App\Models\PaymentDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentDocumentPdf
{
    public function __construct(private PaymentDocuments $documents = new PaymentDocuments()) {}

    public function render(PaymentDocument $document): string
    {
        $document->loadMissing('lines', 'order');

        return Pdf::loadView('exports.payment-document', [
            'document' => $document,
            'totals' => $this->documents->totals($document),
            'service' => $this->documents,
            'rep' => config('rep'),
        ])->setPaper('a4', 'portrait')->output();
    }

    public function filename(PaymentDocument $document): string
    {
        $who = preg_replace('/[^A-Za-z0-9]+/', '_', $document->to_name);

        return trim("{$document->number}_{$who}", '_').'.pdf';
    }
}
