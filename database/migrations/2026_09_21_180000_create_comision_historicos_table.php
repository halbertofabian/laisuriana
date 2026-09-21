<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comision_historicos_chv', function (Blueprint $table) {
            $table->bigIncrements('chv_id');
            $table->unsignedBigInteger('chv_scl_id');
            $table->unsignedBigInteger('chv_alm_id');
            $table->unsignedBigInteger('chv_lna_id');
            $table->date('chv_periodo');
            $table->decimal('chv_ventas_netas', 16, 2);
            $table->decimal('chv_autoservicio', 16, 2);
            $table->string('chv_referencia', 250);
            $table->string('chv_observaciones', 1000)->nullable();
            $table->unsignedInteger('chv_version')->default(1);
            $table->unsignedBigInteger('chv_created_by_usr_id');
            $table->unsignedBigInteger('chv_updated_by_usr_id');
            $table->timestamp('chv_created_at')->nullable();
            $table->timestamp('chv_updated_at')->nullable();
            $table->foreign('chv_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('chv_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('chv_lna_id')->references('lna_id')->on('tbl_lineas_lna');
            $table->unique(['chv_scl_id', 'chv_periodo', 'chv_alm_id', 'chv_lna_id'], 'uk_chv_mes_almacen_linea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_comision_historicos_chv');
    }
};
