<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_proveedores_prv', function (Blueprint $table) {
            $table->bigIncrements('prv_id');
            $table->string('prv_clave', 40);
            $table->string('prv_nombre_empresa', 180);
            $table->string('prv_nombre_asesor_ventas', 180)->nullable();
            $table->string('prv_categoria', 120)->nullable();
            $table->string('prv_razon_social', 180)->nullable();
            $table->string('prv_rfc', 13)->nullable();
            $table->string('prv_correo', 160)->nullable()->index();
            $table->string('prv_condiciones_pago', 220)->nullable();
            $table->string('prv_tiempo_respuesta', 120)->nullable();
            $table->string('prv_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('prv_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('prv_updated_by_usr_id')->nullable()->index();
            $table->boolean('prv_deleted')->default(false)->index();
            $table->timestamp('prv_deleted_at')->nullable()->index();
            $table->timestamp('prv_created_at')->nullable();
            $table->timestamp('prv_updated_at')->nullable();

            $table->unique(['prv_clave', 'prv_deleted'], 'uk_proveedor_clave_activo');
            $table->unique(['prv_nombre_empresa', 'prv_deleted'], 'uk_proveedor_nombre_empresa_activo');
            $table->unique(['prv_rfc', 'prv_deleted'], 'uk_proveedor_rfc_activo');
            $table->index(['prv_deleted', 'prv_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proveedores_prv');
    }
};
