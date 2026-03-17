<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_producto_skus_psk', function (Blueprint $table) {
            $table->bigIncrements('psk_id');
            $table->unsignedBigInteger('psk_prd_id');
            $table->string('psk_codigo', 60);
            $table->string('psk_nombre', 180)->nullable();
            $table->string('psk_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('psk_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('psk_updated_by_usr_id')->nullable()->index();
            $table->boolean('psk_deleted')->default(false)->index();
            $table->timestamp('psk_deleted_at')->nullable()->index();
            $table->timestamp('psk_created_at')->nullable();
            $table->timestamp('psk_updated_at')->nullable();

            $table->foreign('psk_prd_id')->references('prd_id')->on('tbl_productos_prd');
            $table->unique(['psk_prd_id', 'psk_codigo', 'psk_deleted'], 'uk_producto_sku_codigo_activo');
            $table->index(['psk_prd_id', 'psk_deleted', 'psk_estatus'], 'idx_psk_prd_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_skus_psk');
    }
};
