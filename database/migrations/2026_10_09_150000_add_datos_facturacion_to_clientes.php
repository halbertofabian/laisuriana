<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_clientes_cli', function (Blueprint $table): void {
            $table->string('cli_regimen_fiscal', 3)->nullable();
            $table->string('cli_uso_cfdi', 150)->nullable();
            $table->string('cli_forma_pago', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_clientes_cli', function (Blueprint $table): void {
            $table->dropColumn(['cli_regimen_fiscal', 'cli_uso_cfdi', 'cli_forma_pago']);
        });
    }
};
