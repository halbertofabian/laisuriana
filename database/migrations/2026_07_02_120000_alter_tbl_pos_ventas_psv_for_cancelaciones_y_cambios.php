<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pos_ventas_psv', function (Blueprint $table) {
            $table->string('psv_tipo_operacion', 20)
                ->default('venta')
                ->after('psv_pdp_id')
                ->index();
            $table->unsignedBigInteger('psv_venta_origen_id')
                ->nullable()
                ->after('psv_tipo_operacion');
            $table->decimal('psv_credito_cambio', 14, 2)
                ->default(0)
                ->after('psv_descuento');
            $table->timestamp('psv_cancelado_at')
                ->nullable()
                ->after('psv_fecha_cobro')
                ->index();
            $table->unsignedBigInteger('psv_cancelado_by_usr_id')
                ->nullable()
                ->after('psv_cancelado_at')
                ->index();
            $table->string('psv_cancelacion_motivo', 500)
                ->nullable()
                ->after('psv_cancelado_by_usr_id');

            $table->foreign('psv_venta_origen_id')
                ->references('psv_id')
                ->on('tbl_pos_ventas_psv');
            $table->foreign('psv_cancelado_by_usr_id')
                ->references('usr_id')
                ->on('tbl_usuarios_usr');
        });

        Schema::create('tbl_pos_cambios_detalle_pcd', function (Blueprint $table) {
            $table->bigIncrements('pcd_id');
            $table->unsignedBigInteger('pcd_psv_id');
            $table->unsignedBigInteger('pcd_psv_origen_id');
            $table->unsignedBigInteger('pcd_pvd_origen_id');
            $table->unsignedBigInteger('pcd_psk_id');
            $table->unsignedBigInteger('pcd_alm_id');
            $table->decimal('pcd_cantidad', 14, 2);
            $table->decimal('pcd_precio_unitario', 14, 2)->default(0);
            $table->decimal('pcd_importe_credito', 14, 2)->default(0);
            $table->string('pcd_condicion', 20)->default('reventa')->index();
            $table->unsignedBigInteger('pcd_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pcd_updated_by_usr_id')->nullable()->index();
            $table->boolean('pcd_deleted')->default(false)->index();
            $table->timestamp('pcd_deleted_at')->nullable()->index();
            $table->timestamp('pcd_created_at')->nullable();
            $table->timestamp('pcd_updated_at')->nullable();

            $table->foreign('pcd_psv_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pcd_psv_origen_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pcd_pvd_origen_id')->references('pvd_id')->on('tbl_pos_venta_detalle_pvd');
            $table->foreign('pcd_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('pcd_alm_id')->references('alm_id')->on('tbl_almacenes_alm');

            $table->index(['pcd_psv_id', 'pcd_deleted'], 'idx_pcd_psv_del');
            $table->index(['pcd_pvd_origen_id', 'pcd_deleted'], 'idx_pcd_pvd_origen_del');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_cambios_detalle_pcd');

        Schema::table('tbl_pos_ventas_psv', function (Blueprint $table) {
            $table->dropForeign(['psv_venta_origen_id']);
            $table->dropForeign(['psv_cancelado_by_usr_id']);
            $table->dropColumn([
                'psv_tipo_operacion',
                'psv_venta_origen_id',
                'psv_credito_cambio',
                'psv_cancelado_at',
                'psv_cancelado_by_usr_id',
                'psv_cancelacion_motivo',
            ]);
        });
    }
};
