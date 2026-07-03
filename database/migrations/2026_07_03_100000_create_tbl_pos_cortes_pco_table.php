<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pos_cortes_pco', function (Blueprint $table) {
            $table->bigIncrements('pco_id');
            $table->string('pco_folio', 50)->unique();
            $table->unsignedBigInteger('pco_cse_id');
            $table->unsignedBigInteger('pco_caj_id');
            $table->unsignedBigInteger('pco_scl_id');
            $table->unsignedBigInteger('pco_usr_cajero_id');
            $table->unsignedBigInteger('pco_usr_autorizo_id');
            $table->unsignedBigInteger('pco_usr_apertura_id')->nullable();
            $table->timestamp('pco_abierta_at')->nullable()->index();
            $table->dateTime('pco_cerrada_at')->index();
            $table->decimal('pco_efectivo_esperado', 14, 2)->default(0);
            $table->decimal('pco_efectivo_reportado', 14, 2)->default(0);
            $table->decimal('pco_diferencia', 14, 2)->default(0);
            $table->decimal('pco_total_ventas', 14, 2)->default(0);
            $table->decimal('pco_total_retiros', 14, 2)->default(0);
            $table->decimal('pco_total_gastos', 14, 2)->default(0);
            $table->json('pco_resumen_ventas')->nullable();
            $table->json('pco_resumen_metodos_pago')->nullable();
            $table->text('pco_observaciones')->nullable();
            $table->string('pco_estado', 20)->default('cerrado')->index();
            $table->unsignedBigInteger('pco_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('pco_updated_by_usr_id')->nullable()->index();
            $table->boolean('pco_deleted')->default(false)->index();
            $table->timestamp('pco_deleted_at')->nullable()->index();
            $table->timestamp('pco_created_at')->nullable();
            $table->timestamp('pco_updated_at')->nullable();

            $table->foreign('pco_cse_id')->references('cse_id')->on('tbl_caja_sesiones_cse');
            $table->foreign('pco_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('pco_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('pco_usr_cajero_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('pco_usr_autorizo_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('pco_usr_apertura_id')->references('usr_id')->on('tbl_usuarios_usr');

            $table->unique(['pco_cse_id', 'pco_deleted'], 'uk_pco_sesion_activa');
            $table->index(['pco_caj_id', 'pco_cerrada_at'], 'idx_pco_caja_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pos_cortes_pco');
    }
};
