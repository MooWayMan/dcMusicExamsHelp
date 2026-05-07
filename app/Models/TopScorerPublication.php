<?php

// app/Models/TopScorerPublication.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of the four top-scorer awards for a quarter.
 *
 * Created when Paul presses "Publish top-scorer awards" on Step 3. The
 * public Recognition page reads from this snapshot — so once published,
 * a late-arriving higher score won't silently change the leaderboard.
 *
 * @property int    $quarter
 * @property int    $year
 * @property array  $winners                  Snapshot, see structure below.
 * @property bool   $finalised_with_pending   True if pending results existed at publish time.
 * @property int    $pending_count
 * @property int    $published_by
 * @property \Carbon\Carbon $published_at
 *
 * `winners` shape (matches TopScorers::calculate output):
 *   [
 *     'initial_5' => ['distinction' => [...], 'merit' => [...]],
 *     '6_8'       => ['distinction' => [...], 'merit' => [...]],
 *   ]
 */
class TopScorerPublication extends Model
{
    protected $fillable = [
        'quarter',
        'year',
        'winners',
        'finalised_with_pending',
        'pending_count',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'winners' => 'array',
        'finalised_with_pending' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Look up an existing publication for a given quarter, or null.
     */
    public static function forQuarter(int $quarter, int $year): ?self
    {
        return self::where('quarter', $quarter)
            ->where('year', $year)
            ->first();
    }
}
