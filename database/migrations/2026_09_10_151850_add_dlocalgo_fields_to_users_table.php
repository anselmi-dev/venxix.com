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
            $table->string('dlocalgo_subscription_id')->nullable()->after('mercadopago_subscription_id');
            $table->string('dlocalgo_plan_id')->nullable()->after('dlocalgo_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dlocalgo_subscription_id', 'dlocalgo_plan_id']);
        });
    }
};
