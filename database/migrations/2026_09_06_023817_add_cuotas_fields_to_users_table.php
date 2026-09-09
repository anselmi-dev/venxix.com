<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_currency', 3)->default('UYU')->after('password');
            $table->boolean('show_both_currencies')->default(true)->after('preferred_currency');
            $table->decimal('usd_uyu_rate', 12, 4)->nullable()->after('show_both_currencies');
            $table->timestamp('onboarding_completed_at')->nullable()->after('usd_uyu_rate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_currency',
                'show_both_currencies',
                'usd_uyu_rate',
                'onboarding_completed_at',
            ]);
        });
    }
};
