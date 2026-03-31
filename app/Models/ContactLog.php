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
        'user_id',
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
     * The teacher this contact log relates to.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
