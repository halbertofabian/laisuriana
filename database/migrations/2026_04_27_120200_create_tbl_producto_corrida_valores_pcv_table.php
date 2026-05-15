<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_producto_corrida_valores_pcv', function (Blueprint $table) {
            $table->bigIncrements('pcv_id');
            $table->unsignedBigInteger('pcv_prc_id')->index();
            $table->unsignedBigInteger('pcv_vat_id')->index();
            $table->string('pcv_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('pcv_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pcv_updated_by_usr_id')->nullable()->index();
            $table->boolean('pcv_deleted')->default(false)->index();
            $table->timestamp('pcv_deleted_at')->nullable()->index();
            $table->timestamp('pcv_created_at')->nullable();
            $table->timestamp('pcv_updated_at')->nullable();

            $table->foreign('pcv_prc_id')->references('prc_id')->on('tbl_producto_corridas_prc');
            $table->foreign('pcv_vat_id')->references('vat_id')->on('tbl_valores_atributo_vat');
            $table->unique(['pcv_prc_id', 'pcv_vat_id', 'pcv_deleted'], 'uk_pcv_prc_vat_del');
            $table->index(['pcv_prc_id', 'pcv_deleted', 'pcv_estatus'], 'idx_pcv_prc_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_corrida_valores_pcv');
    }
};

