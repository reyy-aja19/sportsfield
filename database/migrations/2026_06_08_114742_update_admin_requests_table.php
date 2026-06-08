<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('admin_requests', 'venue_name')) {
            Schema::table('admin_requests', function (Blueprint $table) {
                $table->string('venue_name')->nullable();
            });
        }

        if (!Schema::hasColumn('admin_requests', 'phone')) {
            Schema::table('admin_requests', function (Blueprint $table) {
                $table->string('phone')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            if (Schema::hasColumn('admin_requests', 'venue_name')) {
                $table->dropColumn('venue_name');
            }

            if (Schema::hasColumn('admin_requests', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};