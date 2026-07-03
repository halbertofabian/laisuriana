<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_corte_denominaciones_pdn', function (Blueprint $table) {
            $table->bigIncrements('pdn_id');
            $table->unsignedBigInteger('pdn_pco_id');
            $table->string('pdn_clave', 20)->index();
            $table->string('pdn_etiqueta', 40);
            $table->string('pdn_tipo', 20)->default('billete')->index();
            $table->unsignedInteger('pdn_cantidad_piezas')->nullable();
            $table->decimal('pdn_monto_unitario', 12, 2)->nullable();
            $table->decimal('pdn_monto', 14, 2)->default(0);
            $table->timestamp('pdn_created_at')->nullable();
            $table->timestamp('pdn_updated_at')->nullable();

            $table->foreign('pdn_pco_id')->references('pco_id')->on('tbl_pos_cortes_pco');
            $table->unique(['pdn_pco_id', 'pdn_clave'], 'uk_pdn_corte_clave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_corte_denominaciones_pdn');
    }
};
