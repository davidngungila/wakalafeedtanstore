<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('device_code', 8)->unique()->after('id');
        });

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        DB::table('devices')->orderBy('id')->each(function ($row) use ($alphabet) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            DB::table('devices')->where('id', $row->id)->update(['device_code' => $code]);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->renameColumn('api_token_hash', 'authorization_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('device_code');
            $table->renameColumn('authorization_token_hash', 'api_token_hash');
        });
    }
};
