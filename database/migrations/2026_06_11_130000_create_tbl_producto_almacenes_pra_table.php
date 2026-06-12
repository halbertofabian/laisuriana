<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_producto_almacenes_pra', function (Blueprint $table) {
            $table->bigIncrements('pra_id');
            $table->unsignedBigInteger('pra_prd_id');
            $table->unsignedBigInteger('pra_alm_id');
            $table->unsignedBigInteger('pra_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pra_updated_by_usr_id')->nullable()->index();
            $table->boolean('pra_deleted')->default(false)->index();
            $table->timestamp('pra_deleted_at')->nullable()->index();
            $table->timestamp('pra_created_at')->nullable();
            $table->timestamp('pra_updated_at')->nullable();

            $table->foreign('pra_prd_id')->references('prd_id')->on('tbl_productos_prd');
            $table->foreign('pra_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->unique(['pra_prd_id', 'pra_alm_id'], 'uniq_pra_prd_alm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_almacenes_pra');
    }
};
