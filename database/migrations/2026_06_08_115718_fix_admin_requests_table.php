<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixAdminRequestsTable extends Migration
{
    public function up()
    {
        // Cek apakah kolom 'user_id' sudah ada
        if (!Schema::hasColumn('admin_requests', 'user_id')) {
            Schema::table('admin_requests', function (Blueprint $table) {
                $table->integer('user_id')->nullable();
            });
        }
    }

    public function down()
{
    Schema::table('admin_requests', function (Blueprint $table) {
        $table->dropForeign(['user_id']); // Hapus foreign key
        $table->dropColumn('user_id');   // Hapus kolom
    });
}

}
