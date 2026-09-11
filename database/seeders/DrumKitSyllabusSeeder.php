<?php

// database/seeders/DrumKitSyllabusSeeder.php

namespace Database\Seeders;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Database\Seeders\Concerns\SeedsSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Additive seeder for the Classical & Jazz Drum Kit repertoire
 * (database/seeders/data/drumkit.json + drumkit_books.json).
 *
 * Unlike SyllabusSeeder (which wipes and reloads the WHOLE finder), this only
 * touches Drum Kit rows, so it is SAFE to run on prod without disturbing other
 * pieces or the Top Ten votes that cascade-delete off syllabus_pieces.
 *
 * Idempotent: clears just the C&J Drum Kit books/pieces, then reloads them.
 *
 *   sail artisan db:seed --class=DrumKitSyllabusSeeder
 */
class DrumKitSyllabusSeeder extends Seeder
{
    use SeedsSyllabus;

    public function run(): void
    {
        $books = $this->loadSyllabusData('drumkit_books.json');
        $pieces = $this->loadSyllabusData('drumkit.json');

        DB::transaction(function () use ($books, $pieces) {
            // Remove only the Drum Kit rows (children first for the FK).
            SyllabusPiece::query()
                ->where('exam_stream', 'Classical & Jazz')
                ->where('instrument', 'Drum Kit')
                ->delete();
            SyllabusBook::query()
                ->where('exam_stream', 'Classical & Jazz')
                ->where('instrument', 'Drum Kit')
                ->delete();

            $this->insertSyllabusPieces($pieces, $this->createSyllabusBooks($books));
        });

        $this->command?->info('Drum Kit seeded: '.SyllabusPiece::where('instrument', 'Drum Kit')->count().' pieces.');
    }
}
