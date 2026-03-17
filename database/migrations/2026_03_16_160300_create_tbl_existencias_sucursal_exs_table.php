<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_existencias_sucursal_exs', function (Blueprint $table) {
            $table->bigIncrements('exs_id');
            $table->unsignedBigInteger('exs_psk_id');
            $table->unsignedBigInteger('exs_scl_id');
            $table->decimal('exs_existencia', 12, 2)->default(0);
            $table->string('exs_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('exs_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('exs_updated_by_usr_id')->nullable()->index();
            $table->boolean('exs_deleted')->default(false)->index();
            $table->timestamp('exs_deleted_at')->nullable()->index();
            $table->timestamp('exs_created_at')->nullable();
            $table->timestamp('exs_updated_at')->nullable();

            $table->foreign('exs_psk_id')->references('psk_id')->on('tbl_producto_skus_psk');
            $table->foreign('exs_scl_id')->references('scl_id')->on('tbl_sucursales_scl');

            $table->unique(['exs_psk_id', 'exs_scl_id', 'exs_deleted'], 'uk_exs_sku_scl_del');
            $table->index(['exs_scl_id', 'exs_deleted', 'exs_estatus'], 'idx_exs_scl_del_est');
            $table->index(['exs_psk_id', 'exs_deleted', 'exs_estatus'], 'idx_exs_sku_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_existencias_sucursal_exs');
    }
};
