<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->unsignedBigInteger('prd_prv_id')->nullable()->after('prd_mdl_id');
            $table->string('prd_imagen_tipo', 20)->nullable()->after('prd_descripcion');
            $table->string('prd_imagen_path', 255)->nullable()->after('prd_imagen_tipo');
            $table->string('prd_imagen_url', 500)->nullable()->after('prd_imagen_path');

            $table->foreign('prd_prv_id')->references('prv_id')->on('tbl_proveedores_prv');
            $table->index('prd_prv_id', 'idx_prd_prv_id');
            $table->index('prd_imagen_tipo', 'idx_prd_imagen_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropForeign(['prd_prv_id']);
            $table->dropIndex('idx_prd_prv_id');
            $table->dropIndex('idx_prd_imagen_tipo');
            $table->dropColumn([
                'prd_prv_id',
                'prd_imagen_tipo',
                'prd_imagen_path',
                'prd_imagen_url',
            ]);
        });
    }
};
