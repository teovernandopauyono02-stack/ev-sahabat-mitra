<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah dukungan Today Yield (kWh harian) untuk API koneksi.
     *
     * - api_connections: 2 field opsional supaya user bisa input URL endpoint
     *   kedua yang return Today Yield (misal HopeWind getPowerPlantStatistics)
     * - api_synclogs: 1 field untuk simpan snapshot Today Yield saat sync
     */
    public function up(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            if (!Schema::hasColumn('api_connections', 'daily_yield_url')) {
                $table->text('daily_yield_url')->nullable()->after('api_url');
            }
            if (!Schema::hasColumn('api_connections', 'response_path_daily_yield')) {
                $table->string('response_path_daily_yield')->nullable()->after('response_path_power');
            }
        });

        Schema::table('api_synclogs', function (Blueprint $table) {
            if (!Schema::hasColumn('api_synclogs', 'daily_yield_kwh')) {
                $table->decimal('daily_yield_kwh', 10, 2)->nullable()->after('power_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            if (Schema::hasColumn('api_connections', 'daily_yield_url')) {
                $table->dropColumn('daily_yield_url');
            }
            if (Schema::hasColumn('api_connections', 'response_path_daily_yield')) {
                $table->dropColumn('response_path_daily_yield');
            }
        });

        Schema::table('api_synclogs', function (Blueprint $table) {
            if (Schema::hasColumn('api_synclogs', 'daily_yield_kwh')) {
                $table->dropColumn('daily_yield_kwh');
            }
        });
    }
};
