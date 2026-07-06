<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chargers', function (Blueprint $table) {
            // Cek kolom yang sudah ada dulu
            if (!Schema::hasColumn('chargers', 'stasiun_pengisian_id')) {
                $table->foreignId('stasiun_pengisian_id')
                      ->after('id')
                      ->constrained('stasiun_pengisian')
                      ->onDelete('cascade');
            }
            if (!Schema::hasColumn('chargers', 'kode_unit')) {
                $table->string('kode_unit', 50)->after('stasiun_pengisian_id');
            }
            if (!Schema::hasColumn('chargers', 'tipe')) {
                $table->enum('tipe', ['AC', 'DC', 'Fast Charging'])->default('AC')->after('kode_unit');
            }
            if (!Schema::hasColumn('chargers', 'daya_kw')) {
                $table->decimal('daya_kw', 8, 2)->default(0)->after('tipe');
            }
            if (!Schema::hasColumn('chargers', 'status')) {
                $table->enum('status', ['Available', 'In Use', 'Maintenance', 'Offline'])->default('Available')->after('daya_kw');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chargers', function (Blueprint $table) {
            $table->dropForeign(['stasiun_pengisian_id']);
            $table->dropColumn(['stasiun_pengisian_id', 'kode_unit', 'tipe', 'daya_kw', 'status']);
        });
    }
};