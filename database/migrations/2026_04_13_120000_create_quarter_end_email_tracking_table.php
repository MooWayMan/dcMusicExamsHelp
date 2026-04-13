<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarter_end_email_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_name');
            $table->integer('quarter');
            $table->integer('year');
            $table->boolean('email_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_name', 'quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarter_end_email_tracking');
    }
};
