<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prize_draws', function (Blueprint $table) {
            $table->id();
            $table->string('type');              // 'student' or 'teacher'
            $table->integer('quarter');
            $table->integer('year');
            $table->string('winner_name');
            $table->string('winner_instrument')->nullable();  // student draws
            $table->string('winner_grade')->nullable();       // student draws
            $table->string('winner_teacher')->nullable();     // student draws — which teacher
            $table->integer('winner_entries')->nullable();     // teacher draws — entry count
            $table->integer('total_tickets');
            $table->json('all_eligible')->nullable();          // snapshot of everyone in the draw
            $table->unsignedBigInteger('drawn_by');            // admin user who ran it
            $table->timestamps();

            $table->foreign('drawn_by')->references('id')->on('users');
            // Only one real draw per type per quarter
            $table->unique(['type', 'quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_draws');
    }
};
