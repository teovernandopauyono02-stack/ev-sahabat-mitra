<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('riwayat_pengisian')) {
            Schema::create('riwayat_pengisian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stasiun_pengisian_id')
                      ->constrained('stasiun_pengisian')
                      ->onDelete('cascade');
                $table->decimal('energi_kwh', 8, 2);
                $table->dateTime('waktu_mulai');
                $table->dateTime('waktu_selesai')->nullable();
                $table->string('lokasi')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengisian');
    }
};
