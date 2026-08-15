<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comision_periodo_lineas_cpl', function (Blueprint $table) {
            $table->bigIncrements('cpl_id');
            $table->unsignedBigInteger('cpl_cpe_id');
            $table->unsignedBigInteger('cpl_cgr_id');
            $table->unsignedBigInteger('cpl_lna_id');
            $table->timestamp('cpl_created_at')->nullable();
            $table->timestamp('cpl_updated_at')->nullable();

            $table->foreign('cpl_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('cpl_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->foreign('cpl_lna_id')->references('lna_id')->on('tbl_lineas_lna');
            $table->unique(['cpl_cpe_id', 'cpl_lna_id'], 'uk_cpl_periodo_linea');
        });

        Schema::create('tbl_comision_periodo_vendedores_cpv', function (Blueprint $table) {
            $table->bigIncrements('cpv_id');
            $table->unsignedBigInteger('cpv_cpe_id');
            $table->unsignedBigInteger('cpv_cve_id');
            $table->unsignedBigInteger('cpv_cgr_id');
            $table->string('cpv_numero_vendedor', 40);
            $table->timestamp('cpv_created_at')->nullable();
            $table->timestamp('cpv_updated_at')->nullable();

            $table->foreign('cpv_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('cpv_cve_id')->references('cve_id')->on('tbl_comision_vendedores_cve');
            $table->foreign('cpv_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->unique(['cpv_cpe_id', 'cpv_cve_id'], 'uk_cpv_periodo_vendedor');
            $table->unique(['cpv_cpe_id', 'cpv_numero_vendedor'], 'uk_cpv_periodo_numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_comision_periodo_vendedores_cpv');
        Schema::dropIfExists('tbl_comision_periodo_lineas_cpl');
    }
};
