<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
        'commission_paid_at',
        'commission_paid_amount',
        'applicant_name',
        'applicant_email',
        'notes',
        'created_by_contact_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_start_date' => 'date',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'commission_paid_at' => 'date',
            'commission_paid_amount' => 'decimal:2',
            'candidates' => 'integer',
        ];
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    /**
     * The contact who created / submitted the order. This is the single
     * canonical link from an order to a person (replaces the old
     * orders.user_id → users FK that was dropped in 2026_04_26).
     */
    public function createdByContact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class, 'created_by_contact_id');
    }

    /**
     * All contacts linked to this order with contextual roles.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(ExamContact::class, 'order_contacts')
            ->withPivot(['role_in_order', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    /**
     * Raw order contact rows.
     */
    public function orderContacts(): HasMany
    {
        return $this->hasMany(OrderContact::class);
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

    /**
     * Has Trinity paid the commission on this order?
     * Presence of `commission_paid_at` is the source of truth — it's set
     * from the remittance date on a Trinity Finance statement.
     */
    public function isPaid(): bool
    {
        return ! is_null($this->commission_paid_at);
    }

    // ──────────────────────────────────────────
    // Query scopes
    // ──────────────────────────────────────────

    /**
     * Commission has been paid by Trinity (remittance received).
     */
    public function scopePaid($query)
    {
        return $query->whereNotNull('commission_paid_at');
    }

    /**
     * Commission has NOT yet been paid by Trinity.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('commission_paid_at');
    }
}