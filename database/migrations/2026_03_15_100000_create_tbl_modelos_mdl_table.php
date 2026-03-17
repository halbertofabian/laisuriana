<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de modelos
        Schema::create('tbl_modelos_mdl', function (Blueprint $table) {
            $table->bigIncrements('mdl_id');
            $table->string('mdl_nombre', 120);
            $table->string('mdl_clave', 40);
            $table->string('mdl_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('mdl_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('mdl_updated_by_usr_id')->nullable()->index();
            $table->boolean('mdl_deleted')->default(false)->index();
            $table->timestamp('mdl_deleted_at')->nullable()->index();
            $table->timestamp('mdl_created_at')->nullable();
            $table->timestamp('mdl_updated_at')->nullable();

            $table->unique(['mdl_clave', 'mdl_deleted'], 'uk_modelo_clave');
            $table->unique(['mdl_nombre', 'mdl_deleted'], 'uk_modelo_nombre');
            $table->index(['mdl_deleted', 'mdl_estatus']);
        });

        // Tabla pivote modelo ↔ marcas (muchos a muchos)
        Schema::create('tbl_modelo_marcas_mdm', function (Blueprint $table) {
            $table->bigIncrements('mdm_id');
            $table->unsignedBigInteger('mdm_mdl_id');
            $table->unsignedBigInteger('mdm_mrc_id');
            $table->timestamp('mdm_created_at')->nullable();

            $table->foreign('mdm_mdl_id')->references('mdl_id')->on('tbl_modelos_mdl')->onDelete('cascade');
            $table->foreign('mdm_mrc_id')->references('mrc_id')->on('tbl_marcas_mrc')->onDelete('cascade');
            $table->unique(['mdm_mdl_id', 'mdm_mrc_id'], 'uk_modelo_marca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_modelo_marcas_mdm');
        Schema::dropIfExists('tbl_modelos_mdl');
    }
};
