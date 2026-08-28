<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pos_venta_detalle_pvd', function (Blueprint $table) {
            $table->unsignedBigInteger('pvd_alm_id')->nullable()->after('pvd_psk_id')->index();
            $table->foreign('pvd_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->index(['pvd_alm_id', 'pvd_psv_id'], 'idx_pvd_alm_venta');
        });

        DB::table('tbl_pos_venta_detalle_pvd')->orderBy('pvd_id')->each(function ($detalle): void {
            $almacenId = DB::table('tbl_pos_ventas_psv')
                ->where('psv_id', $detalle->pvd_psv_id)
                ->value('psv_alm_id');

            if ($almacenId) {
                DB::table('tbl_pos_venta_detalle_pvd')
                    ->where('pvd_id', $detalle->pvd_id)
                    ->update(['pvd_alm_id' => $almacenId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pos_venta_detalle_pvd', function (Blueprint $table) {
            $table->dropForeign(['pvd_alm_id']);
            $table->dropIndex('idx_pvd_alm_venta');
            $table->dropIndex(['pvd_alm_id']);
            $table->dropColumn('pvd_alm_id');
        });
    }
};
