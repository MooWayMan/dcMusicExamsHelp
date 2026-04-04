<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = [
        'name',
        'email',
        'role',
        'source',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return is_null($this->unsubscribed_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unsubscribed_at');
    }
}
