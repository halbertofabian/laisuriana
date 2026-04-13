<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->string('prd_clave_sat', 20)->nullable()->after('prd_codigo_barras');
            $table->index('prd_clave_sat', 'idx_prd_clave_sat');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_productos_prd', function (Blueprint $table) {
            $table->dropIndex('idx_prd_clave_sat');
            $table->dropColumn('prd_clave_sat');
        });
    }
};
