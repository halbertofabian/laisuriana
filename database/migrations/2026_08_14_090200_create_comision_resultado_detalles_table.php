<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comision_resultado_detalles_crd', function (Blueprint $table) {
            $table->bigIncrements('crd_id');
            $table->unsignedBigInteger('crd_crs_id');
            $table->unsignedBigInteger('crd_alm_id')->nullable();
            $table->string('crd_almacen_nombre', 160);
            $table->unsignedBigInteger('crd_lna_id')->nullable();
            $table->string('crd_linea_nombre', 160);
            $table->decimal('crd_venta_bruta', 16, 2)->default(0);
            $table->decimal('crd_descuentos', 16, 2)->default(0);
            $table->decimal('crd_devoluciones', 16, 2)->default(0);
            $table->decimal('crd_venta_neta', 16, 2)->default(0);
            $table->timestamp('crd_created_at')->nullable();
            $table->timestamp('crd_updated_at')->nullable();

            $table->foreign('crd_crs_id')
                ->references('crs_id')
                ->on('tbl_comision_resultados_crs')
                ->cascadeOnDelete();
            $table->index(['crd_crs_id', 'crd_alm_id'], 'ix_crd_resultado_almacen');
            $table->index(['crd_crs_id', 'crd_lna_id'], 'ix_crd_resultado_linea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_comision_resultado_detalles_crd');
    }
};
