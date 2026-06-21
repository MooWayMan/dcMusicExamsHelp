<?php

// app/Models/SyllabusPiece.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusPiece extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_board',
        'exam_stream',
        'instrument',
        'variant',
        'grade',
        'position',
        'composer',
        'title',
        'book_title',
        'publisher_code',
        'syllabus_book_id',
        'technical_focus',
        'voice_range',
        'syllabus_from',
        'buy_kind',
        'buy_url',
        'buy_edition',
        'buy_alt_url',
        'buy_alt_edition',
        'buy_ebook_url',
        'curated_video_url',
        'audio',
        'also_in',
    ];

    protected $casts = [
        'technical_focus' => 'boolean',
        'audio' => 'array',
        'also_in' => 'array',
    ];

    public function book(): BelongsTo
    {
        // Explicit FK: the column is syllabus_book_id, not the belongsTo default (book_id).
        return $this->belongsTo(SyllabusBook::class, 'syllabus_book_id');
    }

    /**
     * Free-text search across title, composer/artist and book name.
     * Columns are table-qualified so the scope survives joins (dev-rules.md).
     * Postgres: use ilike for case-insensitive matching.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('syllabus_pieces.title', 'ilike', $like)
                ->orWhere('syllabus_pieces.composer', 'ilike', $like)
                ->orWhere('syllabus_pieces.book_title', 'ilike', $like);
        });
    }
}
