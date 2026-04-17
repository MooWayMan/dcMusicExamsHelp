<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_order_contacts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('exam_contact_id')
                ->constrained('exam_contacts')
                ->cascadeOnDelete();

            $table->string('role_in_order');
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['order_id', 'exam_contact_id', 'role_in_order'],
                'order_contact_role_unique'
            );

            $table->index(['order_id', 'role_in_order']);
            $table->index(['exam_contact_id', 'role_in_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_contacts');
    }
};