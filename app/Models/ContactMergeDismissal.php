<?php

// app/Models/ContactMergeDismissal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A "not the same person" decision for a pair of contacts. Stored with the
 * lower id in low_contact_id so the pair is order-independent.
 */
class ContactMergeDismissal extends Model
{
    protected $fillable = ['low_contact_id', 'high_contact_id'];

    /**
     * Normalise a pair to (low, high) so lookups/inserts are order-agnostic.
     *
     * @return array{0:int,1:int}
     */
    public static function pair(int $a, int $b): array
    {
        return $a <= $b ? [$a, $b] : [$b, $a];
    }

    public static function isDismissed(int $a, int $b): bool
    {
        [$low, $high] = self::pair($a, $b);

        return self::where('low_contact_id', $low)
            ->where('high_contact_id', $high)
            ->exists();
    }

    public static function dismiss(int $a, int $b): void
    {
        [$low, $high] = self::pair($a, $b);

        self::firstOrCreate(['low_contact_id' => $low, 'high_contact_id' => $high]);
    }
}
