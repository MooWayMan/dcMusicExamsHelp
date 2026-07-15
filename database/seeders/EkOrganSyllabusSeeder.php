<?php

// database/seeders/EkOrganSyllabusSeeder.php

namespace Database\Seeders;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Additive seeder for the Classical & Jazz Electronic Keyboard + Organ repertoire
 * (database/seeders/data/ek_organ.json + ek_organ_books.json), hand-parsed from the
 * "EK & Organ Syllabus from 2019" PDF.
 *
 * Like DrumKitSyllabusSeeder — and UNLIKE SyllabusSeeder (which wipes and reloads the
 * WHOLE finder) — this only touches Electronic Keyboard + Organ rows, so it is SAFE to
 * run on prod without disturbing other pieces or the Top Ten votes that cascade-delete
 * off syllabus_pieces.
 *
 * Buy links: only the 9 Trinity EK "core repertoire" books carry an Amazon link. EK
 * "alternative repertoire" and all Organ pieces show book title + publisher code only.
 *
 * Idempotent: clears just the C&J Electronic Keyboard + Organ books/pieces, then reloads.
 *
 *   sail artisan db:seed --class=EkOrganSyllabusSeeder
 */
class EkOrganSyllabusSeeder extends Seeder
{
    /** @var array<int, string> */
    private array $instruments = ['Electronic Keyboard', 'Organ'];

    public function run(): void
    {
        $books = $this->load('ek_organ_books.json');
        $pieces = $this->load('ek_organ.json');

        DB::transaction(function () use ($books, $pieces) {
            // Remove only the EK + Organ rows (children first for the FK).
            SyllabusPiece::query()
                ->where('exam_stream', 'Classical & Jazz')
                ->whereIn('instrument', $this->instruments)
                ->delete();
            SyllabusBook::query()
                ->where('exam_stream', 'Classical & Jazz')
                ->whereIn('instrument', $this->instruments)
                ->delete();

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

            $now = now();
            $rows = [];
            foreach ($pieces as $p) {
                $buyUrl = $p['buy']['amazon'] ?? null;
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
                    'buy_kind' => $p['buy_kind'] ?? 'none',
                    'buy_url' => $buyUrl,
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

        $count = SyllabusPiece::whereIn('instrument', $this->instruments)->count();
        $this->command?->info('EK + Organ seeded: '.$count.' pieces.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(string $file): array
    {
        $path = database_path('seeders/data/'.$file);
        if (! file_exists($path)) {
            throw new \RuntimeException("EK/Organ seed file missing: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
