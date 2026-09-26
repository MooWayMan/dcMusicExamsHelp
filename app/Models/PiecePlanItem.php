<?php

// app/Models/PiecePlanItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiecePlanItem extends Model
{
    protected $fillable = [
        'piece_plan_id',
        'section',
        'syllabus_piece_id',
        'label',
        'percent',
        'position',
    ];

    protected $casts = [
        'percent' => 'integer',
        'position' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PiecePlan::class, 'piece_plan_id');
    }

    public function syllabusPiece(): BelongsTo
    {
        return $this->belongsTo(SyllabusPiece::class);
    }
}
