<?php

// app/Models/PiecePlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One pupil's next exam on a teacher's Piece tracker. Read and written only
 * through App\Services\PiecePlans.
 */
class PiecePlan extends Model
{
    protected $fillable = [
        'user_id',
        'pupil_name',
        'exam_stream',
        'instrument',
        'grade',
        'target_date',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PiecePlanRating::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PiecePlanItem::class)->orderBy('position')->orderBy('id');
    }
}
