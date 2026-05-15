<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_venta_detalle_pvd', function (Blueprint $table) {
            $table->bigIncrements('pvd_id');
            $table->unsignedBigInteger('pvd_psv_id');
            $table->unsignedBigInteger('pvd_psk_id');
            $table->decimal('pvd_cantidad', 14, 2);
            $table->decimal('pvd_precio_unitario', 14, 2)->default(0);
            $table->decimal('pvd_descuento_porcentaje', 7, 2)->default(0);
            $table->decimal('pvd_descuento_importe', 14, 2)->default(0);
            $table->decimal('pvd_importe', 14, 2)->default(0);
            $table->unsignedBigInteger('pvd_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pvd_updated_by_usr_id')->nullable()->index();
            $table->boolean('pvd_deleted')->default(false)->index();
            $table->timestamp('pvd_deleted_at')->nullable()->index();
            $table->timestamp('pvd_created_at')->nullable();
            $table->timestamp('pvd_updated_at')->nullable();

            $table->foreign('pvd_psv_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('pvd_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');

            $table->index(['pvd_psv_id', 'pvd_deleted'], 'idx_pvd_psv_del');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_venta_detalle_pvd');
    }
};

