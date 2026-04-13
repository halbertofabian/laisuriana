<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_preferencias_matriz_producto_pmp', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_preferencias_matriz_producto_pmp', 'pmp_scl_id')) {
                $table->unsignedBigInteger('pmp_scl_id')->nullable()->after('pmp_prd_id');
                $table->index('pmp_scl_id', 'idx_pmp_scl');
                $table->foreign('pmp_scl_id', 'fk_pmp_scl')->references('scl_id')->on('tbl_sucursales_scl');
            }
        });

        // Garantiza índice base para FK de producto antes de soltar el unique anterior.
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp ADD INDEX idx_pmp_prd_fk (pmp_prd_id)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp DROP INDEX uk_pmp_prd_deleted'); } catch (\Throwable $e) {}
        // Este índice puede estar siendo usado por la FK en algunos motores/configuraciones.
        // Si no se puede soltar, lo dejamos sin afectar operación.
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp DROP INDEX idx_pmp_prd_est_del'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp ADD UNIQUE uk_pmp_prd_scl_deleted (pmp_prd_id, pmp_scl_id, pmp_deleted)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp ADD INDEX idx_pmp_prd_scl_est_del (pmp_prd_id, pmp_scl_id, pmp_estatus, pmp_deleted)'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('tbl_preferencias_matriz_producto_pmp', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_preferencias_matriz_producto_pmp', 'pmp_scl_id')) {
                $table->dropForeign('fk_pmp_scl');
                $table->dropIndex('idx_pmp_scl');
                $table->dropColumn('pmp_scl_id');
            }
        });

        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp DROP INDEX uk_pmp_prd_scl_deleted'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp DROP INDEX idx_pmp_prd_scl_est_del'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp DROP INDEX idx_pmp_prd_fk'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp ADD UNIQUE uk_pmp_prd_deleted (pmp_prd_id, pmp_deleted)'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE tbl_preferencias_matriz_producto_pmp ADD INDEX idx_pmp_prd_est_del (pmp_prd_id, pmp_estatus, pmp_deleted)'); } catch (\Throwable $e) {}
    }
};
