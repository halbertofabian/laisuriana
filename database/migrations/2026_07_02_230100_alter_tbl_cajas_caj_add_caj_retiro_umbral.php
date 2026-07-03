<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_cajas_caj', function (Blueprint $table): void {
            $table->decimal('caj_retiro_umbral', 14, 2)
                ->default(0)
                ->after('caj_clave');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_cajas_caj', function (Blueprint $table): void {
            $table->dropColumn('caj_retiro_umbral');
        });
    }
};
