<?php
// app/Models/OrderContact.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderContact extends Model
{
    protected $fillable = [
        'order_id',
        'exam_contact_id',
        'role_in_order',
        'is_primary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function examContact(): BelongsTo
    {
        return $this->belongsTo(ExamContact::class);
    }
}