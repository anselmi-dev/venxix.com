<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('provider_invoice_id')->nullable()->unique();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UYU');
            $table->string('status')->default('paid');
            $table->string('hosted_invoice_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
