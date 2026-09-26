<?php

// app/Models/PiecePlanRating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pupil's mark out of 10 for one syllabus piece on their plan's
 * "Choose pieces" list. Read and written only through App\Services\PiecePlans.
 */
class PiecePlanRating extends Model
{
    protected $fillable = [
        'piece_plan_id',
        'syllabus_piece_id',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PiecePlan::class, 'piece_plan_id');
    }
}
