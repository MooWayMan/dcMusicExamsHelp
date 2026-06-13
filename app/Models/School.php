<?php

// app/Models/School.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'postcode',
        'email',
        'notes',
    ];

    /**
     * Unified-model contacts linked to this school (teachers, school_admins, etc.)
     * via the contact_school pivot.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(ExamContact::class, 'contact_school')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Instruments this school is associated with, persisted via the
     * school_instrument pivot so they survive deletion of the exam entries
     * they were originally derived from.
     */
    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'school_instrument')
            ->withTimestamps();
    }
}
