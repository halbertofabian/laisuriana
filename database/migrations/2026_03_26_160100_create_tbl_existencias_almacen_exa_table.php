<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_existencias_almacen_exa', function (Blueprint $table) {
            $table->bigIncrements('exa_id');
            $table->unsignedBigInteger('exa_psk_id');
            $table->unsignedBigInteger('exa_scl_id');
            $table->unsignedBigInteger('exa_alm_id');
            $table->decimal('exa_existencia', 14, 2)->default(0);
            $table->string('exa_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('exa_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('exa_updated_by_usr_id')->nullable()->index();
            $table->boolean('exa_deleted')->default(false)->index();
            $table->timestamp('exa_deleted_at')->nullable()->index();
            $table->timestamp('exa_created_at')->nullable();
            $table->timestamp('exa_updated_at')->nullable();

            $table->foreign('exa_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('exa_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('exa_alm_id')->references('alm_id')->on('tbl_almacenes_alm');

            $table->unique(['exa_psk_id', 'exa_scl_id', 'exa_alm_id', 'exa_deleted'], 'uk_exa_sku_scl_alm_del');
            $table->index(['exa_scl_id', 'exa_alm_id', 'exa_deleted', 'exa_estatus'], 'idx_exa_scl_alm_del_est');
            $table->index(['exa_psk_id', 'exa_deleted', 'exa_estatus'], 'idx_exa_sku_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_existencias_almacen_exa');
    }
};
