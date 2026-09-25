<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('two_factor_method', 'email')->update(['two_factor_method' => 'sms']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('two_factor_method', 'sms')->update(['two_factor_method' => 'email']);
    }
};
