<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_productos_prd', function (Blueprint $table) {
            $table->bigIncrements('prd_id');
            $table->string('prd_codigo', 40);
            $table->string('prd_nombre', 180);
            $table->text('prd_descripcion')->nullable();
            $table->unsignedBigInteger('prd_mrc_id');
            $table->unsignedBigInteger('prd_lna_id');
            $table->unsignedBigInteger('prd_ctg_id');
            $table->unsignedBigInteger('prd_umd_id');
            $table->string('prd_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('prd_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('prd_updated_by_usr_id')->nullable()->index();
            $table->boolean('prd_deleted')->default(false)->index();
            $table->timestamp('prd_deleted_at')->nullable()->index();
            $table->timestamp('prd_created_at')->nullable();
            $table->timestamp('prd_updated_at')->nullable();

            $table->foreign('prd_mrc_id')->references('mrc_id')->on('tbl_marcas_mrc');
            $table->foreign('prd_lna_id')->references('lna_id')->on('tbl_lineas_lna');
            $table->foreign('prd_ctg_id')->references('ctg_id')->on('tbl_categorias_ctg');
            $table->foreign('prd_umd_id')->references('umd_id')->on('tbl_unidades_medida_umd');
            $table->unique(['prd_codigo', 'prd_deleted'], 'uk_producto_codigo_activo');
            $table->unique(['prd_nombre', 'prd_deleted'], 'uk_producto_nombre_activo');
            $table->index(['prd_deleted', 'prd_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_productos_prd');
    }
};
