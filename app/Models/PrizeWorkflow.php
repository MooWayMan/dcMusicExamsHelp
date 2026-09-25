<?php

// app/Models/PrizeWorkflow.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Quarter End tick boxes for one prize winner. One row per
 * (quarter, year, award_key, winner_full_name), so tied winners each get
 * their own row and ticking Anna never ticks Maya.
 *
 * Which boxes a prize shows depends on how its gift card reaches the winner,
 * and STEPS_BY_AWARD is the only place that is decided. The page draws its
 * boxes from it and the toggle endpoint refuses any step not listed for that
 * prize.
 *
 *   - Top scorers and the student draw go through a teacher, so the parent
 *     emails to claim and the card is bought only then.
 *   - The teacher draw winner IS the recipient, so the card is bought first
 *     and the link goes in the winner email.
 *
 * @property int    $quarter
 * @property int    $year
 * @property string $award_key
 * @property string $winner_full_name
 * @property bool   $sent       Winner email sent
 * @property bool   $cert       Certificate attached to the email
 * @property bool   $claimed    Parent emailed to claim
 * @property bool   $bought     Gift card bought on Amazon
 * @property bool   $card_sent  Gift card sent to the winner
 * @property bool   $used       Amazon order page shows the card Redeemed
 * @property \Illuminate\Support\Carbon|null $sent_at  When Email sent was ticked
 * @property int    $updated_by
 */
class PrizeWorkflow extends Model
{
    protected $table = 'prize_workflow';

    public const STEP_LABELS = [
        'sent' => 'Email sent',
        'cert' => 'Cert',
        'claimed' => 'Claimed',
        'bought' => 'Bought',
        'card_sent' => 'Card sent',
        'used' => 'Used',
    ];

    private const CLAIM_FIRST = ['sent', 'claimed', 'bought', 'card_sent', 'used'];

    private const TOP_SCORER = ['sent', 'cert', 'claimed', 'bought', 'card_sent', 'used'];

    public const STEPS_BY_AWARD = [
        'initial_5_distinction' => self::TOP_SCORER,
        'initial_5_merit' => self::TOP_SCORER,
        '6_8_distinction' => self::TOP_SCORER,
        '6_8_merit' => self::TOP_SCORER,
        'student_draw' => self::CLAIM_FIRST,
        'teacher_draw' => ['bought', 'sent', 'used'],
    ];

    protected $fillable = [
        'quarter',
        'year',
        'award_key',
        'winner_full_name',
        'sent',
        'cert',
        'claimed',
        'bought',
        'card_sent',
        'used',
        'sent_at',
        'updated_by',
    ];

    protected $casts = [
        'sent' => 'boolean',
        'cert' => 'boolean',
        'claimed' => 'boolean',
        'bought' => 'boolean',
        'card_sent' => 'boolean',
        'used' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Tick or untick one box. Ticking Email sent records the day, which is
     * when the winner's 12 months start; unticking it clears the day.
     */
    public function setStep(string $step, bool $value): void
    {
        $this->{$step} = $value;

        if ($step === 'sent') {
            $this->sent_at = $value ? ($this->sent_at ?? now()) : null;
        }
    }

    /**
     * @return array<string, bool>
     */
    public function status(): array
    {
        return collect(self::STEP_LABELS)
            ->keys()
            ->mapWithKeys(fn (string $step) => [$step => (bool) $this->{$step}])
            ->all();
    }

    /**
     * The boxes each prize shows, in order, with their labels.
     *
     * @return array<string, array<int, array{key: string, label: string}>>
     */
    public static function stepsForPage(): array
    {
        return collect(self::STEPS_BY_AWARD)
            ->map(fn (array $steps) => array_map(
                fn (string $step) => ['key' => $step, 'label' => self::STEP_LABELS[$step]],
                $steps,
            ))
            ->all();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
