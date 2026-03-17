<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_almacenes_alm', function (Blueprint $table) {
            $table->bigIncrements('alm_id');
            $table->unsignedBigInteger('alm_scl_id');
            $table->unsignedBigInteger('alm_tal_id');
            $table->string('alm_nombre', 120);
            $table->string('alm_clave', 40);
            $table->string('alm_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('alm_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('alm_updated_by_usr_id')->nullable()->index();
            $table->boolean('alm_deleted')->default(false)->index();
            $table->timestamp('alm_deleted_at')->nullable()->index();
            $table->timestamp('alm_created_at')->nullable();
            $table->timestamp('alm_updated_at')->nullable();

            $table->foreign('alm_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('alm_tal_id')->references('tal_id')->on('tbl_tipos_almacen_tal');
            $table->unique(['alm_scl_id', 'alm_clave', 'alm_deleted'], 'uk_almacen_sucursal_clave_activo');
            $table->index(['alm_scl_id', 'alm_deleted', 'alm_estatus']);
            $table->index(['alm_tal_id', 'alm_deleted', 'alm_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_almacenes_alm');
    }
};
