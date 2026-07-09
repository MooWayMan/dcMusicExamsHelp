<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'source',
        'notes',
        'user_id',
        'how_they_found_us',
        'hubspot_contact_id',
        'met_face_to_face',
        'spoken_on_phone',
        'contacted_by_email',
        'show_full_name',
        'excluded_from_prize_draw',
    ];

    protected $casts = [
        'met_face_to_face' => 'boolean',
        'spoken_on_phone' => 'boolean',
        'contacted_by_email' => 'boolean',
        'show_full_name' => 'boolean',
        'excluded_from_prize_draw' => 'boolean',
    ];

    /**
     * Allowed person-level types — kept in sync with UnifyContacts::ALLOWED_TYPES.
     */
    public const TYPES = [
        'teacher', 'parent', 'candidate',
        'school_admin', 'trinity_admin', 'subscriber',
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
     * Schools this contact is linked to (for teachers + school_admins).
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'contact_school')
            ->withTimestamps();
    }

    /**
     * Instruments this contact teaches (for teachers).
     */
    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'contact_instrument')
            ->withTimestamps();
    }

    /**
     * Contact log entries (calls, meetings, emails Paul has logged).
     */
    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class, 'exam_contact_id');
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
     * Exam entries where this contact submitted the order to Trinity.
     */
    public function submittedExamEntries(): HasMany
    {
        return $this->hasMany(ExamEntry::class, 'submitter_contact_id');
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
    // Type checks (multi-type via contact_types pivot)
    // ──────────────────────────────────────────

    /**
     * All types this contact has, as a flat array.
     */
    public function getTypesAttribute(): array
    {
        return \DB::table('contact_types')
            ->where('exam_contact_id', $this->id)
            ->pluck('type')
            ->all();
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function isTeacher(): bool
    {
        return $this->hasType('teacher');
    }

    public function isParent(): bool
    {
        return $this->hasType('parent');
    }

    public function isCandidate(): bool
    {
        return $this->hasType('candidate');
    }

    public function isSchoolAdmin(): bool
    {
        return $this->hasType('school_admin');
    }

    /**
     * Display name to show on the authenticated teacher dashboard
     * (specifically the prize-draw winner widget).
     *
     * Priority order:
     *   1. School admin with at least one linked school → school name
     *      (Daniel Rogers / Pulse Music School). Removes the personal
     *      name from view entirely and is more flattering for the school.
     *   2. Contact has opted in via `show_full_name = true` → full name.
     *   3. Otherwise → "First L" (e.g. "Helen H"). GDPR-safe default.
     *
     * Mirrors the same logic used by ThankYouController for candidate
     * names on the public Recognition page.
     */
    public function displayName(): string
    {
        if ($this->isSchoolAdmin()) {
            $school = $this->schools()->first();
            if ($school?->name) {
                return $school->name;
            }
        }

        if ($this->show_full_name) {
            return $this->name;
        }

        $parts = preg_split('/\s+/', trim((string) $this->name));
        if (count($parts) < 2) {
            return (string) $this->name;
        }

        $firstName = $parts[0];
        $surname = end($parts);
        $lastInitial = mb_strtoupper(mb_substr($surname, 0, 1));

        return "{$firstName} {$lastInitial}";
    }

    public function isTrinityAdmin(): bool
    {
        return $this->hasType('trinity_admin');
    }

    public function isSubscriber(): bool
    {
        return $this->hasType('subscriber');
    }

    /**
     * Add a type if not already present.
     */
    public function addType(string $type): void
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Unknown contact type: $type");
        }
        \DB::table('contact_types')->updateOrInsert(
            ['exam_contact_id' => $this->id, 'type' => $type],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Remove a type if present.
     */
    public function removeType(string $type): void
    {
        \DB::table('contact_types')
            ->where('exam_contact_id', $this->id)
            ->where('type', $type)
            ->delete();
    }

    // ──────────────────────────────────────────
    // Query scopes
    // ──────────────────────────────────────────

    /**
     * Scope: contacts that have ANY of the given types.
     *   ExamContact::withType('teacher')->get();
     *   ExamContact::withType(['teacher', 'school_admin'])->get();
     */
    public function scopeWithType(Builder $query, string|array $types): Builder
    {
        $types = (array) $types;

        return $query->whereExists(function ($q) use ($types) {
            $q->select(\DB::raw(1))
                ->from('contact_types')
                ->whereColumn('contact_types.exam_contact_id', 'exam_contacts.id')
                ->whereIn('type', $types);
        });
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

    /**
     * Resolve a contact by ANY of its emails — the primary `email` column OR
     * a secondary in `contact_emails`. Use this everywhere the importer matches
     * an applicant/submitter by email, so a person who books under a second
     * address (e.g. Daniel Rogers using exams@… as well as rogers@…) resolves
     * to their existing record instead of spawning a duplicate.
     */
    public static function findByEmail(?string $email): ?self
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }
        $lower = mb_strtolower($email);

        $contact = static::whereRaw('LOWER(email) = ?', [$lower])->first();
        if ($contact) {
            return $contact;
        }

        $row = ContactEmail::whereRaw('LOWER(email) = ?', [$lower])->first();

        return $row ? static::find($row->exam_contact_id) : null;
    }
}
