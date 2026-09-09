<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statement_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('merchant')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('installment_count')->nullable();
            $table->string('currency', 3)->nullable();
            $table->date('first_due_date')->nullable();
            $table->json('validation_errors')->nullable();
            $table->boolean('selected')->default(true);
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['statement_import_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statement_import_rows');
    }
};
