<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {

            $table->unsignedInteger('stock')
                  ->default(0)
                  ->after('points_required');

            $table->date('expired_at')
                  ->nullable()
                  ->after('status');

        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {

            $table->dropColumn([
                'stock',
                'expired_at'
            ]);

        });
    }
};