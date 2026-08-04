<?php

// database/seeders/SyllabusSeeder.php

namespace Database\Seeders;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Syllabus Finder from the curated dataset built in Cowork:
 *   database/seeders/data/books.json     — canonical Books table (Amazon links, tag musicexams-21)
 *   database/seeders/data/syllabus.json  — 910 pieces (Trinity Piano C&J + Rock & Pop), with
 *                                          audio search links, within-Trinity cross-references,
 *                                          and per-piece exact buy links resolved to the book above.
 *
 * Idempotent: wipes both tables and reloads. Seeding is manual (see dev-rules.md);
 * run with:  php artisan db:seed --class=SyllabusSeeder
 */
class SyllabusSeeder extends Seeder
{
    public function run(): void
    {
        // Core dataset (Piano C&J + Rock & Pop) plus the C&J Drum Kit and
        // Electronic Keyboard + Organ repertoire, each kept in its own files so the
        // large syllabus.json stays untouched.
        $books = array_merge(
            $this->load('books.json'),
            $this->load('drumkit_books.json'),
            $this->load('ek_organ_books.json'),
        );
        $pieces = array_merge(
            $this->load('syllabus.json'),
            $this->load('drumkit.json'),
            $this->load('ek_organ.json'),
        );

        DB::transaction(function () use ($books, $pieces) {
            // Clear children first (FK), then parents.
            SyllabusPiece::query()->delete();
            SyllabusBook::query()->delete();

            // Insert books; remember asin -> id so pieces can link to their book.
            $asinToId = [];
            foreach ($books as $b) {
                $book = SyllabusBook::create([
                    'exam_board' => 'Trinity',
                    'exam_stream' => $b['exam_stream'],
                    'instrument' => $b['instrument'],
                    'title' => $b['book'],
                    'edition' => $b['edition'] !== '' ? $b['edition'] : null,
                    'asin' => $b['asin'] ?? null,
                    'buy_url' => $b['url'] ?? null,
                ]);
                if (! empty($b['asin'])) {
                    $asinToId[$b['asin']] = $book->id;
                }
            }

            // Insert pieces, linking to the book by the ASIN embedded in the buy link.
            $now = now();
            $rows = [];
            foreach ($pieces as $p) {
                $buy = $p['buy'] ?? null;
                $buyUrl = is_array($buy) ? ($buy['amazon'] ?? null) : null;
                $bookId = null;
                if ($buyUrl && preg_match('#/dp/([A-Z0-9]{10})#', $buyUrl, $m)) {
                    $bookId = $asinToId[$m[1]] ?? null;
                }

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
                    'syllabus_book_id' => $bookId,
                    'technical_focus' => $p['technical_focus'] ?? false,
                    'voice_range' => $p['voice_range'] ?? null,
                    'syllabus_from' => $p['syllabus_from'] ?? null,
                    'buy_kind' => $p['buy_kind'] ?? 'none',
                    'buy_url' => $buyUrl,
                    'buy_edition' => $p['buy_edition'] ?? null,
                    'buy_alt_url' => $p['buy_alt_url'] ?? null,
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
        });

        $this->command?->info('Syllabus seeded: '.SyllabusBook::count().' books, '.SyllabusPiece::count().' pieces.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(string $file): array
    {
        $path = database_path('seeders/data/'.$file);
        if (! file_exists($path)) {
            throw new \RuntimeException("Syllabus seed file missing: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
