<?php

// app/Models/ContactLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_contact_id',
        'contact_type',
        'direction',
        'subject',
        'summary',
        'contacted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'date',
        ];
    }

    /**
     * The contact this log relates to (unified contacts model).
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class, 'exam_contact_id');
    }
}
