<?php
// database/migrations/2026_04_14_202814_create_exam_contacts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();

            $table->string('role')->nullable()->index();
            $table->string('source')->nullable()->index();

            $table->text('notes')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_contacts');
    }
};