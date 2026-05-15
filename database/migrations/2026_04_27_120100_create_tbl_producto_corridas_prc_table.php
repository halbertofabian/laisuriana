<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_producto_corridas_prc', function (Blueprint $table) {
            $table->bigIncrements('prc_id');
            $table->unsignedBigInteger('prc_prd_id')->index();
            $table->unsignedBigInteger('prc_atr_id')->index();
            $table->string('prc_nombre', 120);
            $table->unsignedSmallInteger('prc_orden')->default(1);
            $table->decimal('prc_precio_base', 12, 2)->default(0);
            $table->decimal('prc_costo_base', 12, 2)->default(0);
            $table->unsignedInteger('prc_stock_minimo')->default(0);
            $table->unsignedInteger('prc_stock_maximo')->default(0);
            $table->string('prc_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('prc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('prc_updated_by_usr_id')->nullable()->index();
            $table->boolean('prc_deleted')->default(false)->index();
            $table->timestamp('prc_deleted_at')->nullable()->index();
            $table->timestamp('prc_created_at')->nullable();
            $table->timestamp('prc_updated_at')->nullable();

            $table->foreign('prc_prd_id')->references('prd_id')->on('tbl_productos_prd');
            $table->foreign('prc_atr_id')->references('atr_id')->on('tbl_atributos_atr');
            $table->index(['prc_prd_id', 'prc_deleted', 'prc_estatus'], 'idx_prc_prd_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_corridas_prc');
    }
};

