<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->foreignId('teacher_contact_id')
                ->nullable()
                ->after('teacher_name')
                ->constrained('exam_contacts')
                ->nullOnDelete();

            $table->string('teacher_credit_status')->default('unknown')->after('teacher_contact_id');
            $table->string('source')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_contact_id');
            $table->dropColumn(['teacher_credit_status', 'source']);
        });
    }
};
