<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_caja_sesion_usuarios_csu', function (Blueprint $table) {
            $table->bigIncrements('csu_id');
            $table->unsignedBigInteger('csu_cse_id');
            $table->unsignedBigInteger('csu_usr_id');
            $table->timestamp('csu_ingreso_at')->nullable();
            $table->timestamp('csu_salida_at')->nullable()->index();
            $table->string('csu_estatus', 20)->default('activo')->index();
            $table->timestamp('csu_created_at')->nullable();
            $table->timestamp('csu_updated_at')->nullable();

            $table->foreign('csu_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('csu_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->unique(['csu_cse_id', 'csu_usr_id', 'csu_estatus'], 'uk_caja_sesion_usuario_activo');
            $table->index(['csu_usr_id', 'csu_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_caja_sesion_usuarios_csu');
    }
};
