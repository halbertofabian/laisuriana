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
            $table->unsignedBigInteger('ppd_usr_id')->nullable()->after('ppd_importe')->index();
        });

        DB::table('tbl_pedido_piso_detalle_ppd')
            ->whereNull('ppd_usr_id')
            ->update([
                'ppd_usr_id' => DB::raw('COALESCE(ppd_created_by_usr_id, ppd_updated_by_usr_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tbl_pedido_piso_detalle_ppd', function (Blueprint $table) {
            $table->dropColumn('ppd_usr_id');
        });
    }
};
