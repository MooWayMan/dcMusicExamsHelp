<?php

// database/seeders/Concerns/SeedsSyllabus.php

namespace Database\Seeders\Concerns;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;

/**
 * The one copy of "JSON seed data → syllabus_books / syllabus_pieces rows",
 * shared by SyllabusSeeder, DrumKitSyllabusSeeder and EkOrganSyllabusSeeder.
 * Each seeder decides only WHICH rows it clears and which files it loads.
 *
 * Buy links are stored as bare ASINs (`buy_asin`, `buy_alt_asin`, books' `asin`).
 * The Amazon tag is never in the data: App\Support\AmazonLink adds it.
 */
trait SeedsSyllabus
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadSyllabusData(string $file): array
    {
        $path = database_path('seeders/data/'.$file);
        if (! file_exists($path)) {
            throw new \RuntimeException("Syllabus seed file missing: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<int, array<string, mixed>>  $books
     * @return array<string, int> asin => syllabus_books.id, so pieces can link to their book
     */
    private function createSyllabusBooks(array $books): array
    {
        $asinToId = [];
        foreach ($books as $b) {
            $book = SyllabusBook::create([
                'exam_board' => 'Trinity',
                'exam_stream' => $b['exam_stream'],
                'instrument' => $b['instrument'],
                'title' => $b['book'],
                'edition' => $b['edition'] !== '' ? $b['edition'] : null,
                'asin' => $b['asin'] ?? null,
            ]);
            if (! empty($b['asin'])) {
                $asinToId[$b['asin']] = $book->id;
            }
        }

        return $asinToId;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pieces
     * @param  array<string, int>  $asinToId
     */
    private function insertSyllabusPieces(array $pieces, array $asinToId): void
    {
        $now = now();
        $rows = [];
        foreach ($pieces as $p) {
            $asin = $p['buy_asin'] ?? null;

            $rows[] = [
                'exam_board' => 'Trinity',
                'exam_stream' => $p['exam_stream'],
                'instrument' => $p['instrument'],
                'variant' => $p['variant'] ?? null,
                'grade' => $p['grade'],
                'position' => $p['no'] ?? null,
                'composer' => $p['composer'],
                'title' => $p['piece'],
                'book_title' => $p['book'] ?? null,
                'publisher_code' => $p['publisher_code'] ?? null,
                'syllabus_book_id' => $asin !== null ? ($asinToId[$asin] ?? null) : null,
                'technical_focus' => $p['technical_focus'] ?? false,
                'voice_range' => $p['voice_range'] ?? null,
                'syllabus_from' => $p['syllabus_from'] ?? null,
                'buy_kind' => $p['buy_kind'] ?? 'none',
                'buy_asin' => $asin,
                'buy_edition' => $p['buy_edition'] ?? null,
                'buy_alt_asin' => $p['buy_alt_asin'] ?? null,
                'buy_alt_edition' => $p['buy_alt_edition'] ?? null,
                'buy_ebook_url' => $p['buy_ebook_url'] ?? null,
                'curated_video_url' => $p['curated_video_url'] ?? null,
                'audio' => json_encode($p['audio'] ?? null),
                'also_in' => json_encode($p['also_in'] ?? []),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            SyllabusPiece::insert($chunk);
        }
    }
}
