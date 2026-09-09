<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'due_date']);
            $table->unique(['purchase_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
