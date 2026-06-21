<?php

// database/migrations/2026_06_21_120000_create_syllabus_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Canonical "Books" table — single source of truth for both the
        // Syllabus Finder per-piece buy links AND the public /books page.
        Schema::create('syllabus_books', function (Blueprint $table) {
            $table->id();
            $table->string('exam_board')->default('Trinity');
            $table->string('exam_stream');                 // 'Classical & Jazz' | 'Rock & Pop'
            $table->string('instrument');
            $table->string('title');                        // book name
            $table->string('edition')->nullable();          // Standard | Extended | ISBN | null
            $table->string('asin')->nullable();             // Amazon ASIN/ISBN-10
            $table->string('buy_url')->nullable();          // exact Amazon link incl. affiliate tag
            $table->timestamps();

            $table->unique(['exam_stream', 'instrument', 'title', 'edition'], 'syllabus_books_identity_unique');
            $table->index(['exam_stream', 'instrument']);
        });

        // Repertoire pieces / R&P songs. Each piece optionally points at the
        // book that contains it (syllabus_book_id) — that book carries the buy link.
        Schema::create('syllabus_pieces', function (Blueprint $table) {
            $table->id();
            $table->string('exam_board')->default('Trinity');
            $table->string('exam_stream');
            $table->string('instrument');
            $table->string('variant')->nullable();          // Vocals Female/Male at G6-8
            $table->string('grade');                        // Initial..Grade 8
            $table->unsignedSmallInteger('position')->nullable(); // list number on the syllabus
            $table->string('composer');                     // composer (C&J) / artist (R&P)
            $table->string('title');                        // piece / song title
            $table->string('book_title')->nullable();       // raw book name as printed
            $table->string('publisher_code')->nullable();
            $table->foreignId('syllabus_book_id')->nullable()->constrained('syllabus_books')->nullOnDelete();
            $table->boolean('technical_focus')->default(false); // R&P [TF]
            $table->string('voice_range')->nullable();      // R&P vocals
            $table->string('syllabus_from')->nullable();    // e.g. "2023"
            $table->string('buy_kind')->default('none');    // exact | none
            $table->string('buy_url')->nullable();
            $table->string('buy_edition')->nullable();      // Standard | Extended | ISBN
            $table->string('curated_video_url')->nullable(); // the canonical exam performance (set later)
            $table->json('audio')->nullable();              // streaming search links
            $table->json('also_in')->nullable();            // within-Trinity cross-references
            $table->timestamps();

            $table->index(['exam_stream', 'instrument', 'grade']);
            $table->index('composer');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_pieces');
        Schema::dropIfExists('syllabus_books');
    }
};
