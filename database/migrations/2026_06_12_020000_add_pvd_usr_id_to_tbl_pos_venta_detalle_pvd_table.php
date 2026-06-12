<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pos_venta_detalle_pvd', function (Blueprint $table) {
            $table->unsignedBigInteger('pvd_usr_id')->nullable()->after('pvd_importe')->index();
        });

        DB::table('tbl_pos_venta_detalle_pvd')
            ->whereNull('pvd_usr_id')
            ->update([
                'pvd_usr_id' => DB::raw('COALESCE(pvd_created_by_usr_id, pvd_updated_by_usr_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tbl_pos_venta_detalle_pvd', function (Blueprint $table) {
            $table->dropColumn('pvd_usr_id');
        });
    }
};
