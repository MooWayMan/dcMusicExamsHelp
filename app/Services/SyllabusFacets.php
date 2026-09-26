<?php

// app/Services/SyllabusFacets.php

namespace App\Services;

use App\Models\SyllabusPiece;
use Illuminate\Support\Collection;

/**
 * The exam type / instrument / grade lists behind every syllabus dropdown
 * (Piece Finder, Top Ten, Piece tracker), and the order they are shown in.
 * The front-end half is composables/useSyllabusFacets.ts, which cascades
 * them. Both used to be written out separately in each page.
 */
final class SyllabusFacets
{
    public const GRADE_ORDER = ['Initial', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'];

    public const STREAM_ORDER = ['Classical & Jazz', 'Rock & Pop'];

    /**
     * The props every dropdown page is given, under the names the pages
     * already use.
     *
     * @return array{streams: Collection, streamInstruments: Collection, instrumentGrades: Collection, gradeOrder: list<string>}
     */
    public function forDropdowns(): array
    {
        $streamInstruments = $this->streamInstruments();

        return [
            'streams' => collect(self::STREAM_ORDER)
                ->filter(fn ($s) => $streamInstruments->contains('stream', $s))
                ->values(),
            'streamInstruments' => $streamInstruments,
            'instrumentGrades' => SyllabusPiece::query()->select('instrument', 'grade')->distinct()->get()
                ->map(fn ($p) => ['instrument' => $p->instrument, 'grade' => $p->grade])->values(),
            'gradeOrder' => self::GRADE_ORDER,
        ];
    }

    /** @return Collection<int, array{stream: string, instrument: string}> */
    public function streamInstruments(): Collection
    {
        return SyllabusPiece::query()->select('exam_stream', 'instrument')->distinct()->get()
            ->map(fn ($p) => ['stream' => $p->exam_stream, 'instrument' => $p->instrument])->values();
    }

    /** @return array<string, int> grade => position, for sorting */
    public static function gradeIndex(): array
    {
        return array_flip(self::GRADE_ORDER);
    }

    /** @return array<string, int> stream => position, for sorting */
    public static function streamIndex(): array
    {
        return array_flip(self::STREAM_ORDER);
    }
}
