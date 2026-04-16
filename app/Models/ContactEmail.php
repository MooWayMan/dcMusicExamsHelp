<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEmail extends Model
{
    protected $fillable = [
        'exam_contact_id',
        'email',
        'label',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class, 'exam_contact_id');
    }
}
