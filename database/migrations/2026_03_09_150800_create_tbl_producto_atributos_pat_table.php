<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_producto_atributos_pat', function (Blueprint $table) {
            $table->bigIncrements('pat_id');
            $table->unsignedBigInteger('pat_prd_id');
            $table->unsignedBigInteger('pat_atr_id');
            $table->string('pat_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('pat_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pat_updated_by_usr_id')->nullable()->index();
            $table->boolean('pat_deleted')->default(false)->index();
            $table->timestamp('pat_deleted_at')->nullable()->index();
            $table->timestamp('pat_created_at')->nullable();
            $table->timestamp('pat_updated_at')->nullable();

            $table->foreign('pat_prd_id')->references('prd_id')->on('tbl_productos_prd');
            $table->foreign('pat_atr_id')->references('atr_id')->on('tbl_atributos_atr');
            $table->unique(['pat_prd_id', 'pat_atr_id', 'pat_deleted'], 'uk_producto_atributo_activo');
            $table->index(['pat_prd_id', 'pat_deleted', 'pat_estatus'], 'idx_pat_prd_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_atributos_pat');
    }
};
