<?php

// database/migrations/2026_11_17_220000_create_payment_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Payment documents (17 Nov 2026). Two kinds:
//   payment — a payment advice: Paul paid someone on Trinity's behalf
//             (venue rent, a steward, piano tuning).
//   receipt — Paul received money for exam fees (e.g. a teacher paying for
//             their candidates) and the document shows what it covered and
//             the balance either way.
// Documents only: nothing here feeds commission or the money reports.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('number', 32)->unique();
            $table->date('issued_on');
            $table->string('title')->nullable();
            $table->string('to_name');
            $table->text('to_detail')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 32)->nullable();
            $table->date('paid_on')->nullable();
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('description');
            $table->date('line_date')->nullable();
            $table->string('detail')->nullable();
            $table->decimal('quantity', 8, 2);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_document_lines');
        Schema::dropIfExists('payment_documents');
    }
};
