<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_unidades_medida_umd', function (Blueprint $table) {
            $table->bigIncrements('umd_id');
            $table->string('umd_nombre', 120);
            $table->string('umd_codigo', 20);
            $table->string('umd_clave', 40);
            $table->string('umd_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('umd_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('umd_updated_by_usr_id')->nullable()->index();
            $table->boolean('umd_deleted')->default(false)->index();
            $table->timestamp('umd_deleted_at')->nullable()->index();
            $table->timestamp('umd_created_at')->nullable();
            $table->timestamp('umd_updated_at')->nullable();

            $table->unique(['umd_clave', 'umd_deleted'], 'uk_unidad_clave_activo');
            $table->unique(['umd_codigo', 'umd_deleted'], 'uk_unidad_codigo_activo');
            $table->unique(['umd_nombre', 'umd_deleted'], 'uk_unidad_nombre_activo');
            $table->index(['umd_deleted', 'umd_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_unidades_medida_umd');
    }
};
