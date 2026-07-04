<?php

// database/migrations/2026_07_04_120000_create_piece_votes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teacher votes that drive the public "Top Ten" chart. Each row is one
        // teacher's opinion of one syllabus piece: a 1-4 star rating and/or a
        // running total of how many of their students have used it in an exam.
        // One row per (user, piece) — re-voting updates the same row.
        Schema::create('piece_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('syllabus_piece_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();   // 1..4 (1 dislike -> 4 love)
            $table->unsignedInteger('used_count')->default(0);   // this teacher's students who used it in an exam
            $table->timestamps();

            $table->unique(['user_id', 'syllabus_piece_id'], 'piece_votes_user_piece_unique');
            $table->index('syllabus_piece_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_votes');
    }
};
