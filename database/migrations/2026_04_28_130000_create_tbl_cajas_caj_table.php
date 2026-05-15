<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_cajas_caj', function (Blueprint $table) {
            $table->bigIncrements('caj_id');
            $table->unsignedBigInteger('caj_scl_id');
            $table->string('caj_nombre', 120);
            $table->string('caj_clave', 40);
            $table->string('caj_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('caj_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('caj_updated_by_usr_id')->nullable()->index();
            $table->boolean('caj_deleted')->default(false)->index();
            $table->timestamp('caj_deleted_at')->nullable()->index();
            $table->timestamp('caj_created_at')->nullable();
            $table->timestamp('caj_updated_at')->nullable();

            $table->foreign('caj_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->unique(['caj_scl_id', 'caj_nombre', 'caj_deleted'], 'uk_caja_nombre_sucursal_activo');
            $table->unique(['caj_scl_id', 'caj_clave', 'caj_deleted'], 'uk_caja_clave_sucursal_activo');
            $table->index(['caj_scl_id', 'caj_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_cajas_caj');
    }
};
