<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Datos del documento congelados al emitir (cliente, referencia e importes), para que el PDF no cambie al reimprimirse. */
    public function up(): void
    {
        Schema::table('tbl_facturacion_fac', function (Blueprint $table) {
            $table->json('fac_documento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_facturacion_fac', fn (Blueprint $table) => $table->dropColumn('fac_documento'));
    }
};
