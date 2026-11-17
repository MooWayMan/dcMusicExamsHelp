<?php

// app/Models/PaymentDocumentLine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDocumentLine extends Model
{
    protected $fillable = [
        'payment_document_id',
        'position',
        'description',
        'line_date',
        'start_time',
        'end_time',
        'detail',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'line_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(PaymentDocument::class, 'payment_document_id');
    }
}
