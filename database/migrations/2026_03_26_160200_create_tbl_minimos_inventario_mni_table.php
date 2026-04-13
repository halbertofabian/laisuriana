<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_minimos_inventario_mni', function (Blueprint $table) {
            $table->bigIncrements('mni_id');
            $table->unsignedBigInteger('mni_psk_id');
            $table->unsignedBigInteger('mni_scl_id');
            $table->unsignedBigInteger('mni_alm_id');
            $table->decimal('mni_minimo', 14, 2)->default(0);
            $table->string('mni_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('mni_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('mni_updated_by_usr_id')->nullable()->index();
            $table->boolean('mni_deleted')->default(false)->index();
            $table->timestamp('mni_deleted_at')->nullable()->index();
            $table->timestamp('mni_created_at')->nullable();
            $table->timestamp('mni_updated_at')->nullable();

            $table->foreign('mni_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('mni_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('mni_alm_id')->references('alm_id')->on('tbl_almacenes_alm');

            $table->unique(['mni_psk_id', 'mni_scl_id', 'mni_alm_id', 'mni_deleted'], 'uk_mni_sku_scl_alm_del');
            $table->index(['mni_scl_id', 'mni_alm_id', 'mni_deleted', 'mni_estatus'], 'idx_mni_scl_alm_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_minimos_inventario_mni');
    }
};
