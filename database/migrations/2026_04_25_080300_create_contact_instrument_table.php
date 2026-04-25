<?php

// database/migrations/2026_04_25_080300_create_contact_instrument_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Mirrors `teacher_instrument` but for exam_contacts.
     */
    public function up(): void
    {
        Schema::create('contact_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['exam_contact_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_instrument');
    }
};
