<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_proveedor_contactos_prc', function (Blueprint $table) {
            $table->bigIncrements('prc_id');
            $table->unsignedBigInteger('prc_prv_id');
            $table->string('prc_numero', 30);
            $table->unsignedSmallInteger('prc_orden')->default(1);
            $table->string('prc_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('prc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('prc_updated_by_usr_id')->nullable()->index();
            $table->boolean('prc_deleted')->default(false)->index();
            $table->timestamp('prc_deleted_at')->nullable()->index();
            $table->timestamp('prc_created_at')->nullable();
            $table->timestamp('prc_updated_at')->nullable();

            $table->foreign('prc_prv_id')->references('prv_id')->on('tbl_proveedores_prv');
            $table->unique(['prc_prv_id', 'prc_numero', 'prc_deleted'], 'uk_proveedor_contacto_numero_activo');
            $table->index(['prc_prv_id', 'prc_deleted', 'prc_estatus'], 'idx_prc_proveedor_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proveedor_contactos_prc');
    }
};
