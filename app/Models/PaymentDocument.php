<?php

// app/Models/PaymentDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A payment advice (Paul paid someone on Trinity's behalf) or a receipt (Paul
 * was paid exam fees). Written and read only through App\Services\PaymentDocuments.
 */
class PaymentDocument extends Model
{
    public const KIND_PAYMENT = 'payment';

    public const KIND_RECEIPT = 'receipt';

    protected $fillable = [
        'kind',
        'number',
        'issued_on',
        'title',
        'to_name',
        'to_detail',
        'order_id',
        'method',
        'paid_on',
        'amount_received',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'paid_on' => 'date',
            'amount_received' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentDocumentLine::class)->orderBy('position');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
