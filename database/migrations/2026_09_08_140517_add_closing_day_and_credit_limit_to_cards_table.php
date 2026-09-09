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
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedTinyInteger('closing_day')->nullable()->after('type');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('closing_day');
            $table->string('credit_limit_currency')->nullable()->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['closing_day', 'credit_limit', 'credit_limit_currency']);
        });
    }
};
