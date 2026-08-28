<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pedidos_piso_pdp', function (Blueprint $table): void {
            $table->uuid('pdp_mobile_request_id')->nullable()->after('pdp_folio');
            $table->unique(
                ['pdp_mobile_request_id', 'pdp_alm_id'],
                'uk_pdp_mobile_request_almacen',
            );
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pedidos_piso_pdp', function (Blueprint $table): void {
            $table->dropUnique('uk_pdp_mobile_request_almacen');
            $table->dropColumn('pdp_mobile_request_id');
        });
    }
};
