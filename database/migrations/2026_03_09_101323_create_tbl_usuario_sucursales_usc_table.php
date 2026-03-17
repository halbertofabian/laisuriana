<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuario_sucursales_usc', function (Blueprint $table) {
            $table->bigIncrements('usc_id');
            $table->unsignedBigInteger('usc_usr_id');
            $table->unsignedBigInteger('usc_scl_id');
            $table->boolean('usc_es_predeterminada')->default(false)->index();
            $table->string('usc_estatus', 20)->default('activo')->index();
            $table->boolean('usc_deleted')->default(false)->index();
            $table->timestamp('usc_deleted_at')->nullable()->index();
            $table->timestamp('usc_created_at')->nullable();
            $table->timestamp('usc_updated_at')->nullable();

            $table->foreign('usc_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('usc_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->unique(['usc_usr_id', 'usc_scl_id', 'usc_deleted'], 'uk_usuario_sucursal_activo');
            $table->index(['usc_usr_id', 'usc_deleted']);
            $table->index(['usc_scl_id', 'usc_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuario_sucursales_usc');
    }
};
