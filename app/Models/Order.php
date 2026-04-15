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
        'created_by_contact_id',
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
     * Legacy teacher/user link.
     * Keep for backward compatibility while refactoring.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The contact who created / submitted the order in the new system.
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