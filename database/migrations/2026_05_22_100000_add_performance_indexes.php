<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah index untuk optimasi performa query.
 * Mencegah full table scan pada tabel besar.
 */
return new class extends Migration {
    public function up(): void
    {
        // Index untuk riwayat_pengisian — query filter stasiun + waktu
        Schema::table('riwayat_pengisian', function (Blueprint $table) {
            if (!$this->indexExists('riwayat_pengisian', 'idx_riwayat_stasiun_waktu')) {
                $table->index(['stasiun_pengisian_id', 'waktu_mulai'], 'idx_riwayat_stasiun_waktu');
            }
            if (!$this->indexExists('riwayat_pengisian', 'idx_riwayat_waktu')) {
                $table->index('waktu_mulai', 'idx_riwayat_waktu');
            }
            if (!$this->indexExists('riwayat_pengisian', 'idx_riwayat_energi')) {
                $table->index('energi_kwh', 'idx_riwayat_energi');
            }
        });

        // Index untuk audit_logs — query filter module + severity + created_at
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!$this->indexExists('audit_logs', 'idx_audit_module')) {
                $table->index('module', 'idx_audit_module');
            }
            if (!$this->indexExists('audit_logs', 'idx_audit_severity')) {
                $table->index('severity', 'idx_audit_severity');
            }
            if (!$this->indexExists('audit_logs', 'idx_audit_created')) {
                $table->index('created_at', 'idx_audit_created');
            }
        });

        // Index untuk login_attempts — query filter IP + status + created_at
        Schema::table('login_attempts', function (Blueprint $table) {
            if (!$this->indexExists('login_attempts', 'idx_login_ip_status')) {
                $table->index(['ip_address', 'status', 'created_at'], 'idx_login_ip_status');
            }
            if (!$this->indexExists('login_attempts', 'idx_login_email_status')) {
                $table->index(['email', 'status', 'created_at'], 'idx_login_email_status');
            }
        });

        // Index untuk api_synclogs — query filter connection + status + created_at
        Schema::table('api_synclogs', function (Blueprint $table) {
            if (!$this->indexExists('api_synclogs', 'idx_sync_conn_status')) {
                $table->index(['api_connection_id', 'status', 'created_at'], 'idx_sync_conn_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_pengisian', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_riwayat_stasiun_waktu');
            $table->dropIndexIfExists('idx_riwayat_waktu');
            $table->dropIndexIfExists('idx_riwayat_energi');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_audit_module');
            $table->dropIndexIfExists('idx_audit_severity');
            $table->dropIndexIfExists('idx_audit_created');
        });
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_login_ip_status');
            $table->dropIndexIfExists('idx_login_email_status');
        });
        Schema::table('api_synclogs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_sync_conn_status');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};
