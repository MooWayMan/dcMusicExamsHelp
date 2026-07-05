<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'role',
        'source',
        'subscribed_at',
        'unsubscribed_at',
        'marketing_consent_at',
        'hubspot_contact_id',
        'hubspot_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'hubspot_synced_at' => 'datetime',
        ];
    }

    /**
     * Has this subscriber actively opted-in to receive ongoing marketing
     * comms? Lead-magnet downloads on their own do NOT count — only the
     * explicit checkbox tick is recorded.
     */
    public function hasMarketingConsent(): bool
    {
        return ! is_null($this->marketing_consent_at);
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
