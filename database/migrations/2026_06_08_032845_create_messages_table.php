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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        // Menghubungkan chat ke pertandingan mana
        $table->foreignId('open_match_id')->constrained('open_matches')->onDelete('cascade');
        // Siapa yang mengirim chat
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        // Isi pesan teks
        $table->text('message');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
