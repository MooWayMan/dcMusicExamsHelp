<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('teacher_contact_id')
                ->nullable()
                ->after('notes')
                ->constrained('exam_contacts')
                ->nullOnDelete();

            $table->string('teacher_credit_status')->default('unknown')->after('teacher_contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_contact_id');
            $table->dropColumn('teacher_credit_status');
        });
    }
};
