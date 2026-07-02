<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->string('ppd_descuento_tipo', 20)->default('ninguno')->after('ppd_precio_unitario');
            $table->decimal('ppd_descuento_valor', 14, 2)->default(0)->after('ppd_descuento_tipo');
            $table->decimal('ppd_descuento_importe', 14, 2)->default(0)->after('ppd_descuento_valor');
            $table->decimal('ppd_total_linea', 14, 2)->default(0)->after('ppd_importe');
        });

        DB::table('tbl_pedido_piso_detalle_ppd')
            ->where(function ($q): void {
                $q->whereNull('ppd_total_linea')
                    ->orWhere('ppd_total_linea', 0);
            })
            ->update([
                'ppd_descuento_tipo' => 'ninguno',
                'ppd_descuento_valor' => 0,
                'ppd_descuento_importe' => 0,
                'ppd_total_linea' => DB::raw('COALESCE(ppd_importe, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->dropColumn([
                'ppd_descuento_tipo',
                'ppd_descuento_valor',
                'ppd_descuento_importe',
                'ppd_total_linea',
            ]);
        });
    }
};
