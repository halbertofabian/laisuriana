<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->decimal('ppd_descuento_cantidad', 14, 2)->default(0)->after('ppd_descuento_importe');
        });

        DB::table('tbl_pedido_piso_detalle_ppd')
            ->where('ppd_deleted', false)
            ->update([
                'ppd_descuento_cantidad' => DB::raw("CASE WHEN COALESCE(ppd_descuento_tipo, 'ninguno') = 'ninguno' THEN 0 ELSE COALESCE(ppd_cantidad, 0) END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->dropColumn('ppd_descuento_cantidad');
        });
    }
};
