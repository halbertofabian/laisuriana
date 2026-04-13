<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_movimientos_inventario_min', function (Blueprint $table) {
            $table->bigIncrements('min_id');
            $table->string('min_folio', 50)->unique();
            $table->unsignedBigInteger('min_tmi_id');
            $table->unsignedBigInteger('min_psk_id');
            $table->unsignedBigInteger('min_scl_id');
            $table->unsignedBigInteger('min_alm_id');
            $table->unsignedBigInteger('min_mtv_id')->nullable();
            $table->unsignedBigInteger('min_origen_min_id')->nullable();
            $table->unsignedBigInteger('min_reversa_de_min_id')->nullable();
            $table->string('min_documento_tipo', 40)->index();
            $table->string('min_documento_referencia', 120)->nullable()->index();
            $table->decimal('min_cantidad', 14, 2);
            $table->smallInteger('min_signo');
            $table->decimal('min_existencia_antes', 14, 2);
            $table->decimal('min_existencia_despues', 14, 2);
            $table->text('min_motivo_texto')->nullable();
            $table->string('min_estatus', 20)->default('activo')->index();
            $table->boolean('min_es_reversa')->default(false)->index();
            $table->timestamp('min_fecha_movimiento')->index();
            $table->timestamp('min_cancelado_at')->nullable()->index();
            $table->unsignedBigInteger('min_cancelado_by_usr_id')->nullable()->index();
            $table->text('min_cancelacion_motivo')->nullable();
            $table->unsignedBigInteger('min_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('min_updated_by_usr_id')->nullable()->index();
            $table->boolean('min_deleted')->default(false)->index();
            $table->timestamp('min_deleted_at')->nullable()->index();
            $table->timestamp('min_created_at')->nullable();
            $table->timestamp('min_updated_at')->nullable();

            $table->foreign('min_tmi_id')->references('tmi_id')->on('tbl_tipos_movimiento_inventario_tmi');
            $table->foreign('min_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('min_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('min_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('min_mtv_id')->references('mtv_id')->on('tbl_motivos_mtv');
            $table->foreign('min_origen_min_id')->references('min_id')->on('tbl_movimientos_inventario_min');
            $table->foreign('min_reversa_de_min_id')->references('min_id')->on('tbl_movimientos_inventario_min');

            $table->unique(['min_reversa_de_min_id', 'min_deleted'], 'uk_min_reversa_del');
            $table->index(['min_psk_id', 'min_scl_id', 'min_alm_id', 'min_fecha_movimiento'], 'idx_min_kardex_01');
            $table->index(['min_scl_id', 'min_alm_id', 'min_documento_tipo', 'min_fecha_movimiento'], 'idx_min_kardex_02');
            $table->index(['min_estatus', 'min_deleted', 'min_fecha_movimiento'], 'idx_min_est_del_fec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_movimientos_inventario_min');
    }
};
