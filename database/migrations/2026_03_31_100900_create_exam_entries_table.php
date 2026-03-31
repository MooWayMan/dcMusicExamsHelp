<?php

// database/migrations/2026_03_31_100900_create_exam_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Individual student exam entries within an order.
     * An order may have multiple candidates — this tracks each one.
     */
    public function up(): void
    {
        Schema::create('exam_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('instrument_id')->nullable()->constrained()->onDelete('set null');
            $table->string('grade')->nullable(); // Initial, Grade 1-8, etc.
            $table->string('subject_area')->nullable(); // Music, Rock and Pop
            $table->string('delivery_method')->nullable(); // Digital, F2F
            $table->string('result')->nullable(); // Pass, Merit, Distinction, etc.
            $table->date('exam_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_entries');
    }
};
