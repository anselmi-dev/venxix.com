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
        Schema::table('users', function (Blueprint $table) {
            $table->string('billing_provider')->nullable()->after('stripe_subscription_id');
            $table->string('mercadopago_subscription_id')->nullable()->after('billing_provider');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('paid_at');
            $table->index(['status', 'due_date', 'reminder_sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_date', 'reminder_sent_at']);
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['billing_provider', 'mercadopago_subscription_id']);
        });
    }
};
