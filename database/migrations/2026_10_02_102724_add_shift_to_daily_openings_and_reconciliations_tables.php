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
        Schema::table('daily_openings', function (Blueprint $table) {
            $table->string('shift', 10)->default('full')->after('opening_date')->index();
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->string('shift', 10)->default('full')->after('reconciliation_date')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_openings', function (Blueprint $table) {
            $table->dropColumn('shift');
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
    }
};
