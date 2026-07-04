<?php

// app/Http/Controllers/TopTenController.php

namespace App\Http\Controllers;

use App\Models\PieceVote;
use App\Models\SyllabusPiece;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TopTenController extends Controller
{
    private const GRADE_ORDER = ['Initial', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'];

    private const STREAM_ORDER = ['Classical & Jazz', 'Rock & Pop'];

    /** Roles allowed to cast a vote. Everyone else views read-only. */
    private const VOTER_ROLES = ['teacher', 'admin'];

    /**
     * Public "Top Ten" chart. Teachers vote (star rating 1-4 + how many of their
     * students have used a piece in an exam); everyone sees the ranking. Pieces
     * are ranked WITHIN each instrument+grade by times used, then average stars,
     * using competition ranking so ties share a joint position. Each group is
     * split into the Top Ten and the "other pieces" that didn't make it.
     */
    public function index(Request $request): Response
    {
        $stream = trim((string) $request->query('stream', ''));
        $instrument = trim((string) $request->query('instrument', ''));
        $grade = trim((string) $request->query('grade', ''));

        $canVote = $this->canVote($request);

        // Voted pieces (the chart itself) with their aggregate scores.
        // Ranking signals are deliberately teacher-count led so the chart is
        // hard to game: one account can add at most a single capped usage band.
        $voted = SyllabusPiece::query()
            ->whereHas('votes')
            ->when($stream !== '', fn ($x) => $x->where('exam_stream', $stream))
            ->when($instrument !== '', fn ($x) => $x->where('instrument', $instrument))
            ->when($grade !== '', fn ($x) => $x->where('grade', $grade))
            ->withCount(['votes as teachers_using' => fn ($q) => $q->whereNotNull('used_band')])
            ->withSum('votes as usage_score', 'used_band')
            ->withAvg('votes as avg_rating', 'rating')
            ->withCount(['votes as rating_count' => fn ($q) => $q->whereNotNull('rating')])
            ->get()
            ->map(fn (SyllabusPiece $p) => [
                'id' => $p->id,
                'stream' => $p->exam_stream,
                'instrument' => $p->instrument,
                'variant' => $p->variant,
                'grade' => $p->grade,
                'composer' => $p->composer,
                'title' => $p->title,
                'teachers_using' => (int) $p->teachers_using,
                'usage_score' => (int) $p->usage_score,
                'avg_rating' => $p->avg_rating !== null ? round((float) $p->avg_rating, 2) : null,
                'rating_count' => (int) $p->rating_count,
            ]);

        $groups = $this->buildGroups($voted);

        // Current teacher's own votes (drives the inline "your vote" state).
        $myVotes = $canVote
            ? PieceVote::query()->where('user_id', $request->user()->id)->get()
                ->mapWithKeys(fn (PieceVote $v) => [$v->syllabus_piece_id => [
                    'rating' => $v->rating,
                    'used_band' => $v->used_band,
                ]])
            : collect();

        // Pieces a teacher can pick from to rate (bounded to a chosen instrument
        // so we never ship thousands of rows).
        $selectablePieces = ($canVote && $instrument !== '')
            ? SyllabusPiece::query()
                ->where('instrument', $instrument)
                ->when($stream !== '', fn ($x) => $x->where('exam_stream', $stream))
                ->when($grade !== '', fn ($x) => $x->where('grade', $grade))
                ->orderBy('grade')->orderBy('composer')->orderBy('title')
                ->limit(1200)
                ->get()
                ->map(fn (SyllabusPiece $p) => [
                    'id' => $p->id,
                    'grade' => $p->grade,
                    'label' => trim($p->title.' — '.$p->composer),
                ])->values()
            : collect();

        // Facets for the cascading dropdowns (small distinct lists).
        $streamInstruments = SyllabusPiece::query()->select('exam_stream', 'instrument')->distinct()->get()
            ->map(fn ($p) => ['stream' => $p->exam_stream, 'instrument' => $p->instrument])->values();
        $instrumentGrades = SyllabusPiece::query()->select('instrument', 'grade')->distinct()->get()
            ->map(fn ($p) => ['instrument' => $p->instrument, 'grade' => $p->grade])->values();
        $streams = collect(self::STREAM_ORDER)
            ->filter(fn ($s) => $streamInstruments->contains('stream', $s))
            ->values();

        return Inertia::render('TopTen', [
            'groups' => $groups,
            'myVotes' => $myVotes,
            'canVote' => $canVote,
            'selectablePieces' => $selectablePieces,
            'streams' => $streams,
            'streamInstruments' => $streamInstruments,
            'instrumentGrades' => $instrumentGrades,
            'gradeOrder' => self::GRADE_ORDER,
            'active' => compact('stream', 'instrument', 'grade'),
        ]);
    }

    /**
     * Cast / update / clear the current teacher's vote for one piece.
     */
    public function vote(Request $request): RedirectResponse
    {
        abort_unless($this->canVote($request), 403);

        $data = $request->validate([
            'syllabus_piece_id' => ['required', 'integer', 'exists:syllabus_pieces,id'],
            'rating' => ['nullable', 'integer', 'between:1,4'],
            'used_band' => ['nullable', 'integer', 'between:1,3'],
        ]);

        $rating = $data['rating'] ?? null;
        $band = $data['used_band'] ?? null;

        $existing = PieceVote::query()
            ->where('user_id', $request->user()->id)
            ->where('syllabus_piece_id', $data['syllabus_piece_id'])
            ->first();

        // Nothing meaningful set → treat as "clear my vote".
        if ($rating === null && $band === null) {
            $existing?->delete();

            return back()->with('success', 'Your vote was removed.');
        }

        PieceVote::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'syllabus_piece_id' => $data['syllabus_piece_id']],
            ['rating' => $rating, 'used_band' => $band],
        );

        return back()->with('success', 'Thanks — your vote has been counted.');
    }

    private function canVote(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && in_array($user->role, self::VOTER_ROLES, true);
    }

    /**
     * Sort voted pieces into instrument+grade groups, rank them (competition
     * ranking so ties share a joint position) and split Top Ten vs the rest.
     *
     * @param  Collection<int, array<string, mixed>>  $voted
     * @return array<int, array<string, mixed>>
     */
    private function buildGroups(Collection $voted): array
    {
        $streamIndex = array_flip(self::STREAM_ORDER);
        $gradeIndex = array_flip(self::GRADE_ORDER);

        $grouped = $voted->groupBy(fn ($p) => $p['stream'].'|'.$p['instrument'].'|'.$p['grade']);

        $groups = $grouped->map(function (Collection $rows) {
            // Order: most teachers using → higher usage band total → best
            // average stars → most ratings → title.
            $ordered = $rows->sort(function ($a, $b) {
                return [$b['teachers_using'], $b['usage_score'], $b['avg_rating'] ?? -1, $b['rating_count'], $a['title']]
                    <=> [$a['teachers_using'], $a['usage_score'], $a['avg_rating'] ?? -1, $a['rating_count'], $b['title']];
            })->values();

            // Competition ranking — equal (teachers, usage, avg stars) share a position.
            $rank = 0;
            $seen = 0;
            $prevKey = null;
            $ordered = $ordered->map(function ($p) use (&$rank, &$seen, &$prevKey) {
                $seen++;
                $key = $p['teachers_using'].'|'.$p['usage_score'].'|'.($p['avg_rating'] !== null ? number_format($p['avg_rating'], 2) : 'na');
                if ($key !== $prevKey) {
                    $rank = $seen;
                    $prevKey = $key;
                }
                $p['position'] = $rank;
                $p['is_top_ten'] = $rank <= 10;

                return $p;
            });

            $first = $ordered->first();

            return [
                'stream' => $first['stream'],
                'instrument' => $first['instrument'],
                'grade' => $first['grade'],
                'top_ten' => $ordered->filter(fn ($p) => $p['is_top_ten'])->values()->all(),
                'others' => $ordered->reject(fn ($p) => $p['is_top_ten'])->values()->all(),
            ];
        })->values();

        // Order the groups themselves: stream, instrument (alpha), grade.
        return $groups->sort(function ($a, $b) use ($streamIndex, $gradeIndex) {
            return [$streamIndex[$a['stream']] ?? 99, $a['instrument'], $gradeIndex[$a['grade']] ?? 99]
                <=> [$streamIndex[$b['stream']] ?? 99, $b['instrument'], $gradeIndex[$b['grade']] ?? 99];
        })->values()->all();
    }
}
