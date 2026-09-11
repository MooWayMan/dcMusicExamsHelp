<?php

// database/seeders/SyllabusSeeder.php

namespace Database\Seeders;

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Database\Seeders\Concerns\SeedsSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Syllabus Finder from the curated dataset built in Cowork:
 *   database/seeders/data/books.json     — canonical Books table (Amazon ASINs; the tag
 *                                          lives in config/services.php, not here)
 *   database/seeders/data/syllabus.json  — 910 pieces (Trinity Piano C&J + Rock & Pop), with
 *                                          audio search links, within-Trinity cross-references,
 *                                          and per-piece buy ASINs resolved to the book above.
 *
 * Idempotent: wipes both tables and reloads. Seeding is manual (see dev-rules.md);
 * run with:  php artisan db:seed --class=SyllabusSeeder
 */
class SyllabusSeeder extends Seeder
{
    use SeedsSyllabus;

    public function run(): void
    {
        // Core dataset (Piano C&J + Rock & Pop) plus the C&J Drum Kit and
        // Electronic Keyboard + Organ repertoire, each kept in its own files so the
        // large syllabus.json stays untouched.
        $books = array_merge(
            $this->loadSyllabusData('books.json'),
            $this->loadSyllabusData('drumkit_books.json'),
            $this->loadSyllabusData('ek_organ_books.json'),
        );
        $pieces = array_merge(
            $this->loadSyllabusData('syllabus.json'),
            $this->loadSyllabusData('drumkit.json'),
            $this->loadSyllabusData('ek_organ.json'),
        );

        DB::transaction(function () use ($books, $pieces) {
            // Clear children first (FK), then parents.
            SyllabusPiece::query()->delete();
            SyllabusBook::query()->delete();

            $this->insertSyllabusPieces($pieces, $this->createSyllabusBooks($books));
        });

        $this->command?->info('Syllabus seeded: '.SyllabusBook::count().' books, '.SyllabusPiece::count().' pieces.');
    }
}
