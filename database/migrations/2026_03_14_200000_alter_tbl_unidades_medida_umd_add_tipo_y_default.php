<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_unidades_medida_umd', function (Blueprint $table) {
            $table->string('umd_tipo_cantidad', 20)
                ->default('entero')
                ->after('umd_codigo')
                ->index();

            $table->boolean('umd_es_predeterminada')
                ->default(false)
                ->after('umd_tipo_cantidad')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_unidades_medida_umd', function (Blueprint $table) {
            $table->dropColumn([
                'umd_tipo_cantidad',
                'umd_es_predeterminada',
            ]);
        });
    }
};
