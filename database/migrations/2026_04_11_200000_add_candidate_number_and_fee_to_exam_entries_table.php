<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->string('candidate_number')->nullable()->after('id');
            $table->decimal('fee', 8, 2)->nullable()->after('score');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('applicant_name')->nullable()->after('commission_amount');
            $table->string('applicant_email')->nullable()->after('applicant_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn(['candidate_number', 'fee']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['applicant_name', 'applicant_email']);
        });
    }
};
