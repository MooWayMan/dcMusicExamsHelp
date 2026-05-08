<?php

// app/Models/ImportRun.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per committed import via /admin/imports. Preview-only runs
 * do not write here. The summary JSON is shaped by the controller —
 * counts of created/updated/skipped/etc.
 */
class ImportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'filename',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
