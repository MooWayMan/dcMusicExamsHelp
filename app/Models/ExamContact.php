<?php
// app/Models/ExamContact.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'source',
        'notes',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_contact_id');
    }

    public function studentLinks(): HasMany
    {
        return $this->hasMany(Student::class, 'teacher_contact_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_contacts')
            ->withPivot(['role_in_order', 'is_primary', 'notes'])
            ->withTimestamps();
    }

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