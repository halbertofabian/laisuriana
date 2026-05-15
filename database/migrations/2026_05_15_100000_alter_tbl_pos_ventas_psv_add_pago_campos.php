<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pos_ventas_psv', function (Blueprint $table) {
            $table->string('psv_metodo_pago', 30)->nullable()->after('psv_total');
            $table->json('psv_pago_detalle')->nullable()->after('psv_metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pos_ventas_psv', function (Blueprint $table) {
            $table->dropColumn(['psv_metodo_pago', 'psv_pago_detalle']);
        });
    }
};

