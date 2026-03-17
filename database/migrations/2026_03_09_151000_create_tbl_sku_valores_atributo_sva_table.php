<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_sku_valores_atributo_sva', function (Blueprint $table) {
            $table->bigIncrements('sva_id');
            $table->unsignedBigInteger('sva_psk_id');
            $table->unsignedBigInteger('sva_vat_id');
            $table->string('sva_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('sva_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('sva_updated_by_usr_id')->nullable()->index();
            $table->boolean('sva_deleted')->default(false)->index();
            $table->timestamp('sva_deleted_at')->nullable()->index();
            $table->timestamp('sva_created_at')->nullable();
            $table->timestamp('sva_updated_at')->nullable();

            $table->foreign('sva_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('sva_vat_id')->references('vat_id')->on('tbl_valores_atributo_vat');
            $table->unique(['sva_psk_id', 'sva_vat_id', 'sva_deleted'], 'uk_sku_valor_atributo_activo');
            $table->index(['sva_psk_id', 'sva_deleted', 'sva_estatus'], 'idx_sva_psk_del_est');
            $table->index(['sva_vat_id', 'sva_deleted', 'sva_estatus'], 'idx_sva_vat_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sku_valores_atributo_sva');
    }
};
