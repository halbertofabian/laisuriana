<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_caja_movimientos_cjm', function (Blueprint $table): void {
            $table->json('cjm_denominaciones')->nullable()->after('cjm_monto');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_caja_movimientos_cjm', function (Blueprint $table): void {
            $table->dropColumn('cjm_denominaciones');
        });
    }
};
