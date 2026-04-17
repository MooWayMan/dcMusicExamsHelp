<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_created_by_contact_id_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('created_by_contact_id')
                ->nullable()
                ->after('user_id')
                ->constrained('exam_contacts')
                ->nullOnDelete()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by_contact_id']);
            $table->dropColumn('created_by_contact_id');
        });
    }
};