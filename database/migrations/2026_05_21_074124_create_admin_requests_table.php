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
        Schema::create('admin_requests', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel users
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Kolom tambahan untuk mengakomodasi form Flutter
            $table->string('venue_name'); // Untuk Nama Venue / Alamat Venue
            $table->string('phone');      // Untuk Nomor HP

            // ENUM disesuaikan dengan kapitalisasi dari Flutter ('Pending')
            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            // Kolom Alasan Pengajuan
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_requests');
    }
};