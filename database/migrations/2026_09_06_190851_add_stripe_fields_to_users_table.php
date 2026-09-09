<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->after('onboarding_completed_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_id');
            $table->string('pm_brand')->nullable()->after('stripe_subscription_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_brand');
            $table->unsignedTinyInteger('pm_exp_month')->nullable()->after('pm_last_four');
            $table->unsignedSmallInteger('pm_exp_year')->nullable()->after('pm_exp_month');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'stripe_subscription_id',
                'pm_brand',
                'pm_last_four',
                'pm_exp_month',
                'pm_exp_year',
            ]);
        });
    }
};
