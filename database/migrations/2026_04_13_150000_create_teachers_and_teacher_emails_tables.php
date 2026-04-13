<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standalone teachers table — NOT the users table
        // A teacher is someone linked to exam entries, they may never log in
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('teacher'); // teacher, parent, self
            $table->string('phone')->nullable();
            $table->string('trinity_applicant_id')->nullable(); // Trinity's applicant ID for matching
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional link if they also log in
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Multiple emails per teacher (some use different emails for different things)
        Schema::create('teacher_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('label')->nullable(); // e.g. 'Trinity TOL', 'personal', 'school'
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['teacher_id', 'email']);
        });

        // Link exam entries to the teachers table
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('teacher_name')->constrained()->nullOnDelete();
        });

        // Link teachers to schools (many to many)
        Schema::create('school_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });

        Schema::dropIfExists('school_teacher');
        Schema::dropIfExists('teacher_emails');
        Schema::dropIfExists('teachers');
    }
};
