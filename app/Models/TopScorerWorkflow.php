<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-winner workflow tracking row for the /admin/quarter-end top-scorer
 * awards. One row per (quarter, year, award_key, winner_full_name) — tied
 * winners each get their own row so progress on Anna doesn't tick off
 * progress on Maya.
 *
 * @property int    $quarter
 * @property int    $year
 * @property string $award_key          One of self::AWARD_KEYS
 * @property string $winner_full_name
 * @property bool   $bought             Gift card bought on Amazon
 * @property bool   $sent               Personalised winner email sent
 * @property bool   $cert               Certificate attached to email
 * @property int    $updated_by
 */
class TopScorerWorkflow extends Model
{
    protected $table = 'top_scorer_workflow';

    protected $fillable = [
        'quarter',
        'year',
        'award_key',
        'winner_full_name',
        'bought',
        'sent',
        'cert',
        'updated_by',
    ];

    protected $casts = [
        'bought' => 'boolean',
        'sent' => 'boolean',
        'cert' => 'boolean',
    ];

    public const STEPS = ['bought', 'sent', 'cert'];

    public const AWARD_KEYS = [
        'initial_5_distinction',
        'initial_5_merit',
        '6_8_distinction',
        '6_8_merit',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
