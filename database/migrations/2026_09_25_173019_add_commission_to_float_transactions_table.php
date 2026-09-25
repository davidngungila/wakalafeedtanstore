<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('float_transactions', function (Blueprint $table) {
            $table->decimal('commission', 15, 2)->default(0)->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('float_transactions', function (Blueprint $table) {
            $table->dropColumn('commission');
        });
    }
};
