<?php

// app/Models/ExamEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'student_id',
        'instrument_id',
        'candidate_number',
        'candidate_name',
        'teacher_name',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'score' => 'integer',
            'show_full_name' => 'boolean',
            'show_on_thank_you' => 'boolean',
        ];
    }

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
}
