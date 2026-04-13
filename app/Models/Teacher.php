<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'trinity_applicant_id',
        'user_id',
        'notes',
    ];

    /**
     * Get all emails for this teacher.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(TeacherEmail::class);
    }

    /**
     * Get the primary email address.
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        return $this->emails->firstWhere('is_primary', true)?->email
            ?? $this->emails->first()?->email;
    }

    /**
     * Get all exam entries for this teacher.
     */
    public function examEntries(): HasMany
    {
        return $this->hasMany(ExamEntry::class);
    }

    /**
     * Schools this teacher is linked to.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_teacher')->withTimestamps();
    }

    /**
     * Optional user account (if teacher also logs in).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Is this an actual teacher (not a parent/self booking)?
     */
    public function isTeacher(): bool
    {
        return $this->type === 'teacher';
    }
}
