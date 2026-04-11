<?php

// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_id',
        'trinity_order_number',
        'delivery_method',
        'subject_area',
        'candidates',
        'venue',
        'order_status',
        'requested_start_date',
        'commission_rate',
        'commission_amount',
        'applicant_name',
        'applicant_email',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_start_date' => 'date',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'candidates' => 'integer',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    /**
     * The teacher who submitted this order.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function examEntries(): HasMany
    {
        return $this->hasMany(ExamEntry::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Is this a Digital Grade exam?
     */
    public function isDigital(): bool
    {
        return strtolower($this->delivery_method) === 'digital';
    }

    /**
     * Is this a Face-to-Face exam? (Trinity exports as "Default")
     */
    public function isFaceToFace(): bool
    {
        return strtolower($this->delivery_method) === 'default';
    }

    /**
     * Get the friendly delivery method name.
     */
    public function getDeliveryMethodLabelAttribute(): string
    {
        return $this->isDigital() ? 'DG' : 'F2F';
    }
}
