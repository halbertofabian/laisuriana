<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pedidos_piso_pdp', function (Blueprint $table) {
            $table->unsignedBigInteger('pdp_cli_id')->nullable()->after('pdp_usr_id')->index();
            $table->foreign('pdp_cli_id')->references('cli_id')->on('tbl_clientes_cli');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pedidos_piso_pdp', function (Blueprint $table) {
            $table->dropForeign(['pdp_cli_id']);
            $table->dropColumn('pdp_cli_id');
        });
    }
};
