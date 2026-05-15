<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_cajas_caj', function (Blueprint $table): void {
            $table->unsignedBigInteger('caj_alm_id')->nullable()->after('caj_scl_id');
            $table->foreign('caj_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->index('caj_alm_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_cajas_caj', function (Blueprint $table): void {
            $table->dropForeign(['caj_alm_id']);
            $table->dropIndex(['caj_alm_id']);
            $table->dropColumn('caj_alm_id');
        });
    }
};

