<?php

// app/Models/User.php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'notes', 'met_face_to_face', 'spoken_on_phone', 'contacted_by_email', 'how_they_found_us', 'hubspot_contact_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'met_face_to_face' => 'boolean',
            'spoken_on_phone' => 'boolean',
            'contacted_by_email' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────
    // Role helpers
    // ──────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'teacher_school')
            ->withTimestamps();
    }

    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'teacher_instrument')
            ->withTimestamps();
    }

    public function subjectAreas(): BelongsToMany
    {
        return $this->belongsToMany(SubjectArea::class, 'teacher_subject_area')
            ->withTimestamps();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class);
    }
}
