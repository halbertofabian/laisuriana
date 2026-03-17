<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_lineas_lna', function (Blueprint $table) {
            $table->bigIncrements('lna_id');
            $table->string('lna_nombre', 120);
            $table->string('lna_clave', 40);
            $table->string('lna_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('lna_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('lna_updated_by_usr_id')->nullable()->index();
            $table->boolean('lna_deleted')->default(false)->index();
            $table->timestamp('lna_deleted_at')->nullable()->index();
            $table->timestamp('lna_created_at')->nullable();
            $table->timestamp('lna_updated_at')->nullable();

            $table->unique(['lna_clave', 'lna_deleted'], 'uk_linea_clave_activo');
            $table->unique(['lna_nombre', 'lna_deleted'], 'uk_linea_nombre_activo');
            $table->index(['lna_deleted', 'lna_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_lineas_lna');
    }
};
