<?php

// app/Models/PieceVote.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'syllabus_piece_id',
        'rating',
        'used_band',
    ];

    protected $casts = [
        'rating' => 'integer',
        'used_band' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(SyllabusPiece::class, 'syllabus_piece_id');
    }
}
