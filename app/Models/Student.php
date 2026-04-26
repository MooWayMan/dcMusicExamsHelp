<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'notes',
        'teacher_contact_id',
        'teacher_credit_status',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    /**
     * The confirmed teacher contact for this student.
     */
    public function teacherContact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class, 'teacher_contact_id');
    }

    /**
     * @deprecated Use teacherContact() instead. Kept for backward compatibility.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function examEntries(): HasMany
    {
        return $this->hasMany(ExamEntry::class);
    }

    /**
     * All distinct instruments this student has appeared on across exam_entries.
     * Replaces the old students.instrument_id single FK (dropped 2026_04_26):
     * a student can take Piano + Drums grade entries, so a single FK was
     * always going to be wrong sometimes.
     */
    public function instruments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Instrument::class,
            'exam_entries',
            'student_id',
            'instrument_id'
        )->distinct();
    }

    // ──────────────────────────────────────────
    // Computed Attributes
    // ──────────────────────────────────────────

    /**
     * Get student's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
