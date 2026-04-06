<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->boolean('show_full_name')->default(false)->after('school_name');
        });
    }

    public function down(): void
    {
        Schema::table('exam_entries', function (Blueprint $table) {
            $table->dropColumn('show_full_name');
        });
    }
};
