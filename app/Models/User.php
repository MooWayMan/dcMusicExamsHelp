<?php

// app/Models/User.php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    /**
     * All accepted role values for registered users.
     *
     * Mirrors the role split agreed for the registration flow:
     *   - admin        → site admin (Paul, full backend access)
     *   - school_admin → school office staff (sees only their school)
     *   - teacher      → individual music teachers (sees only their candidates)
     *   - parent       → parents of under-18 candidates (sees only their child)
     *   - self         → self-candidates aged 18+ (sees only their own results)
     *
     * Kept loosely consistent with ExamContact::TYPES so role labels read the
     * same across the auth side (this model) and the people-system side
     * (exam_contacts).
     */
    public const ROLES = [
        'admin',
        'school_admin',
        'teacher',
        'parent',
        'self',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'school_admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isSelf(): bool
    {
        return $this->role === 'self';
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
