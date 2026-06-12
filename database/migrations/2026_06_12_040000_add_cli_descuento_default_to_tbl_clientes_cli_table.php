<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_clientes_cli', function (Blueprint $table) {
            $table->unsignedTinyInteger('cli_descuento_default')->nullable()->after('cli_referencias');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_clientes_cli', function (Blueprint $table) {
            $table->dropColumn('cli_descuento_default');
        });
    }
};
