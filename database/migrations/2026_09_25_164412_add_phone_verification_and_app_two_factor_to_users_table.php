<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('two_factor_app_enabled')->default(false)->after('two_factor_method');
        });

        DB::table('users')->where('two_factor_method', 'app')->update(['two_factor_app_enabled' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_verified_at', 'two_factor_app_enabled']);
        });
    }
};
