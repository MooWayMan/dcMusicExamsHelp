<?php

// database/migrations/2026_09_26_120000_create_piece_plans_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A teacher's plan for one pupil's next exam. Private to the teacher
        // who made it; not linked to exam_entries (a pupil can be planned
        // long before they are entered).
        Schema::create('piece_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pupil_name', 120);
            $table->string('exam_stream');
            $table->string('instrument');
            $table->string('grade');
            $table->date('target_date')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Each thing being prepared for that exam: a piece, technical work or
        // a supporting test, with how ready it is. A piece chosen from the
        // syllabus keeps its label too, so re-seeding the syllabus (which
        // deletes and recreates syllabus_pieces) never empties a plan.
        Schema::create('piece_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_plan_id')->constrained()->cascadeOnDelete();
            $table->string('section', 20);
            $table->foreignId('syllabus_piece_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 200);
            $table->unsignedTinyInteger('percent')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_plan_items');
        Schema::dropIfExists('piece_plans');
    }
};
