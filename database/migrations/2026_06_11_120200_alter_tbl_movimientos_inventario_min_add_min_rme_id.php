<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_movimientos_inventario_min', function (Blueprint $table) {
            $table->unsignedBigInteger('min_rme_id')->nullable()->after('min_prv_id')->index();
            $table->foreign('min_rme_id')->references('rme_id')->on('tbl_recepciones_mercancia_rme');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_movimientos_inventario_min', function (Blueprint $table) {
            $table->dropForeign(['min_rme_id']);
            $table->dropColumn('min_rme_id');
        });
    }
};
