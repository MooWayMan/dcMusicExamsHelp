<?php

// app/Services/PiecePlans.php

namespace App\Services;

use App\Models\ExamEntry;
use App\Models\PiecePlan;
use App\Models\PiecePlanItem;
use App\Models\SyllabusPiece;
use App\Models\User;
use App\Support\Grade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The teacher's Piece tracker: one plan per pupil's next exam, each holding
 * the pieces, technical work and supporting tests being prepared and how
 * ready each one is. Everything the page reads or writes goes through here.
 *
 * A plan is private to the user who made it. It is NOT linked to exam
 * entries — a pupil is planned before they are entered — so nothing here
 * can affect orders, commission, prizes or certificates.
 */
final class PiecePlans
{
    /** Who gets the tracker: people who teach pupils, not parents or self-candidates. */
    public const ROLES = ['teacher', 'school_admin', 'admin'];

    public const SECTIONS = ['piece', 'technical', 'supporting'];

    /** What each section is called in each kind of exam. */
    public const SECTION_LABELS = [
        'Classical & Jazz' => ['piece' => 'Pieces', 'technical' => 'Technical work', 'supporting' => 'Supporting tests'],
        'Rock & Pop' => ['piece' => 'Songs', 'technical' => 'Technical work', 'supporting' => 'Session skills'],
    ];

    /**
     * One-tap labels offered for the two free-text sections. A starting
     * point, not the syllabus: the teacher can type anything.
     */
    public const SUGGESTIONS = [
        'Classical & Jazz' => [
            'technical' => ['Scales & arpeggios', 'Exercises'],
            'supporting' => ['Sight reading', 'Aural', 'Improvisation', 'Musical knowledge'],
        ],
        'Rock & Pop' => [
            'technical' => [],
            'supporting' => ['Playback', 'Improvising'],
        ],
    ];

    public const MAX_ITEMS = 30;

    public function __construct(
        private readonly SyllabusFacets $facets,
        private readonly TeacherEntries $teacherEntries,
    ) {}

    public function canUse(User $user): bool
    {
        return in_array($user->role, self::ROLES, true);
    }

    /** @return list<array<string, mixed>> */
    public function forUser(User $user): array
    {
        return PiecePlan::query()
            ->where('user_id', $user->id)
            ->with('items.syllabusPiece:id,book_title')
            ->orderBy('pupil_name')
            ->orderBy('id')
            ->get()
            ->map(fn (PiecePlan $plan) => $this->payload($plan))
            ->all();
    }

    /**
     * Every field served here is written back by update(), except `book` and
     * `ready`, which are worked out from other fields and never stored.
     */
    public function payload(PiecePlan $plan): array
    {
        $items = $plan->items->map(fn (PiecePlanItem $item) => [
            'id' => $item->id,
            'section' => $item->section,
            'syllabus_piece_id' => $item->syllabus_piece_id,
            'label' => $item->label,
            'percent' => $item->percent,
            'book' => $item->syllabusPiece?->book_title,
        ])->values();

        return [
            'id' => $plan->id,
            'pupil_name' => $plan->pupil_name,
            'exam_stream' => $plan->exam_stream,
            'instrument' => $plan->instrument,
            'grade' => $plan->grade,
            'target_date' => $plan->target_date?->toDateString(),
            'items' => $items->all(),
            'ready' => $items->isEmpty() ? 0 : (int) round($items->avg('percent')),
        ];
    }

    /** @return array<string, mixed> */
    public function rules(?string $stream): array
    {
        $instruments = $this->facets->streamInstruments()
            ->where('stream', $stream)
            ->pluck('instrument')
            ->all();

        return [
            'pupil_name' => ['required', 'string', 'max:120'],
            'exam_stream' => ['required', Rule::in(SyllabusFacets::STREAM_ORDER)],
            'instrument' => ['required', Rule::in($instruments)],
            'grade' => ['required', Rule::in(SyllabusFacets::GRADE_ORDER)],
            'target_date' => ['nullable', 'date'],
            'items' => ['array', 'max:'.self::MAX_ITEMS],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.section' => ['required', Rule::in(self::SECTIONS)],
            'items.*.syllabus_piece_id' => ['nullable', 'integer', 'exists:syllabus_pieces,id'],
            'items.*.label' => ['nullable', 'string', 'max:200', 'required_without:items.*.syllabus_piece_id'],
            'items.*.percent' => ['required', 'integer', 'between:0,100'],
        ];
    }

    public function create(User $user, array $data): PiecePlan
    {
        return DB::transaction(function () use ($user, $data) {
            $plan = PiecePlan::create([
                'user_id' => $user->id,
                ...$this->planAttributes($data),
            ]);
            $this->syncItems($plan, $data['items'] ?? []);

            return $plan;
        });
    }

    public function update(PiecePlan $plan, array $data): void
    {
        DB::transaction(function () use ($plan, $data) {
            $plan->update($this->planAttributes($data));
            $this->syncItems($plan, $data['items'] ?? []);
        });
    }

    public function delete(PiecePlan $plan): void
    {
        $plan->delete();
    }

    public function owns(User $user, PiecePlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }

    /**
     * Pieces on the syllabus for one exam, for the piece picker.
     *
     * @return list<array{value: int, label: string, book: ?string}>
     */
    public function syllabusOptions(string $stream, string $instrument, string $grade): array
    {
        return SyllabusPiece::query()
            ->where('exam_stream', $stream)
            ->where('instrument', $instrument)
            ->where('grade', $grade)
            ->orderBy('position')
            ->orderBy('title')
            ->get()
            ->map(fn (SyllabusPiece $p) => [
                'value' => $p->id,
                'label' => self::pieceLabel($p),
                'book' => $p->book_title,
            ])
            ->values()
            ->all();
    }

    /**
     * The user's centre 120 candidates not already on a plan, one row per
     * name, newest exam first, so a teacher can start a plan from them.
     * The last exam is shown as a hint only: exam-entry instrument names
     * ("Guitar (Rock/Pop)") are not the syllabus's ("Guitar"), so nothing
     * is carried across automatically.
     *
     * @return list<array{name: string, last_exam: string}>
     */
    public function candidates(User $user): array
    {
        [, $entries] = $this->teacherEntries->forUser(
            $user,
            Carbon::parse(TeacherEntries::HISTORY_START),
            Carbon::now()->addYear(),
        );

        $planned = PiecePlan::query()
            ->where('user_id', $user->id)
            ->pluck('pupil_name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->all();

        return $entries
            ->filter(fn (ExamEntry $e) => trim((string) $e->candidate_name) !== '')
            ->sortByDesc(fn (ExamEntry $e) => $e->exam_date?->toDateString() ?? '9999')
            ->unique(fn (ExamEntry $e) => mb_strtolower(trim($e->candidate_name)))
            ->reject(fn (ExamEntry $e) => in_array(mb_strtolower(trim($e->candidate_name)), $planned, true))
            ->sortBy(fn (ExamEntry $e) => mb_strtolower($e->candidate_name))
            ->map(fn (ExamEntry $e) => [
                'name' => trim($e->candidate_name),
                'last_exam' => collect([
                    $e->instrument?->name,
                    Grade::label($e->grade),
                    $e->result,
                ])->filter()->implode(' · '),
            ])
            ->values()
            ->all();
    }

    private function planAttributes(array $data): array
    {
        return [
            'pupil_name' => trim($data['pupil_name']),
            'exam_stream' => $data['exam_stream'],
            'instrument' => $data['instrument'],
            'grade' => $data['grade'],
            'target_date' => $data['target_date'] ?? null,
        ];
    }

    /**
     * Make the plan's items exactly the list given, in that order. Rows with
     * an id belonging to this plan are updated, the rest created, and any
     * existing row not in the list is removed.
     */
    private function syncItems(PiecePlan $plan, array $items): void
    {
        $keep = [];

        foreach (array_values($items) as $position => $item) {
            $attributes = $this->itemAttributes($item, $position);

            $existing = isset($item['id'])
                ? PiecePlanItem::query()->where('piece_plan_id', $plan->id)->whereKey($item['id'])->first()
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keep[] = $existing->id;
            } else {
                $keep[] = $plan->items()->create($attributes)->id;
            }
        }

        PiecePlanItem::query()
            ->where('piece_plan_id', $plan->id)
            ->whereNotIn('id', $keep)
            ->delete();

        $plan->unsetRelation('items');
    }

    private function itemAttributes(array $item, int $position): array
    {
        $piece = ! empty($item['syllabus_piece_id'])
            ? SyllabusPiece::query()->find($item['syllabus_piece_id'])
            : null;

        return [
            'section' => $item['section'],
            'syllabus_piece_id' => $piece?->id,
            // A syllabus piece is always labelled from the syllabus, so the
            // label cannot drift from the piece it points at.
            'label' => $piece ? self::pieceLabel($piece) : trim((string) ($item['label'] ?? '')),
            'percent' => max(0, min(100, (int) $item['percent'])),
            'position' => $position,
        ];
    }

    private static function pieceLabel(SyllabusPiece $piece): string
    {
        return trim($piece->title.' — '.$piece->composer);
    }
}
