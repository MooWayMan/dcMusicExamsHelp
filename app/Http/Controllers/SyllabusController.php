<?php

// app/Http/Controllers/SyllabusController.php

namespace App\Http\Controllers;

use App\Models\SyllabusPiece;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyllabusController extends Controller
{
    private const GRADE_ORDER = ['Initial', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'];

    private const STREAM_ORDER = ['Classical & Jazz', 'Rock & Pop'];

    /**
     * Public Piece Finder — server-side filtering so the page stays light no
     * matter how many instruments are loaded. Filter facets (small distinct
     * lists) ship on first load to drive the cascading dropdowns; the matching
     * pieces are fetched via an Inertia partial reload when a filter/search is set.
     */
    public function index(Request $request): Response
    {
        $stream = trim((string) $request->query('stream', ''));
        $instrument = trim((string) $request->query('instrument', ''));
        $grade = trim((string) $request->query('grade', ''));
        $q = trim((string) $request->query('q', ''));
        $hasQuery = $stream !== '' || $instrument !== '' || $grade !== '' || $q !== '';

        $query = SyllabusPiece::query()
            ->when($stream !== '', fn ($x) => $x->where('exam_stream', $stream))
            ->when($instrument !== '', fn ($x) => $x->where('instrument', $instrument))
            ->when($grade !== '', fn ($x) => $x->where('grade', $grade))
            ->when($q !== '', fn ($x) => $x->search($q));

        $count = $hasQuery ? (clone $query)->count() : 0;

        $gradeIndex = array_flip(self::GRADE_ORDER);
        $pieces = $hasQuery
            ? $query->limit(800)->get()
                ->sortBy(fn (SyllabusPiece $p) => sprintf(
                    '%s|%s|%02d|%05d|%s',
                    $p->exam_stream, $p->instrument,
                    $gradeIndex[$p->grade] ?? 99, $p->position ?? 9999, $p->composer
                ))
                ->values()
                ->map(fn (SyllabusPiece $p) => [
                    'id' => $p->id,
                    'stream' => $p->exam_stream,
                    'instrument' => $p->instrument,
                    'variant' => $p->variant,
                    'grade' => $p->grade,
                    'composer' => $p->composer,
                    'title' => $p->title,
                    'book' => $p->book_title,
                    'publisher_code' => $p->publisher_code,
                    'technical_focus' => $p->technical_focus,
                    'voice_range' => $p->voice_range,
                    'buy_kind' => $p->buy_kind,
                    'buy_url' => $p->buy_url,
                    'buy_edition' => $p->buy_edition,
                    'buy_alt_url' => $p->buy_alt_url,
                    'buy_alt_edition' => $p->buy_alt_edition,
                    'buy_ebook_url' => $p->buy_ebook_url,
                    'curated_video_url' => $p->curated_video_url,
                    'audio' => $p->audio,
                    'also_in' => $p->also_in,
                ])
            : collect();

        // Facets for cascading dropdowns (small distinct lists, computed once).
        $streamInstruments = SyllabusPiece::query()->select('exam_stream', 'instrument')->distinct()->get()
            ->map(fn ($p) => ['stream' => $p->exam_stream, 'instrument' => $p->instrument])->values();
        $instrumentGrades = SyllabusPiece::query()->select('instrument', 'grade')->distinct()->get()
            ->map(fn ($p) => ['instrument' => $p->instrument, 'grade' => $p->grade])->values();

        $streams = collect(self::STREAM_ORDER)
            ->filter(fn ($s) => $streamInstruments->contains('stream', $s))
            ->values();

        return Inertia::render('Syllabus', [
            'pieces' => $pieces,
            'count' => $count,
            'hasQuery' => $hasQuery,
            'streams' => $streams,
            'streamInstruments' => $streamInstruments,
            'instrumentGrades' => $instrumentGrades,
            'gradeOrder' => self::GRADE_ORDER,
            'active' => compact('stream', 'instrument', 'grade', 'q'),
        ]);
    }
}
