<?php

// app/Models/SyllabusBook.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_board',
        'exam_stream',
        'instrument',
        'title',
        'edition',
        'asin',
    ];

    public function pieces(): HasMany
    {
        return $this->hasMany(SyllabusPiece::class);
    }
}
