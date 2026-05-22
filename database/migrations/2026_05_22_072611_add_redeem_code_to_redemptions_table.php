<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redemptions', function (Blueprint $table) {

            if (!Schema::hasColumn('redemptions', 'redeem_code')) {
                $table->string('redeem_code')
                      ->nullable()
                      ->after('redeemed_at');
            }

        });
    }

    public function down(): void
    {
        Schema::table('redemptions', function (Blueprint $table) {

            if (Schema::hasColumn('redemptions', 'redeem_code')) {
                $table->dropColumn('redeem_code');
            }

        });
    }
};