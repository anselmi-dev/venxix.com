<?php

use App\Models\StatementImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('statement_imports', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');
        });

        StatementImport::query()->whereNull('ulid')->each(function (StatementImport $import): void {
            $import->forceFill(['ulid' => (string) Str::ulid()])->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statement_imports', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
