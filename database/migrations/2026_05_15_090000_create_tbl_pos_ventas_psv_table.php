<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_ventas_psv', function (Blueprint $table) {
            $table->bigIncrements('psv_id');
            $table->string('psv_folio', 50)->unique();
            $table->unsignedBigInteger('psv_cse_id');
            $table->unsignedBigInteger('psv_caj_id');
            $table->unsignedBigInteger('psv_scl_id');
            $table->unsignedBigInteger('psv_alm_id');
            $table->unsignedBigInteger('psv_usr_id');
            $table->unsignedBigInteger('psv_cli_id')->nullable();
            $table->unsignedBigInteger('psv_pdp_id')->nullable();
            $table->string('psv_estatus', 20)->default('cobrada')->index();
            $table->decimal('psv_subtotal', 14, 2)->default(0);
            $table->decimal('psv_descuento', 14, 2)->default(0);
            $table->decimal('psv_total', 14, 2)->default(0);
            $table->decimal('psv_pagado', 14, 2)->default(0);
            $table->decimal('psv_cambio', 14, 2)->default(0);
            $table->text('psv_notas')->nullable();
            $table->timestamp('psv_fecha_cobro')->nullable()->index();
            $table->unsignedBigInteger('psv_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('psv_updated_by_usr_id')->nullable()->index();
            $table->boolean('psv_deleted')->default(false)->index();
            $table->timestamp('psv_deleted_at')->nullable()->index();
            $table->timestamp('psv_created_at')->nullable();
            $table->timestamp('psv_updated_at')->nullable();

            $table->foreign('psv_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('psv_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('psv_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('psv_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('psv_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('psv_cli_id')->references('cli_id')->on('tbl_clientes_cli');
            $table->foreign('psv_pdp_id')->references('pdp_id')->on('tbl_pedidos_piso_pdp');

            $table->index(['psv_scl_id', 'psv_alm_id', 'psv_fecha_cobro'], 'idx_psv_scl_alm_fecha');
            $table->index(['psv_cse_id', 'psv_estatus'], 'idx_psv_cse_estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_ventas_psv');
    }
};

