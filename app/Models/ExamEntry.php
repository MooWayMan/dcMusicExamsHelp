<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEntry extends Model
{
    use HasFactory;

    // ──────────────────────────────────────────
    // Notes-flag semantics
    // ──────────────────────────────────────────
    // CANCELLED — true cancellation: refund issued, no fee paid, no
    // commission earned. Excluded from EVERYTHING: Recognition page,
    // top-scorer awards, prize draw, certificates, AND teacher volume
    // metrics (Bronze/Silver/Gold/Top Award badges).
    //
    // NO_SHOW — booked, paid, no submission within the 28-day window.
    // Trinity charged the fee, Paul earned commission. Excluded from
    // result-based things (no score, no certificate, can't appear on
    // Recognition or in the prize draw) but DOES count for teacher
    // volume tallies — the booking was made, the entry happened from
    // the teacher's perspective.
    public const NOTE_CANCELLED = 'CANCELLED';
    public const NOTE_NO_SHOW = 'NO_SHOW';

    /**
     * Notes values meaning no exam outcome will exist. Used by every
     * result-based filter (Recognition page, top scorers, prize draw
     * eligibility, pending-results queue, certificate generation).
     */
    public const NOTES_NO_RESULT = [self::NOTE_CANCELLED, self::NOTE_NO_SHOW];

    /**
     * Scope: limit to entries that are still result-possible — exclude
     * both CANCELLED and NO_SHOW. Use anywhere a downstream consumer
     * needs a real exam outcome (score, certificate, draw ticket).
     *
     * Teacher VOLUME tallies should NOT use this scope — they want the
     * narrower CANCELLED-only filter so a NO_SHOW still counts toward
     * the teacher's quarterly entry count.
     */
    public function scopeWhereResultPossible($query)
    {
        // Columns are table-qualified so this scope stays composition-safe
        // when callers join other tables that also have a `notes` column
        // (e.g. `orders.notes`). See docs/dev-rules.md "Model scopes" rule.
        return $query->where(function ($q) {
            $q->whereNull('exam_entries.notes')
                ->orWhereNotIn('exam_entries.notes', self::NOTES_NO_RESULT);
        });
    }

    protected $fillable = [
        'order_id',
        'student_id',
        'instrument_id',
        'candidate_number',
        'candidate_name',
        'teacher_name',
        'booking_role',
        'school_name',
        'show_full_name',
        'show_on_thank_you',
        'grade',
        'subject_area',
        'delivery_method',
        'result',
        'score',
        'fee',
        'exam_date',
        'date_of_birth',
        'notes',
        'teacher_contact_id',
        'teacher_credit_status',
        'source',
        'applicant_name',
        'applicant_email',
        'submitter_contact_id',
        'certificate_sent_at',
        'report',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'date_of_birth' => 'date',
            'score' => 'integer',
            'show_full_name' => 'boolean',
            'show_on_thank_you' => 'boolean',
            'certificate_sent_at' => 'datetime',
            'report' => 'array',
        ];
    }

    // ──────────────────────────────────────────
    // Weekly cert-send tracking
    // ──────────────────────────────────────────

    /**
     * Scope: entries whose weekly cert email has NOT yet been sent.
     * Drives the Weekly Send section on /admin/certificates.
     */
    public function scopeCertNotSent($query)
    {
        return $query->whereNull('exam_entries.certificate_sent_at');
    }

    /**
     * Scope: entries whose weekly cert email has been marked sent.
     */
    public function scopeCertSent($query)
    {
        return $query->whereNotNull('exam_entries.certificate_sent_at');
    }

    // ──────────────────────────────────────────
    // Computed Attributes
    // ──────────────────────────────────────────

    /**
     * Result band based on score.
     */
    public function getResultBandAttribute(): ?string
    {
        if ($this->score === null) {
            return $this->result;
        }

        return match (true) {
            $this->score >= 87 => 'Distinction',
            $this->score >= 75 => 'Merit',
            $this->score >= 60 => 'Pass',
            default => 'Below Pass',
        };
    }

    /**
     * Is this a Hall of Fame entry? (Merit or Distinction)
     */
    public function isHallOfFame(): bool
    {
        return $this->score !== null && $this->score >= 75;
    }

    /**
     * Get the certificate name for this result.
     */
    public function getCertificateNameAttribute(): ?string
    {
        if ($this->score === null) {
            return null;
        }

        return match (true) {
            $this->score >= 87 => 'Standing Ovation Certificate',
            $this->score >= 75 => 'Take a Bow Certificate',
            default => 'Bravo Certificate',
        };
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    /**
     * The confirmed teacher contact for this exam entry.
     */
    public function teacherContact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class, 'teacher_contact_id');
    }
}
