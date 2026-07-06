<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stasiun_pengisian')) {
            Schema::create('stasiun_pengisian', function (Blueprint $table) {
                $table->id();
                $table->string('nama_stasiun', 100);
                $table->string('lokasi', 255);
                $table->enum('status', ['Active', 'Maintenance', 'Inactive'])->default('Active');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stasiun_pengisian');
    }
};
