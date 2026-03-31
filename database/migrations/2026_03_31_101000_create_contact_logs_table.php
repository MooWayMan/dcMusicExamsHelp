<?php

// database/migrations/2026_03_31_101000_create_contact_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks every interaction Paul has had with a teacher.
     * Builds a timeline: emails, phone calls, face-to-face meetings.
     */
    public function up(): void
    {
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // teacher
            $table->string('contact_type'); // email, phone, face_to_face, other
            $table->string('direction')->default('inbound'); // inbound, outbound
            $table->string('subject')->nullable();
            $table->text('summary')->nullable(); // what was discussed
            $table->date('contacted_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
    }
};
