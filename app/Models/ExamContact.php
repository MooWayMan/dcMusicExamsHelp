<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'source',
        'notes',
        'user_id',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Multiple email addresses for this contact.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(ContactEmail::class);
    }

    /**
     * Orders this contact created/submitted.
     */
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_contact_id');
    }

    /**
     * Students where this contact is the confirmed teacher.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'teacher_contact_id');
    }

    /**
     * Exam entries where this contact is the confirmed teacher.
     */
    public function examEntries(): HasMany
    {
        return $this->hasMany(ExamEntry::class, 'teacher_contact_id');
    }

    /**
     * All orders linked to this contact with contextual roles.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_contacts')
            ->withPivot(['role_in_order', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    /**
     * Raw order contact pivot rows.
     */
    public function orderContacts(): HasMany
    {
        return $this->hasMany(OrderContact::class, 'exam_contact_id');
    }

    // ──────────────────────────────────────────
    // Computed Attributes
    // ──────────────────────────────────────────

    /**
     * Get the primary email address (from contact_emails table, falling back to email column).
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->emails->firstWhere('is_primary', true)?->email
            ?? $this->emails->first()?->email
            ?? $this->email;
    }

    // ──────────────────────────────────────────
    // Role Checks
    // ──────────────────────────────────────────

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSelfApplicant(): bool
    {
        return $this->role === 'self';
    }

    public function isApplicant(): bool
    {
        return $this->role === 'applicant';
    }

    public function isUnknown(): bool
    {
        return $this->role === 'unknown' || $this->role === null;
    }
}
