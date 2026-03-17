<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_categorias_ctg', function (Blueprint $table) {
            // Eliminar el unique anterior que solo validaba nombre
            $table->dropUnique('uk_categoria_nombre_activo');

            // Agregar la FK a línea (nullable para no romper registros existentes)
            $table->unsignedBigInteger('ctg_lna_id')
                ->nullable()
                ->after('ctg_nombre')
                ->index();

            $table->foreign('ctg_lna_id')
                ->references('lna_id')
                ->on('tbl_lineas_lna')
                ->nullOnDelete();

            // Nuevo unique compuesto: nombre + línea + deleted
            $table->unique(['ctg_nombre', 'ctg_lna_id', 'ctg_deleted'], 'uk_categoria_nombre_linea');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_categorias_ctg', function (Blueprint $table) {
            $table->dropForeign(['ctg_lna_id']);
            $table->dropUnique('uk_categoria_nombre_linea');
            $table->dropColumn('ctg_lna_id');
            $table->unique(['ctg_nombre', 'ctg_deleted'], 'uk_categoria_nombre_activo');
        });
    }
};
