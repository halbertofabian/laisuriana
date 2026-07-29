<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_creditos_cambio_pcc', function (Blueprint $table) {
            $table->bigIncrements('pcc_id');
            $table->string('pcc_folio', 50)->unique();
            $table->unsignedBigInteger('pcc_cse_id')->nullable();
            $table->unsignedBigInteger('pcc_caj_id')->nullable();
            $table->unsignedBigInteger('pcc_scl_id');
            $table->unsignedBigInteger('pcc_alm_id');
            $table->unsignedBigInteger('pcc_usr_id');
            $table->unsignedBigInteger('pcc_cli_id')->nullable();
            $table->unsignedBigInteger('pcc_psv_origen_id');
            $table->string('pcc_estatus', 20)->default('disponible');
            $table->decimal('pcc_total_credito', 14, 2)->default(0);
            $table->decimal('pcc_saldo_disponible', 14, 2)->default(0);
            $table->text('pcc_notas')->nullable();
            $table->timestamp('pcc_fecha_generado')->nullable();
            $table->timestamp('pcc_cancelado_at')->nullable();
            $table->unsignedBigInteger('pcc_cancelado_by_usr_id')->nullable();
            $table->string('pcc_cancelacion_motivo', 500)->nullable();
            $table->unsignedBigInteger('pcc_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('pcc_updated_by_usr_id')->nullable();
            $table->boolean('pcc_deleted')->default(false);
            $table->timestamp('pcc_deleted_at')->nullable();
            $table->timestamp('pcc_created_at')->nullable();
            $table->timestamp('pcc_updated_at')->nullable();

            $table->foreign('pcc_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('pcc_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('pcc_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('pcc_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('pcc_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('pcc_cli_id')->references('cli_id')->on('tbl_clientes_cli');
            $table->foreign('pcc_psv_origen_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pcc_cancelado_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');

            $table->index(['pcc_scl_id', 'pcc_estatus'], 'idx_pcc_scl_estatus');
            $table->index('pcc_cancelado_by_usr_id', 'idx_pcc_cancel_usr');
            $table->index('pcc_created_by_usr_id', 'idx_pcc_created_usr');
            $table->index('pcc_updated_by_usr_id', 'idx_pcc_updated_usr');
            $table->index('pcc_estatus', 'idx_pcc_estatus');
            $table->index('pcc_fecha_generado', 'idx_pcc_fecha_gen');
            $table->index('pcc_cancelado_at', 'idx_pcc_cancelado_at');
            $table->index('pcc_deleted', 'idx_pcc_deleted');
            $table->index('pcc_deleted_at', 'idx_pcc_deleted_at');
        });

        Schema::create('tbl_pos_creditos_cambio_detalle_pcdv', function (Blueprint $table) {
            $table->bigIncrements('pcdv_id');
            $table->unsignedBigInteger('pcdv_pcc_id');
            $table->unsignedBigInteger('pcdv_psv_origen_id');
            $table->unsignedBigInteger('pcdv_pvd_origen_id');
            $table->unsignedBigInteger('pcdv_psk_id');
            $table->unsignedBigInteger('pcdv_alm_id');
            $table->decimal('pcdv_cantidad', 14, 2);
            $table->decimal('pcdv_precio_unitario', 14, 2)->default(0);
            $table->decimal('pcdv_importe_credito', 14, 2)->default(0);
            $table->string('pcdv_condicion', 20)->default('reventa');
            $table->unsignedBigInteger('pcdv_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('pcdv_updated_by_usr_id')->nullable();
            $table->boolean('pcdv_deleted')->default(false);
            $table->timestamp('pcdv_deleted_at')->nullable();
            $table->timestamp('pcdv_created_at')->nullable();
            $table->timestamp('pcdv_updated_at')->nullable();

            $table->foreign('pcdv_pcc_id')->references('pcc_id')->on('tbl_pos_creditos_cambio_pcc');
            $table->foreign('pcdv_psv_origen_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pcdv_pvd_origen_id')->references('pvd_id')->on('tbl_pos_venta_detalle_pvd');
            $table->foreign('pcdv_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('pcdv_alm_id')->references('alm_id')->on('tbl_almacenes_alm');

            $table->index(['pcdv_pcc_id', 'pcdv_deleted'], 'idx_pcdv_pcc_del');
            $table->index(['pcdv_pvd_origen_id', 'pcdv_deleted'], 'idx_pcdv_pvd_origen_del');
            $table->index('pcdv_created_by_usr_id', 'idx_pcdv_created_usr');
            $table->index('pcdv_updated_by_usr_id', 'idx_pcdv_updated_usr');
            $table->index('pcdv_condicion', 'idx_pcdv_condicion');
            $table->index('pcdv_deleted', 'idx_pcdv_deleted');
            $table->index('pcdv_deleted_at', 'idx_pcdv_deleted_at');
        });

        Schema::create('tbl_pos_creditos_cambio_aplicaciones_pca', function (Blueprint $table) {
            $table->bigIncrements('pca_id');
            $table->unsignedBigInteger('pca_pcc_id');
            $table->unsignedBigInteger('pca_psv_id');
            $table->unsignedBigInteger('pca_cse_id')->nullable();
            $table->unsignedBigInteger('pca_caj_id')->nullable();
            $table->unsignedBigInteger('pca_scl_id');
            $table->unsignedBigInteger('pca_usr_id');
            $table->decimal('pca_monto_aplicado', 14, 2)->default(0);
            $table->timestamp('pca_fecha_aplicacion')->nullable();
            $table->unsignedBigInteger('pca_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('pca_updated_by_usr_id')->nullable();
            $table->boolean('pca_deleted')->default(false);
            $table->timestamp('pca_deleted_at')->nullable();
            $table->timestamp('pca_created_at')->nullable();
            $table->timestamp('pca_updated_at')->nullable();

            $table->foreign('pca_pcc_id')->references('pcc_id')->on('tbl_pos_creditos_cambio_pcc');
            $table->foreign('pca_psv_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pca_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('pca_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('pca_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('pca_usr_id')->references('usr_id')->on('tbl_usuarios_usr');

            $table->index(['pca_pcc_id', 'pca_deleted'], 'idx_pca_pcc_del');
            $table->index(['pca_psv_id', 'pca_deleted'], 'idx_pca_psv_del');
            $table->index('pca_created_by_usr_id', 'idx_pca_created_usr');
            $table->index('pca_updated_by_usr_id', 'idx_pca_updated_usr');
            $table->index('pca_fecha_aplicacion', 'idx_pca_fecha_apl');
            $table->index('pca_deleted', 'idx_pca_deleted');
            $table->index('pca_deleted_at', 'idx_pca_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_creditos_cambio_aplicaciones_pca');
        Schema::dropIfExists('tbl_pos_creditos_cambio_detalle_pcdv');
        Schema::dropIfExists('tbl_pos_creditos_cambio_pcc');
    }
};
