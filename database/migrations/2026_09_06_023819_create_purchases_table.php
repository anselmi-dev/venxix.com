<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->string('merchant');
            $table->decimal('total_amount', 12, 2);
            $table->unsignedSmallInteger('installment_count');
            $table->string('currency', 3);
            $table->date('first_due_date');
            $table->timestamps();

            $table->index(['user_id', 'card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
