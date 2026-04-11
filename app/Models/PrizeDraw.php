<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeDraw extends Model
{
    protected $fillable = [
        'type',
        'quarter',
        'year',
        'winner_name',
        'winner_instrument',
        'winner_grade',
        'winner_teacher',
        'winner_entries',
        'total_tickets',
        'all_eligible',
        'drawn_by',
    ];

    protected function casts(): array
    {
        return [
            'all_eligible' => 'array',
        ];
    }

    public function drawnByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }
}
