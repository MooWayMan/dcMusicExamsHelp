<?php

// database/migrations/2026_09_26_170000_create_piece_plan_ratings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A pupil's mark out of 10 for a syllabus piece they have heard, on
        // the Piece tracker's "Choose pieces" list. Only marked pieces have a
        // row. Whether they are trying a piece is NOT stored here: that is
        // the piece being in the plan's Pieces rows (piece_plan_items).
        Schema::create('piece_plan_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('syllabus_piece_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['piece_plan_id', 'syllabus_piece_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_plan_ratings');
    }
};
