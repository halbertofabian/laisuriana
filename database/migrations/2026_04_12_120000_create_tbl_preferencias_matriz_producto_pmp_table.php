<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_preferencias_matriz_producto_pmp')) {
            return;
        }

        Schema::create('tbl_preferencias_matriz_producto_pmp', function (Blueprint $table) {
            $table->bigIncrements('pmp_id');
            $table->unsignedBigInteger('pmp_prd_id');
            $table->unsignedBigInteger('pmp_atr_dominante_id');
            $table->string('pmp_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('pmp_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pmp_updated_by_usr_id')->nullable()->index();
            $table->boolean('pmp_deleted')->default(false)->index();
            $table->timestamp('pmp_deleted_at')->nullable()->index();
            $table->timestamp('pmp_created_at')->nullable();
            $table->timestamp('pmp_updated_at')->nullable();

            $table->foreign('pmp_prd_id', 'fk_pmp_prd')->references('prd_id')->on('tbl_productos_prd');
            $table->foreign('pmp_atr_dominante_id', 'fk_pmp_atr_dom')->references('atr_id')->on('tbl_atributos_atr');

            $table->unique(['pmp_prd_id', 'pmp_deleted'], 'uk_pmp_prd_deleted');
            $table->index(['pmp_prd_id', 'pmp_estatus', 'pmp_deleted'], 'idx_pmp_prd_est_del');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_preferencias_matriz_producto_pmp');
    }
};
