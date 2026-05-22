<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redemptions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('reward_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('redeemed_at')
                ->nullable();

            // kode redeem
            $table->string('redeem_code')
                ->nullable();

            // lokasi file qr
            $table->string('qr_code')
                ->nullable();

            $table->string('status')
                ->default('Pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};