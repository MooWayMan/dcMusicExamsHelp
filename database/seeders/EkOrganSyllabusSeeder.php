<?php

// database/seeders/EkOrganSyllabusSeeder.php

namespace Database\Seeders;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Database\Seeders\Concerns\SeedsSyllabus;
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
    use SeedsSyllabus;

    /** @var array<int, string> */
    private array $instruments = ['Electronic Keyboard', 'Organ'];

    public function run(): void
    {
        $books = $this->loadSyllabusData('ek_organ_books.json');
        $pieces = $this->loadSyllabusData('ek_organ.json');

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

            $this->insertSyllabusPieces($pieces, $this->createSyllabusBooks($books));
        });

        $count = SyllabusPiece::whereIn('instrument', $this->instruments)->count();
        $this->command?->info('EK + Organ seeded: '.$count.' pieces.');
    }
}
